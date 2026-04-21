<?php
require_once __DIR__ . '/../helpers/db.php';

class Preference
{
    public static function getByUser(int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM user_preferences WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateForUser(int $userId, string $unit, string $theme, bool $notifications): void
    {
        $stmt = db()->prepare('UPDATE user_preferences SET temperature_unit = :temperature_unit, theme = :theme, notifications_enabled = :notifications_enabled, updated_at = NOW() WHERE user_id = :user_id');
        $stmt->execute([
            'temperature_unit' => $unit,
            'theme' => $theme,
            'notifications_enabled' => $notifications ? 1 : 0,
            'user_id' => $userId,
        ]);
    }
}
