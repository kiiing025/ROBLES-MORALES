<?php

declare(strict_types=1);

class SavedLocation
{
    public function __construct(private PDO $pdo)
    {
    }

    public function allByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM saved_locations WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function existsForUser(int $userId, string $cityName): bool
    {
        $stmt = $this->pdo->prepare('SELECT location_id FROM saved_locations WHERE user_id = :user_id AND city_name = :city_name LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'city_name' => $cityName,
        ]);
        return (bool) $stmt->fetch();
    }

    public function create(int $userId, string $cityName, ?string $country = null, ?float $latitude = null, ?float $longitude = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO saved_locations (user_id, city_name, country, latitude, longitude) VALUES (:user_id, :city_name, :country, :latitude, :longitude)');
        $stmt->execute([
            'user_id' => $userId,
            'city_name' => $cityName,
            'country' => $country,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
