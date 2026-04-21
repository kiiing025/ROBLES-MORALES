<?php

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/SearchHistory.php';
require_once __DIR__ . '/../app/models/SavedLocation.php';
require_once __DIR__ . '/../app/models/UserPreference.php';
require_once __DIR__ . '/../app/models/Alert.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userId = (int) $_SESSION['user']['user_id'];

$historyModel = new SearchHistory($pdo);
$savedLocationModel = new SavedLocation($pdo);
$preferenceModel = new UserPreference($pdo);
$alertModel = new Alert($pdo);

$preferences = $preferenceModel->findByUser($userId);
$temperatureUnit = $preferences['temperature_unit'] ?? 'C';
$windUnit = $preferences['wind_unit'] ?? 'kph';
$themeMode = $preferences['theme_mode'] ?? 'light';

function httpGetJson(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "User-Agent: WeatherHub/1.0\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    return is_array($data) ? $data : null;
}

function getWeatherCodeLabel(int $code): string
{
    return match ($code) {
        0 => 'Clear Sky',
        1, 2 => 'Partly Cloudy',
        3 => 'Cloudy',
        45, 48 => 'Fog',
        51, 53, 55, 56, 57 => 'Drizzle',
        61, 63, 65, 66, 67, 80, 81, 82 => 'Rainy',
        71, 73, 75, 77, 85, 86 => 'Snow',
        95, 96, 99 => 'Storm',
        default => 'Unknown',
    };
}

function getWeatherIcon(string $condition): string
{
    $condition = strtolower($condition);

    return match (true) {
        str_contains($condition, 'clear') => '☀️',
        str_contains($condition, 'partly') => '⛅',
        str_contains($condition, 'cloud') => '☁️',
        str_contains($condition, 'rain') => '🌧️',
        str_contains($condition, 'drizzle') => '🌦️',
        str_contains($condition, 'storm') => '⛈️',
        str_contains($condition, 'snow') => '❄️',
        str_contains($condition, 'fog') => '🌫️',
        default => '🌤️',
    };
}

function getThemeClass(string $condition): string
{
    $condition = strtolower($condition);

    return match (true) {
        str_contains($condition, 'clear') => 'weather-theme-clear',
        str_contains($condition, 'partly') => 'weather-theme-partly',
        str_contains($condition, 'cloud') => 'weather-theme-cloudy',
        str_contains($condition, 'rain') || str_contains($condition, 'drizzle') => 'weather-theme-rainy',
        str_contains($condition, 'storm') => 'weather-theme-storm',
        str_contains($condition, 'fog') => 'weather-theme-fog',
        default => 'weather-theme-default',
    };
}

function getWeatherTip(string $condition, float $temperatureCelsius): string
{
    $condition = strtolower($condition);

    if (str_contains($condition, 'storm')) {
        return 'Avoid outdoor activities if possible and keep your phone charged.';
    }

    if (str_contains($condition, 'rain') || str_contains($condition, 'drizzle')) {
        return 'Bring an umbrella and expect slippery roads.';
    }

    if (str_contains($condition, 'fog')) {
        return 'Travel carefully because visibility may be low.';
    }

    if ($temperatureCelsius >= 34) {
        return 'Stay hydrated and avoid too much direct sunlight.';
    }

    if (str_contains($condition, 'cloud')) {
        return 'Weather looks mild today. Great for light outdoor activities.';
    }

    return 'Conditions look stable. It is a good day to plan ahead.';
}

function convertTemperature(float $celsius, string $unit): float
{
    if ($unit === 'F') {
        return ($celsius * 9 / 5) + 32;
    }

    return $celsius;
}

function convertWind(float $kmh, string $unit): float
{
    if ($unit === 'mph') {
        return $kmh * 0.621371;
    }

    return $kmh;
}

function formatTemperature(float $celsius, string $unit): string
{
    $value = convertTemperature($celsius, $unit);
    return number_format($value, 1) . '°' . $unit;
}

function formatWind(float $kmh, string $unit): string
{
    $value = convertWind($kmh, $unit);
    return number_format($value, 1) . ' ' . $unit;
}

function fetchWeatherData(string $city): ?array
{
    $geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?name=' . urlencode($city) . '&count=1&language=en&format=json';
    $geoData = httpGetJson($geoUrl);

    if (!$geoData || empty($geoData['results'][0])) {
        return null;
    }

    $place = $geoData['results'][0];
    $latitude = (float) $place['latitude'];
    $longitude = (float) $place['longitude'];

    $forecastUrl = 'https://api.open-meteo.com/v1/forecast?latitude=' . $latitude .
        '&longitude=' . $longitude .
        '&current=temperature_2m,relative_humidity_2m,apparent_temperature,wind_speed_10m,weather_code' .
        '&daily=weather_code,temperature_2m_max,temperature_2m_min' .
        '&timezone=auto&forecast_days=5';

    $forecastData = httpGetJson($forecastUrl);

    if (!$forecastData || empty($forecastData['current']) || empty($forecastData['daily'])) {
        return null;
    }

    $current = $forecastData['current'];
    $daily = $forecastData['daily'];

    $forecast = [];
    $count = min(count($daily['time'] ?? []), 5);

    for ($i = 0; $i < $count; $i++) {
        $forecast[] = [
            'date' => $daily['time'][$i],
            'condition' => getWeatherCodeLabel((int) $daily['weather_code'][$i]),
            'icon' => getWeatherIcon(getWeatherCodeLabel((int) $daily['weather_code'][$i])),
            'temp_max_c' => (float) $daily['temperature_2m_max'][$i],
            'temp_min_c' => (float) $daily['temperature_2m_min'][$i],
        ];
    }

    return [
        'city' => $place['name'],
        'country' => $place['country'] ?? '',
        'latitude' => $latitude,
        'longitude' => $longitude,
        'condition' => getWeatherCodeLabel((int) $current['weather_code']),
        'icon' => getWeatherIcon(getWeatherCodeLabel((int) $current['weather_code'])),
        'temperature_c' => (float) $current['temperature_2m'],
        'feels_like_c' => (float) $current['apparent_temperature'],
        'humidity' => (int) $current['relative_humidity_2m'],
        'wind_kph' => (float) $current['wind_speed_10m'],
        'tip' => getWeatherTip(getWeatherCodeLabel((int) $current['weather_code']), (float) $current['temperature_2m']),
        'theme_class' => getThemeClass(getWeatherCodeLabel((int) $current['weather_code'])),
        'forecast' => $forecast,
    ];
}

function getTriggeredAlerts(array $userAlerts, array $weatherResult, string $temperatureUnit, string $windUnit): array
{
    $triggered = [];

    foreach ($userAlerts as $alert) {
        if (strcasecmp(trim($alert['city']), trim($weatherResult['city'])) !== 0) {
            continue;
        }

        $type = $alert['condition_type'];
        $threshold = $alert['threshold_value'] !== null ? (float) $alert['threshold_value'] : null;

        if ($type === 'rain') {
            $condition = strtolower($weatherResult['condition']);

            if (
                str_contains($condition, 'rain') ||
                str_contains($condition, 'drizzle') ||
                str_contains($condition, 'storm')
            ) {
                $triggered[] = 'Rain alert triggered for ' . $weatherResult['city'] . '.';
            }
        }

        if ($type === 'temperature' && $threshold !== null) {
            $currentTemp = convertTemperature($weatherResult['temperature_c'], $temperatureUnit);

            if ($currentTemp >= $threshold) {
                $triggered[] = 'Temperature alert triggered for ' . $weatherResult['city'] . ' at ' .
                    number_format($currentTemp, 1) . '°' . $temperatureUnit . '.';
            }
        }

        if ($type === 'wind' && $threshold !== null) {
            $currentWind = convertWind($weatherResult['wind_kph'], $windUnit);

            if ($currentWind >= $threshold) {
                $triggered[] = 'Wind alert triggered for ' . $weatherResult['city'] . ' at ' .
                    number_format($currentWind, 1) . ' ' . $windUnit . '.';
            }
        }
    }

    return $triggered;
}

$weatherResult = null;
$triggeredAlerts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'search_weather') {
        $city = trim($_POST['city'] ?? '');

        if ($city === '') {
            setFlash('danger', 'Please enter a city name.');
            redirect('weather.php');
        }

        $weatherResult = fetchWeatherData($city);

        if ($weatherResult === null) {
            setFlash('danger', 'Weather data could not be found for that city.');
            redirect('weather.php');
        }

        $historyModel->add($userId, $weatherResult['city']);
        $userAlerts = $alertModel->getUserAlerts($userId);
        $triggeredAlerts = getTriggeredAlerts($userAlerts, $weatherResult, $temperatureUnit, $windUnit);
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

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">Weather Search</h2>
            <p class="text-muted mb-0">Search any city and get live weather, forecast, saved locations, and alert matches.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="alerts.php" class="btn btn-outline-primary">Manage Alerts</a>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="card weather-side-card mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="search_weather">

                <div class="col-md-8">
                    <label for="city" class="form-label">City</label>
                    <input
                        type="text"
                        name="city"
                        id="city"
                        class="form-control form-control-lg"
                        placeholder="Enter a city name"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Search Weather</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($weatherResult): ?>
        <?php if (!empty($triggeredAlerts)): ?>
            <div class="mb-4">
                <?php foreach ($triggeredAlerts as $message): ?>
                    <div class="alert-box mb-2">
                        <strong>Alert:</strong> <?= e($message) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card weather-hero-card <?= e($weatherResult['theme_class']) ?> mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="weather-icon-lg"><?= e($weatherResult['icon']) ?></div>
                            <div>
                                <h3 class="fw-bold mb-1"><?= e($weatherResult['city']) ?><?= $weatherResult['country'] !== '' ? ', ' . e($weatherResult['country']) : '' ?></h3>
                                <p class="mb-0 fs-5"><?= e($weatherResult['condition']) ?></p>
                            </div>
                        </div>

                        <div class="display-4 fw-bold mb-2">
                            <?= e(formatTemperature($weatherResult['temperature_c'], $temperatureUnit)) ?>
                        </div>

                        <p class="mb-0 fs-5">
                            Feels like <?= e(formatTemperature($weatherResult['feels_like_c'], $temperatureUnit)) ?>
                        </p>
                    </div>

                    <div class="col-lg-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_location">
                            <input type="hidden" name="city" value="<?= e($weatherResult['city']) ?>">
                            <input type="hidden" name="country" value="<?= e($weatherResult['country']) ?>">
                            <input type="hidden" name="latitude" value="<?= e((string) $weatherResult['latitude']) ?>">
                            <input type="hidden" name="longitude" value="<?= e((string) $weatherResult['longitude']) ?>">

                            <button type="submit" class="btn btn-light btn-lg w-100 mb-3">Save This Location</button>
                        </form>

                        <div class="bg-white bg-opacity-25 rounded-4 p-3">
                            <div class="small text-uppercase fw-semibold mb-1">Weather Tip</div>
                            <div><?= e($weatherResult['tip']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card weather-stat-card h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Humidity</h6>
                        <div class="fs-3 fw-bold"><?= e((string) $weatherResult['humidity']) ?>%</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card weather-stat-card h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Wind Speed</h6>
                        <div class="fs-3 fw-bold"><?= e(formatWind($weatherResult['wind_kph'], $windUnit)) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card weather-stat-card h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Coordinates</h6>
                        <div class="fw-semibold"><?= e(number_format($weatherResult['latitude'], 4)) ?>, <?= e(number_format($weatherResult['longitude'], 4)) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card weather-side-card mb-4">
            <div class="card-body">
                <h4 class="fw-bold mb-4">5-Day Forecast</h4>

                <div class="row g-3">
                    <?php foreach ($weatherResult['forecast'] as $day): ?>
                        <div class="col-md-6 col-lg">
                            <div class="card forecast-card h-100">
                                <div class="card-body text-center">
                                    <div class="forecast-icon mb-2"><?= e($day['icon']) ?></div>
                                    <h6 class="fw-bold"><?= e(date('D, M j', strtotime($day['date']))) ?></h6>
                                    <div class="text-muted mb-2"><?= e($day['condition']) ?></div>
                                    <div class="fw-semibold">
                                        <?= e(formatTemperature($day['temp_max_c'], $temperatureUnit)) ?>
                                    </div>
                                    <small class="text-muted">
                                        Low: <?= e(formatTemperature($day['temp_min_c'], $temperatureUnit)) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card weather-side-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Saved Locations</h4>
                        <span class="badge bg-primary"><?= count($savedLocations) ?></span>
                    </div>

                    <?php if (!empty($savedLocations)): ?>
                        <?php foreach ($savedLocations as $location): ?>
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="action" value="search_weather">
                                <input type="hidden" name="city" value="<?= e($location['city_name']) ?>">
                                <button type="submit" class="search-chip border-0">
                                    <span>📍</span>
                                    <span><?= e($location['city_name']) ?><?= !empty($location['country']) ? ', ' . e($location['country']) : '' ?></span>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No saved locations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card weather-side-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Recent Searches</h4>
                        <span class="badge bg-secondary"><?= count($recentSearches) ?></span>
                    </div>

                    <?php if (!empty($recentSearches)): ?>
                        <?php foreach ($recentSearches as $history): ?>
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="action" value="search_weather">
                                <input type="hidden" name="city" value="<?= e($history['city_name']) ?>">
                                <button type="submit" class="search-chip border-0">
                                    <span>🔎</span>
                                    <span><?= e($history['city_name']) ?></span>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent searches yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>