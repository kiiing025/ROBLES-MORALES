<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/SettingsController.php';
require_once __DIR__ . '/../app/models/Preference.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SettingsController::update(user_id());
}

$preferences = Preference::getByUser(user_id());
$title = 'Preferences';
require __DIR__ . '/../resources/views/partials/header.php';
require __DIR__ . '/../resources/views/partials/navbar.php';
?>
<main class="page-wrap">
    <?php require __DIR__ . '/../resources/views/partials/flash.php'; ?>
    <section class="page-hero compact">
        <div>
            <p class="eyebrow">Preferences</p>
            <h1>Adjust your experience.</h1>
        </div>
    </section>
    <section class="panel form-panel">
        <form method="POST" class="form-grid">
            <?= csrf_field() ?>
            <label>Temperature unit</label>
            <select name="temperature_unit">
                <option value="celsius" <?= ($preferences['temperature_unit'] ?? '') === 'celsius' ? 'selected' : '' ?>>Celsius</option>
                <option value="fahrenheit" <?= ($preferences['temperature_unit'] ?? '') === 'fahrenheit' ? 'selected' : '' ?>>Fahrenheit</option>
            </select>

            <label>Theme</label>
            <select name="theme">
                <option value="dark" <?= ($preferences['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
                <option value="light" <?= ($preferences['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
            </select>

            <label class="checkbox-row">
                <input type="checkbox" name="notifications_enabled" <?= !empty($preferences['notifications_enabled']) ? 'checked' : '' ?>>
                Enable weather notifications
            </label>

            <button class="btn primary" type="submit">Save changes</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../resources/views/partials/footer.php'; ?>
