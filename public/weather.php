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

if (!$preferences) {
    $preferenceModel->createDefault($userId);
    $preferences = $preferenceModel->findByUser($userId);
}

$temperatureUnit = $preferences['temperature_unit'] ?? 'C';
$windUnit = $preferences['wind_unit'] ?? 'kph';
$defaultLocation = $savedLocationModel->getDefaultByUser($userId);

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

function getWeatherIconName(string $condition): string
{
    $condition = strtolower($condition);

    return match (true) {
        str_contains($condition, 'clear') => 'sun',
        str_contains($condition, 'partly') => 'cloud-sun',
        str_contains($condition, 'cloud') => 'cloud',
        str_contains($condition, 'rain') => 'cloud-rain',
        str_contains($condition, 'drizzle') => 'cloud-drizzle',
        str_contains($condition, 'storm') => 'cloud-lightning',
        str_contains($condition, 'snow') => 'cloud-snow',
        str_contains($condition, 'fog') => 'cloud-fog',
        default => 'cloud-sun',
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
        return 'Avoid outdoor activities if possible and keep your devices charged.';
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
        $condition = getWeatherCodeLabel((int) $daily['weather_code'][$i]);

        $forecast[] = [
            'date' => $daily['time'][$i],
            'condition' => $condition,
            'icon' => getWeatherIconName($condition),
            'temp_max_c' => (float) $daily['temperature_2m_max'][$i],
            'temp_min_c' => (float) $daily['temperature_2m_min'][$i],
        ];
    }

    $currentCondition = getWeatherCodeLabel((int) $current['weather_code']);

    return [
        'city' => $place['name'],
        'country' => $place['country'] ?? '',
        'latitude' => $latitude,
        'longitude' => $longitude,
        'condition' => $currentCondition,
        'icon' => getWeatherIconName($currentCondition),
        'temperature_c' => (float) $current['temperature_2m'],
        'feels_like_c' => (float) $current['apparent_temperature'],
        'humidity' => (int) $current['relative_humidity_2m'],
        'wind_kph' => (float) $current['wind_speed_10m'],
        'tip' => getWeatherTip($currentCondition, (float) $current['temperature_2m']),
        'theme_class' => getThemeClass($currentCondition),
        'forecast' => $forecast,
    ];
}

function getAlertMeta(string $type): array
{
    return match ($type) {
        'rain' => [
            'icon' => 'cloud-rain',
            'title' => 'Rain Alert',
            'class' => 'border-primary bg-primary-subtle',
            'badge' => 'text-bg-primary'
        ],
        'temperature' => [
            'icon' => 'thermometer',
            'title' => 'Temperature Alert',
            'class' => 'border-warning bg-warning-subtle',
            'badge' => 'text-bg-warning'
        ],
        'wind' => [
            'icon' => 'wind',
            'title' => 'Wind Alert',
            'class' => 'border-info bg-info-subtle',
            'badge' => 'text-bg-info'
        ],
        default => [
            'icon' => 'triangle-alert',
            'title' => 'Weather Alert',
            'class' => 'border-secondary bg-light',
            'badge' => 'text-bg-secondary'
        ],
    };
}

function evaluateAlertTriggered(array $alert, array $weatherResult, string $temperatureUnit, string $windUnit): bool
{
    $type = $alert['condition_type'];
    $threshold = $alert['threshold_value'] !== null ? (float) $alert['threshold_value'] : null;

    if ($type === 'rain') {
        $condition = strtolower($weatherResult['condition']);

        return str_contains($condition, 'rain')
            || str_contains($condition, 'drizzle')
            || str_contains($condition, 'storm');
    }

    if ($type === 'temperature' && $threshold !== null) {
        $currentTemp = convertTemperature($weatherResult['temperature_c'], $temperatureUnit);
        return $currentTemp >= $threshold;
    }

    if ($type === 'wind' && $threshold !== null) {
        $currentWind = convertWind($weatherResult['wind_kph'], $windUnit);
        return $currentWind >= $threshold;
    }

    return false;
}

function buildTriggeredAlertMessage(array $alert, array $weatherResult, string $temperatureUnit, string $windUnit): string
{
    if ($alert['condition_type'] === 'rain') {
        return 'Rain-related conditions were detected in ' . $weatherResult['city'] . '.';
    }

    if ($alert['condition_type'] === 'temperature') {
        $currentTemp = convertTemperature($weatherResult['temperature_c'], $temperatureUnit);
        return 'Current temperature in ' . $weatherResult['city'] . ' is ' . number_format($currentTemp, 1) . '°' . $temperatureUnit . ', which reached your threshold.';
    }

    if ($alert['condition_type'] === 'wind') {
        $currentWind = convertWind($weatherResult['wind_kph'], $windUnit);
        return 'Current wind speed in ' . $weatherResult['city'] . ' is ' . number_format($currentWind, 1) . ' ' . $windUnit . ', which reached your threshold.';
    }

    return 'Weather alert triggered.';
}

function sendAlertEmail(string $toEmail, string $city, string $alertTitle, string $message): bool
{
    $subject = 'WeatherHub Alert: ' . $alertTitle . ' in ' . $city;

    $body = "Hello,\n\n" .
        "Your WeatherHub alert has been triggered.\n\n" .
        "City: " . $city . "\n" .
        "Alert: " . $alertTitle . "\n" .
        "Details: " . $message . "\n\n" .
        "Please check WeatherHub for more details.\n\n" .
        "WeatherHub";

    $headers = "From: no-reply@weatherhub.local\r\n";
    $headers .= "Reply-To: no-reply@weatherhub.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $body, $headers);
}

function evaluatePersistAndEmailAlerts(
    Alert $alertModel,
    int $userId,
    string $userEmail,
    array $cityAlerts,
    array $weatherResult,
    string $temperatureUnit,
    string $windUnit
): array {
    $triggered = [];

    foreach ($cityAlerts as $alert) {
        $isTriggered = evaluateAlertTriggered($alert, $weatherResult, $temperatureUnit, $windUnit);
        $alertModel->updateTriggerStatus((int) $alert['alert_id'], $userId, $isTriggered);

        if ($isTriggered) {
            $meta = getAlertMeta($alert['condition_type']);
            $message = buildTriggeredAlertMessage($alert, $weatherResult, $temperatureUnit, $windUnit);

            $triggered[] = [
                'type' => $alert['condition_type'],
                'icon' => $meta['icon'],
                'title' => $meta['title'],
                'message' => $message,
                'class' => $meta['class'],
                'badge' => $meta['badge']
            ];

            if ((int) $alert['email_enabled'] === 1 && empty($alert['last_email_sent_at'])) {
                if (sendAlertEmail($userEmail, $weatherResult['city'], $meta['title'], $message)) {
                    $alertModel->markEmailSent((int) $alert['alert_id'], $userId);
                }
            }
        }
    }

    return $triggered;
}

$weatherResult = null;
$triggeredAlerts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

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

        $cityAlerts = $alertModel->getUserAlertsByCity($userId, $weatherResult['city']);
        $triggeredAlerts = evaluatePersistAndEmailAlerts(
            $alertModel,
            $userId,
            $_SESSION['user']['email'],
            $cityAlerts,
            $weatherResult,
            $temperatureUnit,
            $windUnit
        );
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

    <section class="premium-hero mb-4 weather-premium-hero">
        <div class="premium-hero-content">
            <div>
                <span class="section-kicker">Weather Center</span>
                <h1 class="premium-title mb-2">Live Weather and Forecast</h1>
                <p class="premium-subtitle mb-0">Search any city, monitor weather alerts, and quickly access your saved and default locations.</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="alerts.php" class="btn btn-outline-primary premium-action-btn">Manage Alerts</a>
                <a href="dashboard.php" class="btn btn-outline-secondary premium-action-btn">Back to Dashboard</a>
            </div>
        </div>
    </section>

    <?php if ($defaultLocation): ?>
        <div class="card premium-default-card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="section-kicker">Default Location</span>
                    <h5 class="fw-bold mb-1"><?= e($defaultLocation['city_name']) ?><?php if (!empty($defaultLocation['country'])): ?>, <?= e($defaultLocation['country']) ?><?php endif; ?></h5>
                    <p class="text-muted mb-0">Use your preferred city for quick weather access.</p>
                </div>

                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="search_weather">
                    <input type="hidden" name="city" value="<?= e($defaultLocation['city_name']) ?>">
                    <button type="submit" class="btn btn-outline-primary premium-action-btn">View Default Weather</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card premium-panel weather-search-card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <span class="section-kicker">Search</span>
                    <h5 class="fw-bold mb-0">Find Weather by City</h5>
                </div>
            </div>

            <form method="POST" class="row g-3 align-items-end">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="search_weather">

                <div class="col-md-9">
                    <label for="city" class="form-label">City Name</label>
                    <input
                        type="text"
                        name="city"
                        id="city"
                        class="form-control form-control-lg"
                        placeholder="Enter a city name"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100 premium-action-btn">Search Weather</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($weatherResult): ?>
        <?php if (!empty($triggeredAlerts)): ?>
            <div class="card premium-panel shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <span class="section-kicker">Alerts</span>
                            <h5 class="fw-bold mb-1">Triggered Alerts</h5>
                            <p class="text-muted mb-0">These alerts matched the current weather for <?= e($weatherResult['city']) ?>.</p>
                        </div>
                        <span class="badge text-bg-danger"><?= count($triggeredAlerts) ?> Triggered</span>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($triggeredAlerts as $item): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 rounded-4 border-2 <?= e($item['class']) ?> alert-card-active premium-alert-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="icon-lg"><i data-lucide="<?= e($item['icon']) ?>"></i></div>
                                            <span class="badge <?= e($item['badge']) ?>">TRIGGERED</span>
                                        </div>

                                        <h6 class="fw-bold mb-2"><?= e($item['title']) ?></h6>
                                        <p class="mb-0"><?= e($item['message']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card weather-hero-card premium-weather-hero <?= e($weatherResult['theme_class']) ?> mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="weather-hero-badge">Current Conditions</span>

                        <div class="d-flex align-items-center gap-3 mb-3 mt-3">
                            <div class="weather-icon-lg">
                                <i data-lucide="<?= e($weatherResult['icon']) ?>"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-1">
                                    <?= e($weatherResult['city']) ?><?= $weatherResult['country'] !== '' ? ', ' . e($weatherResult['country']) : '' ?>
                                </h3>
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
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_location">
                            <input type="hidden" name="city" value="<?= e($weatherResult['city']) ?>">
                            <input type="hidden" name="country" value="<?= e($weatherResult['country']) ?>">
                            <input type="hidden" name="latitude" value="<?= e((string) $weatherResult['latitude']) ?>">
                            <input type="hidden" name="longitude" value="<?= e((string) $weatherResult['longitude']) ?>">

                            <button type="submit" class="btn btn-light btn-lg w-100 mb-3 premium-action-btn">Save This Location</button>
                        </form>

                        <div class="glass-soft rounded-4 p-3 weather-tip-card">
                            <div class="small text-uppercase fw-semibold mb-1">Weather Tip</div>
                            <div><?= e($weatherResult['tip']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card weather-stat-card premium-metric-card h-100">
                    <div class="card-body">
                        <div class="premium-stat-icon icon-md mb-2"><i data-lucide="droplets"></i></div>
                        <h6 class="text-muted mb-2">Humidity</h6>
                        <div class="fs-3 fw-bold"><?= e((string) $weatherResult['humidity']) ?>%</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card weather-stat-card premium-metric-card h-100">
                    <div class="card-body">
                        <div class="premium-stat-icon icon-md mb-2"><i data-lucide="wind"></i></div>
                        <h6 class="text-muted mb-2">Wind Speed</h6>
                        <div class="fs-3 fw-bold"><?= e(formatWind($weatherResult['wind_kph'], $windUnit)) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card weather-stat-card premium-metric-card h-100">
                    <div class="card-body">
                        <div class="premium-stat-icon icon-md mb-2"><i data-lucide="map-pin"></i></div>
                        <h6 class="text-muted mb-2">Coordinates</h6>
                        <div class="fw-semibold"><?= e(number_format($weatherResult['latitude'], 4)) ?>, <?= e(number_format($weatherResult['longitude'], 4)) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card premium-panel shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <span class="section-kicker">Forecast</span>
                        <h4 class="fw-bold mb-0">5-Day Forecast</h4>
                    </div>
                </div>

                <div class="row g-3">
                    <?php foreach ($weatherResult['forecast'] as $day): ?>
                        <div class="col-md-6 col-lg">
                            <div class="card forecast-card premium-forecast-card h-100">
                                <div class="card-body text-center">
                                    <div class="forecast-icon mb-2">
                                        <i data-lucide="<?= e($day['icon']) ?>"></i>
                                    </div>
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
    <?php else: ?>
        <div class="card premium-panel weather-empty-card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body text-center py-5">
                <div class="weather-icon-lg mb-3">
                    <i data-lucide="cloud-sun"></i>
                </div>
                <span class="section-kicker">No Search Yet</span>
                <h4 class="fw-bold mb-2">Search for a city</h4>
                <p class="text-muted mb-0">View live weather, forecast details, and alert matches for your saved conditions.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card premium-panel weather-side-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="section-kicker">Quick Access</span>
                            <h4 class="fw-bold mb-0">Saved Locations</h4>
                        </div>
                        <span class="badge bg-primary"><?= count($savedLocations) ?></span>
                    </div>

                    <?php if (!empty($savedLocations)): ?>
                        <?php foreach ($savedLocations as $location): ?>
                            <form method="POST" class="mb-2">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="search_weather">
                                <input type="hidden" name="city" value="<?= e($location['city_name']) ?>">
                                <button type="submit" class="search-chip border-0 premium-search-chip">
                                    <span class="icon-sm"><i data-lucide="map-pin"></i></span>
                                    <span><?= e($location['city_name']) ?><?= !empty($location['country']) ? ', ' . e($location['country']) : '' ?></span>
                                    <?php if ((int) ($location['is_default'] ?? 0) === 1): ?>
                                        <span class="badge text-bg-primary ms-2">Default</span>
                                    <?php endif; ?>
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
            <div class="card premium-panel weather-side-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="section-kicker">Activity</span>
                            <h4 class="fw-bold mb-0">Recent Searches</h4>
                        </div>
                        <span class="badge bg-secondary"><?= count($recentSearches) ?></span>
                    </div>

                    <?php if (!empty($recentSearches)): ?>
                        <?php foreach ($recentSearches as $history): ?>
                            <form method="POST" class="mb-2">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="search_weather">
                                <input type="hidden" name="city" value="<?= e($history['city_name']) ?>">
                                <button type="submit" class="search-chip border-0 premium-search-chip">
                                    <span class="icon-sm"><i data-lucide="search"></i></span>
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