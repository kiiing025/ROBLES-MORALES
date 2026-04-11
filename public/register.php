<?php
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController($pdo);
    $controller->register($_POST);
}
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>
<div class="auth-shell">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <span class="eyebrow text-primary">REGISTER</span>
                        <h2 class="fw-bold mt-2">Create your WeatherHub account</h2>
                    </div>
                    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control form-control-lg" value="<?= old('full_name') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control form-control-lg" value="<?= old('username') ?>" required>
                            <div class="form-text">Use 4 to 20 letters, numbers, or underscore.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg" value="<?= old('email') ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control form-control-lg" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Register</button>
                    </form>
                    <p class="text-center mt-4 mb-0">Already have an account? <a href="login.php">Login here</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>
