<?php
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../models/Preference.php';

class SettingsController
{
    public static function update(int $userId): void
    {
        verify_csrf();
        $unit = $_POST['temperature_unit'] ?? 'celsius';
        $theme = $_POST['theme'] ?? 'dark';
        $notifications = isset($_POST['notifications_enabled']);

        if (!in_array($unit, ['celsius', 'fahrenheit'], true)) {
            $unit = 'celsius';
        }

        if (!in_array($theme, ['dark', 'light'], true)) {
            $theme = 'dark';
        }

        Preference::updateForUser($userId, $unit, $theme, $notifications);
        flash('success', 'Preferences updated.');
        redirect('preferences.php');
    }
}
