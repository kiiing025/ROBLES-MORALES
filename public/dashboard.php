<?php

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/models/UserPreference.php';
require_once __DIR__ . '/../app/models/Alert.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userId = (int) $_SESSION['user']['user_id'];

$preferenceModel = new UserPreference($pdo);
$alertModel = new Alert($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_preferences') {
    $temperatureUnit = in_array($_POST['temperature_unit'] ?? 'C', ['C', 'F'], true) ? $_POST['temperature_unit'] : 'C';
    $windUnit = in_array($_POST['wind_unit'] ?? 'kph', ['kph', 'mph'], true) ? $_POST['wind_unit'] : 'kph';
    $themeMode = in_array($_POST['theme_mode'] ?? 'light', ['light', 'dark'], true) ? $_POST['theme_mode'] : 'light';

    $preferenceModel->updateByUser($userId, $temperatureUnit, $windUnit, $themeMode);

    setFlash('success', 'Preferences updated successfully.');
    redirect('dashboard.php');
}

$controller = new DashboardController($pdo);
$data = $controller->getDashboardData($userId);
$alerts = $alertModel->getUserAlerts($userId);

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Dashboard</h2>
            <p class="text-muted mb-0">Welcome, <?= e($_SESSION['user']['full_name']) ?>.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="alerts.php" class="btn btn-outline-primary">Manage Alerts</a>
            <a href="weather.php" class="btn btn-primary">Open Weather Page</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <h5>Account Info</h5>
                    <p class="mb-1"><strong>Username:</strong> <?= e($_SESSION['user']['username']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= e($_SESSION['user']['email']) ?></p>
                    <p class="mb-0"><strong>Role:</strong> <?= e($_SESSION['user']['role'] ?? 'user') ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <h5>Preferences</h5>

                    <?php $preferences = $data['preferences']; ?>

                    <?php if ($preferences): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="update_preferences">

                            <div class="mb-3">
                                <label class="form-label">Temperature Unit</label>
                                <select name="temperature_unit" class="form-select">
                                    <option value="C" <?= $preferences['temperature_unit'] === 'C' ? 'selected' : '' ?>>Celsius</option>
                                    <option value="F" <?= $preferences['temperature_unit'] === 'F' ? 'selected' : '' ?>>Fahrenheit</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Wind Unit</label>
                                <select name="wind_unit" class="form-select">
                                    <option value="kph" <?= $preferences['wind_unit'] === 'kph' ? 'selected' : '' ?>>kph</option>
                                    <option value="mph" <?= $preferences['wind_unit'] === 'mph' ? 'selected' : '' ?>>mph</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Theme Mode</label>
                                <select name="theme_mode" class="form-select">
                                    <option value="light" <?= $preferences['theme_mode'] === 'light' ? 'selected' : '' ?>>Light</option>
                                    <option value="dark" <?= $preferences['theme_mode'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Update Preferences</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">No preferences found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <h5>Saved Locations</h5>

                    <?php if (!empty($data['saved_locations'])): ?>
                        <ul class="mb-0">
                            <?php foreach ($data['saved_locations'] as $location): ?>
                                <li><?= e($location['city_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No saved locations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Active Alerts</h5>
                        <a href="alerts.php" class="btn btn-sm btn-outline-primary">Add Alert</a>
                    </div>

                    <?php if (!empty($alerts)): ?>
                        <div class="row g-3">
                            <?php foreach ($alerts as $alert): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <p class="mb-1 fw-semibold"><?= e(ucfirst($alert['condition_type'])) ?> Alert</p>
                                        <p class="mb-1 text-dark"><?= e($alert['city']) ?></p>

                                        <?php if (!empty($alert['threshold_value'])): ?>
                                            <small class="text-muted">Threshold: <?= e($alert['threshold_value']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">No threshold value set</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No active alerts yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5>Recent Search History</h5>

                    <?php if (!empty($data['search_history'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>City</th>
                                        <th>Date Searched</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['search_history'] as $history): ?>
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