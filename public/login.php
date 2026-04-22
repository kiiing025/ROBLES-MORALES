<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/helpers/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$authController = new AuthController($pdo);
$flash = getFlash();
$oldLogin = old('login', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $authController->login([
        'login' => trim($_POST['login'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ]);
}

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<section class="auth-page-section">
    <div class="container py-5">
        <div class="row align-items-center g-5 min-vh-75">
            <div class="col-lg-6">
                <span class="landing-badge">Welcome Back</span>
                <h1 class="landing-title mt-3">Login to WeatherHub</h1>
                <p class="landing-text mt-3">
                    Access your dashboard, track real-time weather, manage alerts, save locations,
                    and personalize your weather experience.
                </p>

                <div class="row g-3 mt-4">
                    <div class="col-sm-4">
                        <div class="landing-stat-box">
                            <div class="icon-md mb-2"><i data-lucide="cloud-sun"></i></div>
                            <h4>Live</h4>
                            <p>Weather data</p>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="landing-stat-box">
                            <div class="icon-md mb-2"><i data-lucide="bell-ring"></i></div>
                            <h4>Smart</h4>
                            <p>Alerts system</p>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="landing-stat-box">
                            <div class="icon-md mb-2"><i data-lucide="shield-check"></i></div>
                            <h4>Secure</h4>
                            <p>User access</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="auth-card">
                    <div class="auth-card-header text-center">
                        <div class="auth-icon mb-3"><i data-lucide="lock-keyhole"></i></div>
                        <h2 class="fw-bold mb-1">Login</h2>
                        <p class="text-muted mb-0">Access your WeatherHub account</p>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']) ?> mt-4">
                            <?= e($flash['message']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="mt-4">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label for="login" class="form-label">Email or Username</label>
                            <input
                                type="text"
                                class="form-control auth-input"
                                id="login"
                                name="login"
                                value="<?= e($oldLogin) ?>"
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

                        <button type="submit" class="btn btn-primary w-100 auth-submit-btn">Login</button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Don’t have an account?
                        <a href="register.php" class="fw-semibold">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>