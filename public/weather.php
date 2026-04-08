<?php
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/WeatherController.php';

$controller = new WeatherController($pdo);
$userId = (int) $_SESSION['user']['user_id'];
$weatherData = null;
$city = trim((string) ($_GET['city'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'search') {
        $cityInput = trim((string) ($_POST['city_name'] ?? ''));
        $weatherData = $controller->search($userId, $cityInput);
        $city = $weatherData['city'];
    }

    if ($action === 'save') {
        $controller->saveLocation($userId, (string) ($_POST['city_name'] ?? ''));
    }
}

if ($weatherData === null && $city !== '') {
    $weatherData = [
        'city' => $city,
        'condition' => 'Partly Cloudy',
        'temperature' => 29,
        'humidity' => 78,
        'wind' => 12,
        'source' => 'Sample midterm-ready output. Replace this with a real weather API in the final phase.',
    ];
}
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>
<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>
    <div class="panel-card mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <span class="eyebrow text-primary">WEATHER MODULE</span>
                <h2 class="fw-bold mb-1">Search a city</h2>
                <p class="text-muted mb-0">This stores search history and allows saving locations for future implementation.</p>
            </div>
        </div>
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="search">
            <div class="col-md-9">
                <input type="text" name="city_name" class="form-control form-control-lg" placeholder="Enter city name" value="<?= e($city) ?>" required>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Search Weather</button>
            </div>
        </form>
    </div>

    <?php if ($weatherData): ?>
        <div class="weather-card mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                <div>
                    <span class="eyebrow text-info">SEARCH RESULT</span>
                    <h3 class="fw-bold mb-1"><?= e($weatherData['city']) ?></h3>
                    <p class="mb-0 text-white-50"><?= e($weatherData['condition']) ?></p>
                </div>
                <div class="display-4 fw-bold"><?= e((string) $weatherData['temperature']) ?>°C</div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-md-4"><div class="mini-metric"><span>Humidity</span><strong><?= e((string) $weatherData['humidity']) ?>%</strong></div></div>
                <div class="col-md-4"><div class="mini-metric"><span>Wind</span><strong><?= e((string) $weatherData['wind']) ?> kph</strong></div></div>
                <div class="col-md-4"><div class="mini-metric"><span>Data Note</span><strong>Sample Data</strong></div></div>
            </div>
            <p class="small text-white-50 mt-3 mb-0"><?= e($weatherData['source']) ?></p>
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="city_name" value="<?= e($weatherData['city']) ?>">
                <button type="submit" class="btn btn-warning">Save This Location</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>
