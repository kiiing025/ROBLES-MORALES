<?php
require_once __DIR__ . '/../app/middleware/admin.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userModel = new User($pdo);
$users = $userModel->getAllUsers();
$currentAdminId = (int) $_SESSION['user']['user_id'];
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Admin Dashboard</h2>
            <p class="text-muted mb-0">Welcome, <?= e($_SESSION['user']['full_name']) ?>.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <h4 class="mb-3">All Registered Users</h4>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th style="width: 180px;">Role Action</th>
                            <th style="width: 140px;">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= e((string) $user['user_id']) ?></td>
                                    <td><?= e($user['full_name']) ?></td>
                                    <td><?= e($user['username']) ?></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['role_name'] === 'admin'): ?>
                                            <span class="badge bg-warning text-dark">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($user['created_at']) ?></td>

                                    <td>
                                        <?php if ((int) $user['user_id'] === $currentAdminId): ?>
                                            <span class="badge bg-info text-dark">Current Admin</span>
                                        <?php else: ?>
                                            <form method="POST" action="update_user_role.php">
                                                <input type="hidden" name="user_id" value="<?= e((string) $user['user_id']) ?>">
                                                <?php if ($user['role_name'] === 'admin'): ?>
                                                    <input type="hidden" name="role_name" value="user">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">Set as User</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="role_name" value="admin">
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">Set as Admin</button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $user['user_id'] === $currentAdminId): ?>
                                            <span class="badge bg-secondary">Protected</span>
                                        <?php else: ?>
                                            <form method="POST" action="delete_user.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="user_id" value="<?= e((string) $user['user_id']) ?>">
                                                <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>