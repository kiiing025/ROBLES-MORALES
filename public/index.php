<?php
session_start();

if (isset($_SESSION['user'])) {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    }

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeatherHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">WeatherHub</a>
        <div class="ms-auto d-flex gap-2">
            <a href="login.php" class="btn btn-outline-primary">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>
    </div>
</nav>

<section class="hero-section text-center d-flex align-items-center">
    <div class="container">
        <h1 class="display-4 fw-bold">WeatherHub</h1>
        <p class="lead mt-3">
            Your smart weather companion. Stay updated, stay prepared, and manage your weather experience with ease.
        </p>

        <div class="mt-4">
            <a href="register.php" class="btn btn-primary btn-lg me-2">Get Started</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Main Features</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="feature-card h-100">
                    <h5>Weather Search</h5>
                    <p>Search any city and view weather details instantly.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="feature-card h-100">
                    <h5>Saved Locations</h5>
                    <p>Store your favorite cities for easier access.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="feature-card h-100">
                    <h5>Recent Searches</h5>
                    <p>Review previously searched locations quickly.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="feature-card h-100">
                    <h5>Smart Tips</h5>
                    <p>Get weather-based recommendations for daily use.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Weather Preview</h2>
        <div class="weather-preview-card mx-auto">
            <img src="assets/icons/sunny.svg" alt="Sunny" style="width: 80px; height: 80px; margin-bottom: 15px;">
            <h3>Manila, Philippines</h3>
            <h1>33°C</h1>
            <p class="mb-2">Sunny</p>
            <small>Stay hydrated and avoid too much direct sun exposure.</small>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Why Use WeatherHub?</h2>
        <p class="w-75 mx-auto text-muted">
            WeatherHub is designed to improve user experience by combining weather search, saved locations, search history,
            smart insights, and user-friendly dashboards into one organized web application.
        </p>
    </div>
</section>

<footer class="text-center py-4 bg-light">
    <p class="mb-0">© 2026 WeatherHub. All rights reserved.</p>
</footer>

</body>
</html>