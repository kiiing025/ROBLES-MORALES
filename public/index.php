<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="eyebrow">WEATHERHUB</span>
                <h1 class="display-4 fw-bold mb-3">Secure login, clean architecture, and a future-ready database.</h1>
                <p class="lead text-white-50 mb-4">WeatherHub is a web-based weather information system built for IT223. It demonstrates registration, login, authentication, separation of concerns, and scalable MySQL design.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="register.php" class="btn btn-warning btn-lg">Create Account</a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="glass-card p-4 p-lg-5">
                    <h3 class="fw-bold mb-3">What this project already includes</h3>
                    <ul class="feature-list mb-0">
                        <li>Fully working registration with validation</li>
                        <li>Secure login with password hashing and sessions</li>
                        <li>Protected pages using authentication middleware</li>
                        <li>Professional folder structure</li>
                        <li>Normalized MySQL database ready for future expansion</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>
