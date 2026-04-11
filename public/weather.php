<?php
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/models/SearchHistory.php';

$weatherResult = null;

function getWeatherInfo(string $city): array
{
    $normalized = strtolower(trim($city));

    $samples = [
        'manila' => ['condition' => 'Sunny', 'temperature' => 33, 'humidity' => 62, 'wind' => 12],
        'cebu' => ['condition' => 'Cloudy', 'temperature' => 30, 'humidity' => 70, 'wind' => 10],
        'davao' => ['condition' => 'Rainy', 'temperature' => 28, 'humidity' => 85, 'wind' => 14],
        'cagayan de oro' => ['condition' => 'Storm', 'temperature' => 27, 'humidity' => 90, 'wind' => 20],
        'butuan' => ['condition' => 'Partly Cloudy', 'temperature' => 29, 'humidity' => 74, 'wind' => 11],
    ];

    if (isset($samples[$normalized])) {
        return $samples[$normalized];
    }

    return [
        'condition' => 'Sunny',
        'temperature' => 31,
        'humidity' => 65,
        'wind' => 9,
    ];
}

function getWeatherIcon(string $condition): string
{
    $condition = strtolower($condition);

    return match ($condition) {
        'sunny' => '☀️',
        'cloudy' => '☁️',
        'rainy' => '🌧️',
        'storm' => '⛈️',
        'partly cloudy' => '⛅',
        'windy' => '💨',
        default => '🌤️',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city = trim($_POST['city'] ?? '');

    if ($city === '') {
        setFlash('danger', 'Please enter a city name.');
        redirect('weather.php');
    }

    $weatherData = getWeatherInfo($city);
    $weatherResult = [
        'city' => $city,
        'condition' => $weatherData['condition'],
        'icon' => getWeatherIcon($weatherData['condition']),
        'temperature' => $weatherData['temperature'],
        'humidity' => $weatherData['humidity'],
        'wind' => $weatherData['wind'],
    ];

    $historyModel = new SearchHistory($pdo);
    $historyModel->add((int) $_SESSION['user']['user_id'], $city);
}
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h2 class="mb-3">Weather Search</h2>
            <p class="text-muted">Search a city to view a sample weather preview with matching weather signs.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" name="city" class="form-control" placeholder="Enter city name" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Search Weather</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($weatherResult): ?>
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="font-size: 3rem;"><?= e($weatherResult['icon']) ?></div>
                    <div>
                        <h3 class="mb-1"><?= e($weatherResult['city']) ?></h3>
                        <p class="text-muted mb-0"><?= e($weatherResult['condition']) ?></p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <strong>Temperature:</strong> <?= e((string) $weatherResult['temperature']) ?>°C
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <strong>Humidity:</strong> <?= e((string) $weatherResult['humidity']) ?>%
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <strong>Wind:</strong> <?= e((string) $weatherResult['wind']) ?> km/h
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>