<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user']);
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
?>

<nav class="navbar navbar-expand-lg app-navbar navbar-dark px-3 py-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold app-brand" href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>">
            <span class="brand-logo">🌦️</span>
            <span>WeatherHub</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link app-nav-link <?= $currentPage === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
                                Admin
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link app-nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link app-nav-link <?= $currentPage === 'weather.php' ? 'active' : '' ?>" href="weather.php">
                            Weather
                        </a>
                    </li>

                    <?php if (file_exists(__DIR__ . '/../../../public/saved_locations.php')): ?>
                        <li class="nav-item">
                            <a class="nav-link app-nav-link <?= $currentPage === 'saved_locations.php' ? 'active' : '' ?>" href="saved_locations.php">
                                Saved
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (file_exists(__DIR__ . '/../../../public/alerts.php')): ?>
                        <li class="nav-item">
                            <a class="nav-link app-nav-link <?= $currentPage === 'alerts.php' ? 'active' : '' ?>" href="alerts.php">
                                Alerts
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item ms-lg-3">
                        <div class="user-chip">
                            <span class="user-chip-avatar">
                                <?= strtoupper(substr($_SESSION['user']['username'] ?? 'U', 0, 1)) ?>
                            </span>
                            <span class="user-chip-name">
                                <?= htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light app-logout-btn" href="logout.php">
                            Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link app-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link app-nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>" href="login.php">
                            Login
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light app-logout-btn" href="register.php">
                            Register
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>