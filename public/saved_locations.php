<?php

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/SavedLocation.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$userId = (int) $_SESSION['user']['user_id'];
$savedLocationModel = new SavedLocation($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'delete_location') {
        $locationId = (int) ($_POST['location_id'] ?? 0);

        if ($locationId <= 0) {
            setFlash('danger', 'Invalid saved location selected.');
            redirect('saved_locations.php');
        }

        $location = $savedLocationModel->findById($locationId, $userId);

        if (!$location) {
            setFlash('danger', 'Saved location not found.');
            redirect('saved_locations.php');
        }

        $savedLocationModel->delete($locationId, $userId);
        setFlash('success', 'Saved location deleted successfully.');
        redirect('saved_locations.php');
    }

    if ($action === 'set_default_location') {
        $locationId = (int) ($_POST['location_id'] ?? 0);

        if ($locationId <= 0) {
            setFlash('danger', 'Invalid location selected.');
            redirect('saved_locations.php');
        }

        $location = $savedLocationModel->findById($locationId, $userId);

        if (!$location) {
            setFlash('danger', 'Saved location not found.');
            redirect('saved_locations.php');
        }

        $savedLocationModel->setDefault($locationId, $userId);
        setFlash('success', 'Default location updated successfully.');
        redirect('saved_locations.php');
    }
}

$savedLocations = $savedLocationModel->allByUser($userId);
$defaultLocation = $savedLocationModel->getDefaultByUser($userId);

?>

<?php require_once __DIR__ . '/../resources/views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../resources/views/layouts/navbar.php'; ?>

<div class="container py-5">
    <?php require __DIR__ . '/../resources/views/partials/alerts.php'; ?>

    <section class="premium-hero mb-4 saved-premium-hero">
        <div class="premium-hero-content">
            <div>
                <span class="section-kicker">Saved Places</span>
                <h1 class="premium-title mb-2">Manage Saved Locations</h1>
                <p class="premium-subtitle mb-0">Organize your saved cities, choose a default location, and quickly open live weather details.</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="weather.php" class="btn btn-primary premium-action-btn">Search Weather</a>
                <a href="dashboard.php" class="btn btn-outline-secondary premium-action-btn">Back to Dashboard</a>
            </div>
        </div>
    </section>

    <?php if ($defaultLocation): ?>
        <div class="card premium-default-card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="section-kicker">Current Default</span>
                    <h5 class="fw-bold mb-1">
                        <?= e($defaultLocation['city_name']) ?>
                        <?php if (!empty($defaultLocation['country'])): ?>
                            , <?= e($defaultLocation['country']) ?>
                        <?php endif; ?>
                    </h5>
                    <p class="text-muted mb-0">This location is prioritized for quick access across your account.</p>
                </div>

                <form method="POST" action="weather.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="search_weather">
                    <input type="hidden" name="city" value="<?= e($defaultLocation['city_name']) ?>">
                    <button type="submit" class="btn btn-outline-primary premium-action-btn">View Default Weather</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card premium-panel shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <span class="section-kicker">Library</span>
                    <h5 class="fw-bold mb-1">Your Saved Cities</h5>
                    <p class="text-muted mb-0">Set favorites, open current weather, or remove locations you no longer need.</p>
                </div>

                <span class="badge bg-primary"><?= count($savedLocations) ?></span>
            </div>

            <?php if (!empty($savedLocations)): ?>
                <div class="row g-4">
                    <?php foreach ($savedLocations as $location): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card premium-location-card h-100 rounded-4 border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="premium-location-icon">
                                            <i data-lucide="map-pin"></i>
                                        </div>

                                        <?php if ((int) $location['is_default'] === 1): ?>
                                            <span class="badge text-bg-primary">DEFAULT</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-light">SAVED</span>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="fw-bold mb-1"><?= e($location['city_name']) ?></h5>

                                    <?php if (!empty($location['country'])): ?>
                                        <p class="text-muted mb-2"><?= e($location['country']) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted mb-2">No country set</p>
                                    <?php endif; ?>

                                    <?php if (!empty($location['latitude']) && !empty($location['longitude'])): ?>
                                        <div class="premium-location-meta mb-3">
                                            <span>Coordinates</span>
                                            <strong>
                                                <?= e(number_format((float) $location['latitude'], 4)) ?>,
                                                <?= e(number_format((float) $location['longitude'], 4)) ?>
                                            </strong>
                                        </div>
                                    <?php else: ?>
                                        <div class="premium-location-meta mb-3">
                                            <span>Coordinates</span>
                                            <strong>Not available</strong>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-grid gap-2">
                                        <form method="POST" action="weather.php">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="search_weather">
                                            <input type="hidden" name="city" value="<?= e($location['city_name']) ?>">
                                            <button type="submit" class="btn btn-primary premium-action-btn w-100">View Weather</button>
                                        </form>

                                        <?php if ((int) $location['is_default'] !== 1): ?>
                                            <form method="POST" action="saved_locations.php">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="set_default_location">
                                                <input type="hidden" name="location_id" value="<?= (int) $location['location_id'] ?>">
                                                <button type="submit" class="btn btn-outline-primary premium-action-btn w-100">Set as Default</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="saved_locations.php">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_location">
                                            <input type="hidden" name="location_id" value="<?= (int) $location['location_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger premium-action-btn w-100">Delete</button>
                                        </form>
                                    </div>

                                    <small class="text-muted d-block mt-3">
                                        Saved: <?= e($location['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="icon-lg mb-3"><i data-lucide="map-pin-off"></i></div>
                    <span class="section-kicker">Empty State</span>
                    <h5 class="fw-bold mb-2">No saved locations yet</h5>
                    <p class="text-muted mb-3">Search for a city in the weather page and save it here for quick access.</p>
                    <a href="weather.php" class="btn btn-primary premium-action-btn">Go to Weather Search</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../resources/views/layouts/footer.php'; ?>