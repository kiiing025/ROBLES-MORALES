<?php

declare(strict_types=1);

class UserPreference
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getByUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_preferences WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateByUser(int $userId, string $temperatureUnit, string $windUnit, string $themeMode): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE user_preferences
            SET temperature_unit = :temperature_unit,
                wind_unit = :wind_unit,
                theme_mode = :theme_mode
            WHERE user_id = :user_id
        ');

        $stmt->execute([
            'temperature_unit' => $temperatureUnit,
            'wind_unit' => $windUnit,
            'theme_mode' => $themeMode,
            'user_id' => $userId,
        ]);
    }
}