<?php

require_once __DIR__ . '/../app/middleware/admin.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';

function fetchUserById(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.full_name,
            u.username,
            u.email,
            u.created_at,
            r.role_name
        FROM users u
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        WHERE u.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function getRoleIdByName(PDO $pdo, string $roleName): ?int
{
    $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = :role_name LIMIT 1");
    $stmt->execute(['role_name' => $roleName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int) $row['role_id'] : null;
}

function emailExistsForOtherUser(PDO $pdo, string $email, int $userId): bool
{
    $stmt = $pdo->prepare("
        SELECT user_id 
        FROM users 
        WHERE email = :email AND user_id != :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'email' => $email,
        'user_id' => $userId
    ]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function usernameExistsForOtherUser(PDO $pdo, string $username, int $userId): bool
{
    $stmt = $pdo->prepare("
        SELECT user_id 
        FROM users 
        WHERE username = :username AND user_id != :user_id
        LIMIT 1
    ");
    $stmt->execute([
        'username' => $username,
        'user_id' => $userId
    ]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function emailExists(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function usernameExists(PDO $pdo, string $username): bool
{
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleName = trim($_POST['role_name'] ?? 'user');

        if ($fullName === '' || $username === '' || $email === '' || $password === '' || $roleName === '') {
            setFlash('danger', 'All fields are required for creating a user.');
            redirect('admin_dashboard.php');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Invalid email format.');
            redirect('admin_dashboard.php');
        }

        if (strlen($password) < 8) {
            setFlash('danger', 'Password must be at least 8 characters.');
            redirect('admin_dashboard.php');
        }

        if (!in_array($roleName, ['admin', 'user'], true)) {
            setFlash('danger', 'Invalid role selected.');
            redirect('admin_dashboard.php');
        }

        if (emailExists($pdo, $email)) {
            setFlash('danger', 'Email is already in use.');
            redirect('admin_dashboard.php');
        }

        if (usernameExists($pdo, $username)) {
            setFlash('danger', 'Username is already in use.');
            redirect('admin_dashboard.php');
        }

        $roleId = getRoleIdByName($pdo, $roleName);

        if ($roleId === null) {
            setFlash('danger', 'Role does not exist in the database.');
            redirect('admin_dashboard.php');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, username, email, password_hash, created_at)
                VALUES (:full_name, :username, :email, :password_hash, NOW())
            ");
            $stmt->execute([
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            $newUserId = (int) $pdo->lastInsertId();

            $stmtRole = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                VALUES (:user_id, :role_id)
            ");
            $stmtRole->execute([
                'user_id' => $newUserId,
                'role_id' => $roleId
            ]);

            $stmtPref = $pdo->prepare("
                INSERT INTO user_preferences (user_id, temperature_unit, wind_unit, theme_mode)
                VALUES (:user_id, 'C', 'kph', 'light')
            ");
            $stmtPref->execute([
                'user_id' => $newUserId
            ]);

            $pdo->commit();

            setFlash('success', 'User created successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Failed to create user.');
        }

        redirect('admin_dashboard.php');
    }

    if ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleName = trim($_POST['role_name'] ?? 'user');
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0 || $fullName === '' || $username === '' || $email === '' || $roleName === '') {
            setFlash('danger', 'All required user fields must be filled in.');
            redirect('admin_dashboard.php');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Invalid email format.');
            redirect('admin_dashboard.php');
        }

        if (!in_array($roleName, ['admin', 'user'], true)) {
            setFlash('danger', 'Invalid role selected.');
            redirect('admin_dashboard.php');
        }

        if (emailExistsForOtherUser($pdo, $email, $userId)) {
            setFlash('danger', 'Email is already used by another account.');
            redirect('admin_dashboard.php');
        }

        if (usernameExistsForOtherUser($pdo, $username, $userId)) {
            setFlash('danger', 'Username is already used by another account.');
            redirect('admin_dashboard.php');
        }

        $roleId = getRoleIdByName($pdo, $roleName);

        if ($roleId === null) {
            setFlash('danger', 'Role does not exist in the database.');
            redirect('admin_dashboard.php');
        }

        try {
            $pdo->beginTransaction();

            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    $pdo->rollBack();
                    setFlash('danger', 'New password must be at least 8 characters.');
                    redirect('admin_dashboard.php');
                }

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = :full_name,
                        username = :username,
                        email = :email,
                        password_hash = :password_hash
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = :full_name,
                        username = :username,
                        email = :email
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'user_id' => $userId
                ]);
            }

            $stmtDeleteRole = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $stmtDeleteRole->execute(['user_id' => $userId]);

            $stmtInsertRole = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                VALUES (:user_id, :role_id)
            ");
            $stmtInsertRole->execute([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);

            $pdo->commit();

            setFlash('success', 'User updated successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Failed to update user.');
        }

        redirect('admin_dashboard.php');
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            setFlash('danger', 'Invalid user selected.');
            redirect('admin_dashboard.php');
        }

        if ($userId === (int) $_SESSION['user']['user_id']) {
            setFlash('danger', 'You cannot delete your own admin account while logged in.');
            redirect('admin_dashboard.php');
        }

        try {
            $pdo->beginTransaction();

            $stmtDeleteRoles = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $stmtDeleteRoles->execute(['user_id' => $userId]);

            $stmtDeletePreferences = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = :user_id");
            $stmtDeletePreferences->execute(['user_id' => $userId]);

            $stmtDeleteAlerts = $pdo->prepare("DELETE FROM alerts WHERE user_id = :user_id");
            $stmtDeleteAlerts->execute(['user_id' => $userId]);

            $stmtDeleteSearches = $pdo->prepare("DELETE FROM search_history WHERE user_id = :user_id");
            $stmtDeleteSearches->execute(['user_id' => $userId]);

            $stmtDeleteLocations = $pdo->prepare("DELETE FROM saved_locations WHERE user_id = :user_id");
            $stmtDeleteLocations->execute(['user_id' => $userId]);

            $stmtDeleteUser = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmtDeleteUser->execute(['user_id' => $userId]);

            $pdo->commit();

            setFlash('success', 'User deleted successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Failed to delete user.');
        }

        redirect('admin_dashboard.php');
    }
}

$editUserId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editUser = $editUserId > 0 ? fetchUserById($pdo, $editUserId) : null;

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLocations = (int) $pdo->query("SELECT COUNT(*) FROM saved_locations")->fetchColumn();
$totalAlerts = (int) $pdo->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
$totalSearches = (int) $pdo->query("SELECT COUNT(*) FROM search_history")->fetchColumn();

$users = $pdo->query("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.created_at,
        COALESCE(r.role_name, 'user') AS role_name
    FROM users u
    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.role_id
    ORDER BY u.user_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <section class="premium-hero mb-4">
        <div class="premium-hero-content">
            <div>
                <span class="section-kicker">Admin Panel</span>
                <h1 class="premium-title mb-2">Admin Dashboard</h1>
                <p class="premium-subtitle mb-0">Manage users and monitor platform activity from one place.</p>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="icon-md mb-2"><i data-lucide="users"></i></div>
                    <p class="text-muted mb-2">Total Users</p>
                    <div class="dashboard-stat-number"><?= $totalUsers ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="icon-md mb-2"><i data-lucide="map-pin"></i></div>
                    <p class="text-muted mb-2">Saved Locations</p>
                    <div class="dashboard-stat-number"><?= $totalLocations ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="icon-md mb-2"><i data-lucide="bell-ring"></i></div>
                    <p class="text-muted mb-2">Active Alerts</p>
                    <div class="dashboard-stat-number"><?= $totalAlerts ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="icon-md mb-2"><i data-lucide="search"></i></div>
                    <p class="text-muted mb-2">Search Requests</p>
                    <div class="dashboard-stat-number"><?= $totalSearches ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card premium-panel h-100">
                <div class="card-body">
                    <?php if ($editUser): ?>
                        <span class="section-kicker">Update</span>
                        <h5 class="fw-bold mb-3">Edit User</h5>

                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="user_id" value="<?= (int) $editUser['user_id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= e($editUser['full_name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= e($editUser['username']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= e($editUser['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role_name" class="form-select" required>
                                    <option value="user" <?= $editUser['role_name'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $editUser['role_name'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update User</button>
                                <a href="admin_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <span class="section-kicker">Create</span>
                        <h5 class="fw-bold mb-3">Create User</h5>

                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="create_user">

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role_name" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Create User</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card premium-panel h-100">
                <div class="card-body">
                    <span class="section-kicker">Read / Delete</span>
                    <h5 class="fw-bold mb-3">User Records</h5>

                    <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle premium-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= (int) $user['user_id'] ?></td>
                                            <td><?= e($user['full_name']) ?></td>
                                            <td><?= e($user['username']) ?></td>
                                            <td><?= e($user['email']) ?></td>
                                            <td><?= e(ucfirst($user['role_name'])) ?></td>
                                            <td><?= e($user['created_at']) ?></td>
                                            <td>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <a href="admin_dashboard.php?edit=<?= (int) $user['user_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                                                    <?php if ((int) $user['user_id'] !== (int) $_SESSION['user']['user_id']): ?>
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-light">Current Admin</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No users found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>