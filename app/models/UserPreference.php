<?php

class UserPreference
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM user_preferences
            WHERE user_id = :user_id
            LIMIT 1
        ');

        $stmt->execute([
            'user_id' => $userId
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getByUser(int $userId): ?array
    {
        return $this->findByUser($userId);
    }

    public function getByUserId(int $userId): ?array
    {
        return $this->findByUser($userId);
    }

    public function createDefault(int $userId): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO user_preferences (user_id, temperature_unit, wind_unit, theme_mode)
            VALUES (:user_id, :temperature_unit, :wind_unit, :theme_mode)
        ');

        return $stmt->execute([
            'user_id' => $userId,
            'temperature_unit' => 'C',
            'wind_unit' => 'kph',
            'theme_mode' => 'light'
        ]);
    }

    public function ensureExists(int $userId): void
    {
        $existing = $this->findByUser($userId);

        if (!$existing) {
            $this->createDefault($userId);
        }
    }

    public function updateByUser(int $userId, string $temperatureUnit, string $windUnit, string $themeMode): void
    {
        $this->ensureExists($userId);

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
            'user_id' => $userId
        ]);
    }
}