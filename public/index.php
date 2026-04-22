<?php

require_once __DIR__ . '/../app/helpers/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<section class="landing-hero-section">
    <div class="container py-5">
        <div class="row align-items-center g-5 min-vh-75">
            <div class="col-lg-6">
                <span class="landing-badge">Smart Weather Monitoring</span>
                <h1 class="landing-title">Stay updated with live weather, alerts, and saved locations.</h1>
                <p class="landing-text mt-3">
                    WeatherHub helps users search real-time weather, monitor important conditions,
                    manage saved cities, and personalize the experience with alerts, dark mode, and default locations.
                </p>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="register.php" class="btn btn-primary landing-btn-primary">Get Started</a>
                    <a href="login.php" class="btn btn-outline-primary landing-btn-outline">Login</a>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-sm-4">
                        <div class="landing-stat-box">
                            <div class="icon-md mb-2"><i data-lucide="cloud-sun"></i></div>
                            <h4>Live</h4>
                            <p>Weather search</p>
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
                            <p>User login flow</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="landing-preview-card">
                    <div class="landing-preview-top">
                        <div>
                            <span class="section-kicker">Sample Preview</span>
                            <h4 class="mb-1">Cagayan de Oro</h4>
                            <p class="text-muted mb-0">Current weather interface</p>
                        </div>
                        <div class="landing-weather-icon">
                            <i data-lucide="cloud-sun"></i>
                        </div>
                    </div>

                    <div class="landing-preview-temp mt-4">29°C</div>
                    <p class="landing-preview-condition">Partly Cloudy</p>

                    <div class="row g-3 mt-2">
                        <div class="col-4">
                            <div class="landing-mini-card">
                                <div class="icon-sm mb-2"><i data-lucide="droplets"></i></div>
                                <span>Humidity</span>
                                <strong>78%</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="landing-mini-card">
                                <div class="icon-sm mb-2"><i data-lucide="wind"></i></div>
                                <span>Wind</span>
                                <strong>12 kph</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="landing-mini-card">
                                <div class="icon-sm mb-2"><i data-lucide="triangle-alert"></i></div>
                                <span>Alert</span>
                                <strong>Active</strong>
                            </div>
                        </div>
                    </div>

                    <div class="landing-tip-box mt-4">
                        <strong>Tip:</strong> Save your default city to open weather updates faster.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-kicker">Features</span>
            <h2 class="landing-section-title">Why use WeatherHub?</h2>
            <p class="landing-section-text">
                The system is designed to be functional, secure, and easy to use for everyday weather tracking.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="feature-card h-100">
                    <div class="feature-icon"><i data-lucide="cloud-sun"></i></div>
                    <h5>Live Weather</h5>
                    <p>Search cities and get real-time weather data with a 5-day forecast.</p>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="feature-card h-100">
                    <div class="feature-icon"><i data-lucide="bell-ring"></i></div>
                    <h5>Smart Alerts</h5>
                    <p>Create personalized weather alerts for rain, temperature, and wind conditions.</p>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="feature-card h-100">
                    <div class="feature-icon"><i data-lucide="map-pin"></i></div>
                    <h5>Saved Locations</h5>
                    <p>Store important cities and assign a default location for quick access.</p>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="feature-card h-100">
                    <div class="feature-icon"><i data-lucide="shield-check"></i></div>
                    <h5>Secure Access</h5>
                    <p>Protected login, CSRF protection, and rate limiting improve account security.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-section landing-soft-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="section-kicker">Built for usability</span>
                <h2 class="landing-section-title">Simple, practical, and presentation-ready.</h2>
                <p class="landing-section-text text-start mx-0">
                    WeatherHub combines weather search, alerts, saved locations, theme customization,
                    and dashboard summaries into one organized interface.
                </p>

                <ul class="landing-benefits">
                    <li>Quick access to default location</li>
                    <li>Visual weather alerts and dashboard summary cards</li>
                    <li>Dark mode support</li>
                    <li>Organized folder structure and MySQL-backed data</li>
                </ul>
            </div>

            <div class="col-lg-6">
                <div class="landing-info-panel">
                    <div class="landing-info-row">
                        <span>Authentication</span>
                        <strong>Ready</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Weather API</span>
                        <strong>Integrated</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Alerts</span>
                        <strong>Enabled</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Theme Mode</span>
                        <strong>Supported</strong>
                    </div>
                    <div class="landing-info-row">
                        <span>Security</span>
                        <strong>Improved</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-footer-section">
    <div class="container text-center">
        <h3 class="mb-3">Ready to use WeatherHub?</h3>
        <p class="text-muted mb-4">Create an account and start monitoring weather conditions more efficiently.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="register.php" class="btn btn-primary landing-btn-primary">Register</a>
            <a href="login.php" class="btn btn-outline-primary landing-btn-outline">Login</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>