<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user']);
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
?>

<nav class="navbar navbar-expand-lg app-navbar navbar-dark px-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>">
            WeatherHub
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'admin_dashboard.php' ? 'active fw-semibold' : '' ?>" href="admin_dashboard.php">
                                Admin Dashboard
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active fw-semibold' : '' ?>" href="dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'weather.php' ? 'active fw-semibold' : '' ?>" href="weather.php">
                            Weather
                        </a>
                    </li>

                    <?php if (file_exists(__DIR__ . '/../../../public/alerts.php')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'alerts.php' ? 'active fw-semibold' : '' ?>" href="alerts.php">
                                Alerts
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item ms-lg-3">
                        <span class="nav-link text-white-50 small">
                            <?= htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-light btn-sm ms-lg-2" href="logout.php">
                            Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active fw-semibold' : '' ?>" href="index.php">
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'login.php' ? 'active fw-semibold' : '' ?>" href="login.php">
                            Login
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-light btn-sm ms-lg-2" href="register.php">
                            Register
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>