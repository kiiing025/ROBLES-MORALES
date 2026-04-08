<?php
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';

$controller = new DashboardController($pdo);
$data = $controller->getDashboardData((int) $_SESSION['user']['user_id']);
?>
<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>
<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>
    <div class="dashboard-banner mb-4">
        <div>
            <span class="eyebrow text-primary">PROTECTED PAGE</span>
            <h2 class="fw-bold mb-1">Hello, <?= e($_SESSION['user']['full_name']) ?>.</h2>
            <p class="text-muted mb-0">This dashboard is only available after successful authentication.</p>
        </div>
        <a href="weather.php" class="btn btn-primary btn-lg">Open Weather Search</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel-card h-100">
                <h5 class="fw-bold mb-3">Account Information</h5>
                <p class="mb-2"><strong>Username:</strong> <?= e($_SESSION['user']['username']) ?></p>
                <p class="mb-0"><strong>Email:</strong> <?= e($_SESSION['user']['email']) ?></p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel-card h-100">
                <h5 class="fw-bold mb-3">User Preferences</h5>
                <?php if ($data['preferences']): ?>
                    <p class="mb-2"><strong>Temperature Unit:</strong> <?= e($data['preferences']['temperature_unit']) ?></p>
                    <p class="mb-2"><strong>Wind Unit:</strong> <?= e($data['preferences']['wind_unit']) ?></p>
                    <p class="mb-0"><strong>Theme Mode:</strong> <?= e($data['preferences']['theme_mode']) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0">No preferences found.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel-card h-100">
                <h5 class="fw-bold mb-3">Saved Locations</h5>
                <?php if (!empty($data['saved_locations'])): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($data['saved_locations'] as $location): ?>
                            <li class="list-group-item px-0"><?= e($location['city_name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No saved locations yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Recent Search History</h5>
                    <small class="text-muted">Latest 10 records</small>
                </div>
                <?php if (!empty($data['search_history'])): ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>City</th>
                                    <th>Searched At</th>
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
<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>
