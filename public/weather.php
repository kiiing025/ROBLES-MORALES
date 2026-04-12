<?php
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/models/SearchHistory.php';
require_once __DIR__ . '/../app/models/SavedLocation.php';

$weatherResult = null;
$forecast = [];
$recentSearches = [];
$savedLocations = [];
$userId = (int) $_SESSION['user']['user_id'];

$historyModel = new SearchHistory($pdo);
$savedLocationModel = new SavedLocation($pdo);

function getWeatherInfo(string $city): array
{
    $normalized = strtolower(trim($city));

    $samples = [
        'manila' => [
            'condition' => 'Sunny',
            'temperature' => 33,
            'feels_like' => 36,
            'humidity' => 62,
            'wind' => 12,
            'country' => 'Philippines',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ],
        'cebu' => [
            'condition' => 'Cloudy',
            'temperature' => 30,
            'feels_like' => 32,
            'humidity' => 70,
            'wind' => 10,
            'country' => 'Philippines',
            'latitude' => 10.3157,
            'longitude' => 123.8854,
        ],
        'davao' => [
            'condition' => 'Rainy',
            'temperature' => 28,
            'feels_like' => 30,
            'humidity' => 85,
            'wind' => 14,
            'country' => 'Philippines',
            'latitude' => 7.1907,
            'longitude' => 125.4553,
        ],
        'cagayan de oro' => [
            'condition' => 'Storm',
            'temperature' => 27,
            'feels_like' => 29,
            'humidity' => 90,
            'wind' => 20,
            'country' => 'Philippines',
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ],
        'butuan' => [
            'condition' => 'Partly Cloudy',
            'temperature' => 29,
            'feels_like' => 31,
            'humidity' => 74,
            'wind' => 11,
            'country' => 'Philippines',
            'latitude' => 8.9475,
            'longitude' => 125.5406,
        ],
    ];

    if (isset($samples[$normalized])) {
        return $samples[$normalized];
    }

    return [
        'condition' => 'Sunny',
        'temperature' => 31,
        'feels_like' => 33,
        'humidity' => 65,
        'wind' => 9,
        'country' => 'Philippines',
        'latitude' => 0.0,
        'longitude' => 0.0,
    ];
}

function getWeatherIconPath(string $condition): string
{
    $condition = strtolower(trim($condition));

    return match ($condition) {
        'sunny' => 'assets/icons/sunny.svg',
        'cloudy' => 'assets/icons/cloudy.svg',
        'rainy' => 'assets/icons/rainy.svg',
        'storm' => 'assets/icons/storm.svg',
        'partly cloudy' => 'assets/icons/partly-cloudy.svg',
        default => 'assets/icons/sunny.svg',
    };
}

function getWeatherThemeClass(string $condition): string
{
    $condition = strtolower(trim($condition));

    return match ($condition) {
        'sunny' => 'weather-theme-sunny',
        'cloudy' => 'weather-theme-cloudy',
        'rainy' => 'weather-theme-rainy',
        'storm' => 'weather-theme-storm',
        'partly cloudy' => 'weather-theme-partly',
        default => 'weather-theme-sunny',
    };
}

function getWeatherTip(string $condition, int $temperature): string
{
    $condition = strtolower(trim($condition));

    if ($condition === 'storm') {
        return 'Avoid outdoor travel if possible and keep devices charged.';
    }

    if ($condition === 'rainy') {
        return 'Bring an umbrella and wear waterproof footwear.';
    }

    if ($condition === 'cloudy') {
        return 'Weather is mild today. A light jacket may be enough.';
    }

    if ($condition === 'partly cloudy') {
        return 'Good weather for light outdoor activities. Stay updated for changes.';
    }

    if ($temperature >= 33) {
        return 'Stay hydrated and avoid too much direct sun exposure.';
    }

    return 'Weather looks favorable today. Stay prepared and monitor updates.';
}

function getForecastSamples(string $condition, int $temperature): array
{
    return [
        ['day' => 'Tomorrow', 'condition' => $condition, 'temp' => $temperature - 1],
        ['day' => 'Next Day', 'condition' => 'Cloudy', 'temp' => max($temperature - 2, 24)],
        ['day' => 'Day 3', 'condition' => 'Rainy', 'temp' => max($temperature - 3, 23)],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'search_weather') {
        $city = trim($_POST['city'] ?? '');

        if ($city === '') {
            setFlash('danger', 'Please enter a city name.');
            redirect('weather.php');
        }

        $weatherData = getWeatherInfo($city);

        $weatherResult = [
            'city' => $city,
            'country' => $weatherData['country'],
            'condition' => $weatherData['condition'],
            'icon_path' => getWeatherIconPath($weatherData['condition']),
            'theme_class' => getWeatherThemeClass($weatherData['condition']),
            'temperature' => $weatherData['temperature'],
            'feels_like' => $weatherData['feels_like'],
            'humidity' => $weatherData['humidity'],
            'wind' => $weatherData['wind'],
            'latitude' => $weatherData['latitude'],
            'longitude' => $weatherData['longitude'],
            'tip' => getWeatherTip($weatherData['condition'], $weatherData['temperature']),
        ];

        $forecast = getForecastSamples($weatherData['condition'], $weatherData['temperature']);
        $historyModel->add($userId, $city);
    }

    if ($action === 'save_location') {
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $latitude = isset($_POST['latitude']) ? (float) $_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) ? (float) $_POST['longitude'] : null;

        if ($city === '') {
            setFlash('danger', 'Invalid city to save.');
            redirect('weather.php');
        }

        if ($savedLocationModel->exists($userId, $city)) {
            setFlash('danger', 'This location is already saved.');
            redirect('weather.php');
        }

        $savedLocationModel->add($userId, $city, $country !== '' ? $country : null, $latitude, $longitude);
        setFlash('success', 'Location saved successfully.');
        redirect('weather.php');
    }
}

$recentSearches = $historyModel->allByUser($userId);
$savedLocations = $savedLocationModel->allByUser($userId);
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <div class="card shadow-sm border-0 rounded-4 mb-4 weather-search-card">
        <div class="card-body p-4">
            <h2 class="mb-2">Weather Search</h2>
            <p class="text-muted mb-4">Search a city and view weather condition, forecast preview, and useful weather tips.</p>

            <form method="POST">
                <input type="hidden" name="action" value="search_weather">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" name="city" class="form-control form-control-lg" placeholder="Enter city name" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Search Weather</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($weatherResult): ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4 weather-result-card <?= e($weatherResult['theme_class']) ?>">
            <div class="card-body p-4">
                <div class="row align-items-center g-4">
                    <div class="col-md-4 text-center">
                        <img src="<?= e($weatherResult['icon_path']) ?>" alt="<?= e($weatherResult['condition']) ?>" class="weather-icon-img mb-3">
                        <h3 class="mb-1"><?= e($weatherResult['city']) ?></h3>
                        <p class="mb-0 text-muted"><?= e($weatherResult['country']) ?></p>
                    </div>

                    <div class="col-md-4">
                        <div class="display-5 fw-bold mb-2"><?= e((string) $weatherResult['temperature']) ?>°C</div>
                        <p class="mb-1"><strong>Condition:</strong> <?= e($weatherResult['condition']) ?></p>
                        <p class="mb-1"><strong>Feels Like:</strong> <?= e((string) $weatherResult['feels_like']) ?>°C</p>
                        <p class="mb-1"><strong>Humidity:</strong> <?= e((string) $weatherResult['humidity']) ?>%</p>
                        <p class="mb-0"><strong>Wind:</strong> <?= e((string) $weatherResult['wind']) ?> km/h</p>
                    </div>

                    <div class="col-md-4">
                        <div class="weather-tip-box">
                            <h5 class="mb-2">Weather Tip</h5>
                            <p class="mb-0"><?= e($weatherResult['tip']) ?></p>
                        </div>

                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="save_location">
                            <input type="hidden" name="city" value="<?= e($weatherResult['city']) ?>">
                            <input type="hidden" name="country" value="<?= e($weatherResult['country']) ?>">
                            <input type="hidden" name="latitude" value="<?= e((string) $weatherResult['latitude']) ?>">
                            <input type="hidden" name="longitude" value="<?= e((string) $weatherResult['longitude']) ?>">
                            <button type="submit" class="btn btn-success w-100">Save This Location</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($forecast as $dayForecast): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body text-center">
                            <img src="<?= e(getWeatherIconPath($dayForecast['condition'])) ?>" alt="<?= e($dayForecast['condition']) ?>" class="forecast-icon-img mb-2">
                            <h5><?= e($dayForecast['day']) ?></h5>
                            <p class="mb-1"><?= e($dayForecast['condition']) ?></p>
                            <p class="mb-0 fw-bold"><?= e((string) $dayForecast['temp']) ?>°C</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <h4 class="mb-3">Saved Locations</h4>
                    <?php if (!empty($savedLocations)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($savedLocations as $location): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?= e($location['city_name']) ?></strong>
                                        <?php if (!empty($location['country'])): ?>
                                            <span class="text-muted">- <?= e($location['country']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="badge bg-primary rounded-pill">Saved</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No saved locations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <h4 class="mb-3">Recent Searches</h4>
                    <?php if (!empty($recentSearches)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentSearches as $history): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= e($history['city_name']) ?></span>
                                    <small class="text-muted"><?= e($history['searched_at']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent searches yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>