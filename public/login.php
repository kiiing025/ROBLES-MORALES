<?php
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController($pdo);
    $controller->login($_POST);
}
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>
<div class="auth-shell">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <span class="eyebrow text-primary">LOGIN</span>
                        <h2 class="fw-bold mt-2">Welcome back</h2>
                        <p class="text-muted mb-0">Only authenticated users can access protected pages.</p>
                    </div>
                    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Email or Username</label>
                            <input type="text" name="login" class="form-control form-control-lg" value="<?= old('login') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Login</button>
                    </form>
                    <p class="text-center mt-4 mb-0">No account yet? <a href="register.php">Create one</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>
