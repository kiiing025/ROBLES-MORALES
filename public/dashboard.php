<?php

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/models/UserPreference.php';
require_once __DIR__ . '/../app/models/Alert.php';
require_once __DIR__ . '/../app/models/SavedLocation.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userId = (int) $_SESSION['user']['user_id'];

$preferenceModel = new UserPreference($pdo);
$alertModel = new Alert($pdo);
$savedLocationModel = new SavedLocation($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_preferences') {
    verifyCsrf();

    $temperatureUnit = in_array($_POST['temperature_unit'] ?? 'C', ['C', 'F'], true) ? $_POST['temperature_unit'] : 'C';
    $windUnit = in_array($_POST['wind_unit'] ?? 'kph', ['kph', 'mph'], true) ? $_POST['wind_unit'] : 'kph';
    $themeMode = in_array($_POST['theme_mode'] ?? 'light', ['light', 'dark'], true) ? $_POST['theme_mode'] : 'light';

    $preferenceModel->updateByUser($userId, $temperatureUnit, $windUnit, $themeMode);
    setThemeMode($themeMode);

    setFlash('success', 'Preferences updated successfully.');
    redirect('dashboard.php');
}

function getAlertMeta(string $type): array
{
    return match ($type) {
        'rain' => [
            'icon' => 'cloud-rain',
            'title' => 'Rain Alert',
            'class' => 'border-primary bg-primary-subtle'
        ],
        'temperature' => [
            'icon' => 'thermometer',
            'title' => 'Temperature Alert',
            'class' => 'border-warning bg-warning-subtle'
        ],
        'wind' => [
            'icon' => 'wind',
            'title' => 'Wind Alert',
            'class' => 'border-info bg-info-subtle'
        ],
        default => [
            'icon' => 'triangle-alert',
            'title' => 'Weather Alert',
            'class' => 'border-secondary bg-light'
        ],
    };
}

$controller = new DashboardController($pdo);
$data = $controller->getDashboardData($userId);
$alerts = $alertModel->getUserAlerts($userId);

$preferences = $data['preferences'] ?? null;
$savedLocations = $data['saved_locations'] ?? [];
$searchHistory = $data['search_history'] ?? [];
$defaultLocation = $savedLocationModel->getDefaultByUser($userId);

$temperatureUnitLabel = ($preferences['temperature_unit'] ?? 'C') === 'F' ? 'Fahrenheit' : 'Celsius';
$windUnitLabel = ($preferences['wind_unit'] ?? 'kph') === 'mph' ? 'Miles per hour' : 'Kilometers per hour';
$themeModeLabel = ucfirst($preferences['theme_mode'] ?? 'light');

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <section class="premium-hero mb-4">
        <div class="premium-hero-content">
            <div>
                <span class="section-kicker">Overview</span>
                <h1 class="premium-title mb-2">Welcome back, <?= e($_SESSION['user']['full_name']) ?></h1>
                <p class="premium-subtitle mb-0">Manage your weather preferences, alerts, saved locations, and recent activity in one place.</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="weather.php" class="btn btn-primary premium-action-btn">Open Weather</a>
                <a href="alerts.php" class="btn btn-outline-primary premium-action-btn">Manage Alerts</a>
            </div>
        </div>
    </section>

    <?php if ($defaultLocation): ?>
        <div class="card premium-default-card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="section-kicker">Default Location</span>
                    <h5 class="fw-bold mb-1">
                        <?= e($defaultLocation['city_name']) ?>
                        <?php if (!empty($defaultLocation['country'])): ?>
                            , <?= e($defaultLocation['country']) ?>
                        <?php endif; ?>
                    </h5>
                    <p class="text-muted mb-0">Your preferred city for quick weather access.</p>
                </div>

                <form method="POST" action="weather.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="search_weather">
                    <input type="hidden" name="city" value="<?= e($defaultLocation['city_name']) ?>">
                    <button type="submit" class="btn btn-outline-primary premium-action-btn">View Weather</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card premium-stat-card h-100">
                <div class="card-body">
                    <div class="premium-stat-icon icon-md"><i data-lucide="map-pin"></i></div>
                    <p class="text-muted mb-2">Saved Locations</p>
                    <div class="dashboard-stat-number"><?= count($savedLocations) ?></div>
                    <small class="text-muted">Quick-access cities</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card premium-stat-card h-100">
                <div class="card-body">
                    <div class="premium-stat-icon icon-md"><i data-lucide="search"></i></div>
                    <p class="text-muted mb-2">Recent Searches</p>
                    <div class="dashboard-stat-number"><?= count($searchHistory) ?></div>
                    <small class="text-muted">Weather lookups made</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card premium-stat-card h-100">
                <div class="card-body">
                    <div class="premium-stat-icon icon-md"><i data-lucide="bell-ring"></i></div>
                    <p class="text-muted mb-2">Active Alerts</p>
                    <div class="dashboard-stat-number"><?= count($alerts) ?></div>
                    <small class="text-muted">Monitoring conditions</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-stat-card premium-stat-card h-100">
                <div class="card-body">
                    <div class="premium-stat-icon icon-md"><i data-lucide="palette"></i></div>
                    <p class="text-muted mb-2">Theme Mode</p>
                    <div class="dashboard-stat-number fs-3"><?= e($themeModeLabel) ?></div>
                    <small class="text-muted">Current UI appearance</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card premium-panel shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <span class="section-kicker">Account</span>
                    <h5 class="fw-bold mb-3">User Information</h5>

                    <div class="premium-info-list">
                        <div class="premium-info-item">
                            <span class="premium-info-label">Full Name</span>
                            <span class="premium-info-value"><?= e($_SESSION['user']['full_name']) ?></span>
                        </div>
                        <div class="premium-info-item">
                            <span class="premium-info-label">Username</span>
                            <span class="premium-info-value"><?= e($_SESSION['user']['username']) ?></span>
                        </div>
                        <div class="premium-info-item">
                            <span class="premium-info-label">Email</span>
                            <span class="premium-info-value"><?= e($_SESSION['user']['email']) ?></span>
                        </div>
                        <div class="premium-info-item">
                            <span class="premium-info-label">Role</span>
                            <span class="premium-info-value"><?= e($_SESSION['user']['role'] ?? 'user') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card premium-panel shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <span class="section-kicker">Preferences</span>
                    <h5 class="fw-bold mb-3">Customize Experience</h5>

                    <?php if ($preferences): ?>
                        <div class="premium-mini-summary mb-3">
                            <div><strong>Temperature:</strong> <?= e($temperatureUnitLabel) ?></div>
                            <div><strong>Wind:</strong> <?= e($windUnitLabel) ?></div>
                            <div><strong>Theme:</strong> <?= e($themeModeLabel) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_preferences">

                        <div class="mb-3">
                            <label class="form-label">Temperature Unit</label>
                            <select name="temperature_unit" class="form-select">
                                <option value="C" <?= ($preferences['temperature_unit'] ?? 'C') === 'C' ? 'selected' : '' ?>>Celsius</option>
                                <option value="F" <?= ($preferences['temperature_unit'] ?? 'C') === 'F' ? 'selected' : '' ?>>Fahrenheit</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Wind Unit</label>
                            <select name="wind_unit" class="form-select">
                                <option value="kph" <?= ($preferences['wind_unit'] ?? 'kph') === 'kph' ? 'selected' : '' ?>>kph</option>
                                <option value="mph" <?= ($preferences['wind_unit'] ?? 'kph') === 'mph' ? 'selected' : '' ?>>mph</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Theme Mode</label>
                            <select name="theme_mode" class="form-select">
                                <option value="light" <?= ($preferences['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option>
                                <option value="dark" <?= ($preferences['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 premium-action-btn">Update Preferences</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card premium-panel shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="section-kicker">Saved Places</span>
                            <h5 class="fw-bold mb-0">Locations</h5>
                        </div>
                        <span class="badge bg-primary"><?= count($savedLocations) ?></span>
                    </div>

                    <?php if (!empty($savedLocations)): ?>
                        <ul class="custom-list">
                            <?php foreach ($savedLocations as $location): ?>
                                <li>
                                    <strong><?= e($location['city_name']) ?></strong>
                                    <?php if (!empty($location['country'])): ?>
                                        <span class="text-muted"> - <?= e($location['country']) ?></span>
                                    <?php endif; ?>
                                    <?php if ((int) ($location['is_default'] ?? 0) === 1): ?>
                                        <span class="badge text-bg-primary ms-2">Default</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No saved locations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card premium-panel shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <span class="section-kicker">Monitoring</span>
                            <h5 class="fw-bold mb-1">Active Weather Alerts</h5>
                            <p class="text-muted mb-0">Your personalized monitoring conditions.</p>
                        </div>

                        <a href="alerts.php" class="btn btn-sm btn-outline-primary premium-action-btn">Add New Alert</a>
                    </div>

                    <?php if (!empty($alerts)): ?>
                        <div class="row g-3">
                            <?php foreach ($alerts as $alert): ?>
                                <?php $meta = getAlertMeta($alert['condition_type']); ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="card h-100 rounded-4 border-2 <?= e($meta['class']) ?> alert-card-active premium-alert-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="icon-lg"><i data-lucide="<?= e($meta['icon']) ?>"></i></div>

                                                <?php if ((int) $alert['is_triggered'] === 1): ?>
                                                    <span class="badge text-bg-danger">TRIGGERED</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-light">WAITING</span>
                                                <?php endif; ?>
                                            </div>

                                            <h6 class="fw-bold mb-1"><?= e($meta['title']) ?></h6>
                                            <p class="mb-2"><?= e($alert['city']) ?></p>

                                            <?php if ($alert['condition_type'] === 'rain'): ?>
                                                <p class="mb-2 text-muted">This alert is triggered when rain-related conditions are detected.</p>
                                            <?php elseif ($alert['condition_type'] === 'temperature'): ?>
                                                <p class="mb-2 text-muted">
                                                    Trigger when temperature reaches or exceeds
                                                    <strong><?= e((string) $alert['threshold_value']) ?></strong>.
                                                </p>
                                            <?php elseif ($alert['condition_type'] === 'wind'): ?>
                                                <p class="mb-2 text-muted">
                                                    Trigger when wind speed reaches or exceeds
                                                    <strong><?= e((string) $alert['threshold_value']) ?></strong>.
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($alert['last_triggered_at'])): ?>
                                                <small class="text-muted d-block">Last triggered: <?= e($alert['last_triggered_at']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted d-block">Last triggered: Not yet</small>
                                            <?php endif; ?>

                                            <small class="text-muted d-block mt-1">Created: <?= e($alert['created_at']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="icon-lg mb-2"><i data-lucide="bell-off"></i></div>
                            <h6 class="mb-2">No active alerts yet</h6>
                            <p class="text-muted mb-3">Create your first personalized weather alert to monitor important conditions.</p>
                            <a href="alerts.php" class="btn btn-primary premium-action-btn">Create Alert</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card premium-panel shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="section-kicker">Activity</span>
                            <h5 class="fw-bold mb-0">Recent Search History</h5>
                        </div>
                        <span class="badge bg-secondary"><?= count($searchHistory) ?></span>
                    </div>

                    <?php if (!empty($searchHistory)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0 premium-table">
                                <thead>
                                    <tr>
                                        <th>City</th>
                                        <th>Date Searched</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($searchHistory as $history): ?>
                                        <tr>
                                            <td><?= e($history['city_name']) ?></td>
                                            <td><?= e($history['searched_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No search history yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>