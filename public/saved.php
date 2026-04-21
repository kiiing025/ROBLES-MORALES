<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/WeatherController.php';
require_once __DIR__ . '/../app/models/Location.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city'])) {
    WeatherController::saveLocation(user_id());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    verify_csrf();
    Location::makeDefault(user_id(), (int) $_POST['location_id']);
    flash('success', 'Default location updated.');
    redirect('saved.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    verify_csrf();
    Location::remove(user_id(), (int) $_POST['location_id']);
    flash('success', 'Location removed.');
    redirect('saved.php');
}

$locations = Location::allByUser(user_id());
$title = 'Saved Locations';
require __DIR__ . '/../resources/views/partials/header.php';
require __DIR__ . '/../resources/views/partials/navbar.php';
?>
<main class="page-wrap">
    <?php require __DIR__ . '/../resources/views/partials/flash.php'; ?>
    <section class="page-hero compact">
        <div>
            <p class="eyebrow">Saved places</p>
            <h1>Manage your locations.</h1>
        </div>
    </section>
    <section class="panel">
        <?php if (empty($locations)): ?>
            <p class="muted">No saved locations yet.</p>
        <?php else: ?>
            <div class="list-stack">
                <?php foreach ($locations as $location): ?>
                    <div class="list-item actions-row">
                        <div>
                            <strong><?= e($location['city_name']) ?></strong>
                            <p class="muted"><?= e($location['country_name'] ?: 'No country set') ?></p>
                        </div>
                        <div class="button-row">
                            <?php if ((int) $location['is_default'] === 1): ?>
                                <span class="badge">Default</span>
                            <?php else: ?>
                                <form method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="location_id" value="<?= e((string) $location['id']) ?>">
                                    <button class="btn secondary" name="set_default" value="1">Set default</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="location_id" value="<?= e((string) $location['id']) ?>">
                                <button class="btn ghost" name="delete_location" value="1">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../resources/views/partials/footer.php'; ?>
