<nav class="nav">
    <div class="brand">
        <a href="<?= e(base_url('dashboard.php')) ?>">WeatherHub</a>
    </div>
    <?php if (auth_check()): ?>
        <div class="nav-links">
            <a href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
            <a href="<?= e(base_url('weather.php')) ?>">Weather</a>
            <a href="<?= e(base_url('saved.php')) ?>">Saved</a>
            <a href="<?= e(base_url('preferences.php')) ?>">Preferences</a>
            <a href="<?= e(base_url('logout.php')) ?>">Logout</a>
        </div>
    <?php else: ?>
        <div class="nav-links">
            <a href="<?= e(base_url('login.php')) ?>">Login</a>
            <a href="<?= e(base_url('register.php')) ?>">Register</a>
        </div>
    <?php endif; ?>
</nav>
