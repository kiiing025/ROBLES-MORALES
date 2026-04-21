<?php

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Alert.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userId = (int) $_SESSION['user']['user_id'];
$alertModel = new Alert($pdo);

$allowedTypes = ['rain', 'temperature', 'wind'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_alert') {
        $city = trim($_POST['city'] ?? '');
        $conditionType = $_POST['condition_type'] ?? '';
        $thresholdValue = trim($_POST['threshold_value'] ?? '');

        $errors = [];

        if ($city === '') {
            $errors[] = 'City is required.';
        }

        if (!in_array($conditionType, $allowedTypes, true)) {
            $errors[] = 'Invalid alert type.';
        }

        if ($conditionType === 'temperature' || $conditionType === 'wind') {
            if ($thresholdValue === '' || !is_numeric($thresholdValue)) {
                $errors[] = 'Threshold value is required for temperature and wind alerts.';
            }
        } else {
            $thresholdValue = null;
        }

        if (empty($errors)) {
            $alertModel->create(
                $userId,
                $city,
                $conditionType,
                $thresholdValue !== null ? (float) $thresholdValue : null
            );

            setFlash('success', 'Alert added successfully.');
            redirect('alerts.php');
        }

        setFlash('error', implode(' ', $errors));
        redirect('alerts.php');
    }

    if ($action === 'delete_alert') {
        $alertId = (int) ($_POST['alert_id'] ?? 0);

        if ($alertId > 0) {
            $alertModel->delete($alertId, $userId);
            setFlash('success', 'Alert deleted successfully.');
        } else {
            setFlash('error', 'Invalid alert selected.');
        }

        redirect('alerts.php');
    }
}

$alerts = $alertModel->getUserAlerts($userId);

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Weather Alerts</h2>
            <p class="text-muted mb-0">Create personalized alerts for your important locations.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            <a href="weather.php" class="btn btn-primary">Open Weather Page</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="mb-3">Add New Alert</h5>

                    <form method="POST">
                        <input type="hidden" name="action" value="create_alert">

                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input
                                type="text"
                                class="form-control"
                                id="city"
                                name="city"
                                placeholder="Enter city name"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="condition_type" class="form-label">Alert Type</label>
                            <select class="form-select" id="condition_type" name="condition_type" required>
                                <option value="rain">Rain</option>
                                <option value="temperature">Temperature</option>
                                <option value="wind">Wind</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="threshold_value" class="form-label">Threshold Value</label>
                            <input
                                type="number"
                                step="0.1"
                                class="form-control"
                                id="threshold_value"
                                name="threshold_value"
                                placeholder="Required for temperature and wind alerts"
                            >
                            <small class="text-muted">Leave blank for rain alerts.</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Save Alert</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Your Active Alerts</h5>
                        <span class="badge bg-primary"><?= count($alerts) ?></span>
                    </div>

                    <?php if (!empty($alerts)): ?>
                        <div class="row g-3">
                            <?php foreach ($alerts as $alert): ?>
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100 bg-light">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1"><?= e(ucfirst($alert['condition_type'])) ?> Alert</h6>
                                                <p class="mb-1 text-dark"><?= e($alert['city']) ?></p>

                                                <?php if (!empty($alert['threshold_value'])): ?>
                                                    <small class="text-muted">Threshold: <?= e($alert['threshold_value']) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">No threshold value set</small>
                                                <?php endif; ?>
                                            </div>

                                            <form method="POST" class="ms-2">
                                                <input type="hidden" name="action" value="delete_alert">
                                                <input type="hidden" name="alert_id" value="<?= (int) $alert['alert_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>

                                        <small class="text-muted d-block mt-2">
                                            Created: <?= e($alert['created_at']) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <h6 class="mb-2">No alerts yet</h6>
                            <p class="text-muted mb-0">Create your first weather alert using the form on the left.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>