<?php

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Alert.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userId = (int) $_SESSION['user']['user_id'];
$alertModel = new Alert($pdo);

$allowedTypes = ['rain', 'temperature', 'wind'];

function getAlertMeta(string $type): array
{
    return match ($type) {
        'rain' => [
            'icon' => 'cloud-rain',
            'title' => 'Rain Alert',
            'class' => 'border-primary bg-primary-subtle',
            'description' => 'Triggers when rain-related conditions are detected.'
        ],
        'temperature' => [
            'icon' => 'thermometer',
            'title' => 'Temperature Alert',
            'class' => 'border-warning bg-warning-subtle',
            'description' => 'Triggers when the temperature reaches your selected threshold.'
        ],
        'wind' => [
            'icon' => 'wind',
            'title' => 'Wind Alert',
            'class' => 'border-info bg-info-subtle',
            'description' => 'Triggers when wind speed reaches your selected threshold.'
        ],
        default => [
            'icon' => 'triangle-alert',
            'title' => 'Weather Alert',
            'class' => 'border-secondary bg-light',
            'description' => 'General weather alert.'
        ],
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'create_alert') {
        $city = trim($_POST['city'] ?? '');
        $conditionType = $_POST['condition_type'] ?? '';
        $thresholdValue = trim($_POST['threshold_value'] ?? '');
        $emailEnabled = isset($_POST['email_enabled']) && $_POST['email_enabled'] === '1';

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
                $thresholdValue !== null ? (float) $thresholdValue : null,
                $emailEnabled
            );

            setFlash('success', 'Alert added successfully.');
            redirect('alerts.php');
        }

        setFlash('danger', implode(' ', $errors));
        redirect('alerts.php');
    }

    if ($action === 'delete_alert') {
        $alertId = (int) ($_POST['alert_id'] ?? 0);

        if ($alertId > 0) {
            $alertModel->delete($alertId, $userId);
            setFlash('success', 'Alert deleted successfully.');
        } else {
            setFlash('danger', 'Invalid alert selected.');
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

    <section class="premium-hero mb-4">
        <div class="premium-hero-content">
            <div>
                <span class="section-kicker">Monitoring</span>
                <h1 class="premium-title mb-2">Manage Weather Alerts</h1>
                <p class="premium-subtitle mb-0">Create personalized alerts for rain, temperature, and wind conditions in your important cities.</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="weather.php" class="btn btn-primary premium-action-btn">Open Weather</a>
                <a href="dashboard.php" class="btn btn-outline-secondary premium-action-btn">Back to Dashboard</a>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card premium-panel shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <span class="section-kicker">Create</span>
                    <h5 class="fw-bold mb-3">Add New Alert</h5>

                    <form method="POST">
                        <?= csrfField() ?>
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

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="email_enabled" name="email_enabled" value="1">
                            <label class="form-check-label" for="email_enabled">Send email when triggered</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 premium-action-btn">Save Alert</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card premium-panel shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="section-kicker">Active Alerts</span>
                            <h5 class="fw-bold mb-0">Your Alerts</h5>
                        </div>
                        <span class="badge bg-primary"><?= count($alerts) ?></span>
                    </div>

                    <?php if (!empty($alerts)): ?>
                        <div class="row g-3">
                            <?php foreach ($alerts as $alert): ?>
                                <?php $meta = getAlertMeta($alert['condition_type']); ?>
                                <div class="col-md-6">
                                    <div class="card h-100 rounded-4 border-2 <?= e($meta['class']) ?> alert-card-active">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="icon-lg"><i data-lucide="<?= e($meta['icon']) ?>"></i></div>

                                                <div class="d-flex gap-2 align-items-center">
                                                    <?php if ((int) $alert['is_triggered'] === 1): ?>
                                                        <span class="badge text-bg-danger">TRIGGERED</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-light">WAITING</span>
                                                    <?php endif; ?>

                                                    <form method="POST">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete_alert">
                                                        <input type="hidden" name="alert_id" value="<?= (int) $alert['alert_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>

                                            <h6 class="fw-bold mb-1"><?= e($meta['title']) ?></h6>
                                            <p class="mb-1"><?= e($alert['city']) ?></p>

                                            <?php if (!empty($alert['threshold_value'])): ?>
                                                <p class="mb-2 text-muted">Threshold: <?= e((string) $alert['threshold_value']) ?></p>
                                            <?php else: ?>
                                                <p class="mb-2 text-muted"><?= e($meta['description']) ?></p>
                                            <?php endif; ?>

                                            <small class="text-muted d-block">
                                                Email notifications: <?= (int) $alert['email_enabled'] === 1 ? 'Enabled' : 'Disabled' ?>
                                            </small>

                                            <?php if (!empty($alert['last_email_sent_at'])): ?>
                                                <small class="text-muted d-block">Last email sent: <?= e($alert['last_email_sent_at']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted d-block">Last email sent: Not yet</small>
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