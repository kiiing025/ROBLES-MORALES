<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/helpers/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$authController = new AuthController($pdo);
$flash = getFlash();

$oldFullName = old('full_name', '');
$oldUsername = old('username', '');
$oldEmail = old('email', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $authController->register([
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ]);
}

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<section class="auth-page-section">
    <div class="container py-5">
        <div class="row align-items-center g-5 min-vh-75">
            <div class="col-lg-6">
                <span class="landing-badge">Create Account</span>
                <h1 class="landing-title mt-3">Join WeatherHub today</h1>
                <p class="landing-text mt-3">
                    Create your account to save locations, manage personalized alerts,
                    set a default city, and customize your weather dashboard.
                </p>

                <div class="landing-info-panel mt-4">
                    <div class="landing-info-row">
                        <span>Weather Search</span>
                        <strong>Available</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Saved Locations</span>
                        <strong>Enabled</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Dark Mode</span>
                        <strong>Supported</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Smart Alerts</span>
                        <strong>Ready</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="auth-card">
                    <div class="auth-card-header text-center">
                        <div class="auth-icon mb-3"><i data-lucide="user-plus"></i></div>
                        <h2 class="fw-bold mb-1">Register</h2>
                        <p class="text-muted mb-0">Create your WeatherHub account</p>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']) ?> mt-4">
                            <?= e($flash['message']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="mt-4">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input
                                type="text"
                                class="form-control auth-input"
                                id="full_name"
                                name="full_name"
                                value="<?= e($oldFullName) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input
                                type="text"
                                class="form-control auth-input"
                                id="username"
                                name="username"
                                value="<?= e($oldUsername) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control auth-input"
                                id="email"
                                name="email"
                                value="<?= e($oldEmail) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control auth-input"
                                id="password"
                                name="password"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                class="form-control auth-input"
                                id="confirm_password"
                                name="confirm_password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100 auth-submit-btn">Register</button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Already have an account?
                        <a href="login.php" class="fw-semibold">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>