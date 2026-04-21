<?php
require_once __DIR__ . '/../helpers/db.php';

class Location
{
    public static function allByUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM saved_locations WHERE user_id = :user_id ORDER BY is_default DESC, city_name ASC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function save(int $userId, string $city, ?string $country = null): void
    {
        $stmt = db()->prepare('INSERT INTO saved_locations (user_id, city_name, country_name, is_default, created_at) VALUES (:user_id, :city_name, :country_name, 0, NOW()) ON DUPLICATE KEY UPDATE city_name = VALUES(city_name), country_name = VALUES(country_name)');
        $stmt->execute([
            'user_id' => $userId,
            'city_name' => $city,
            'country_name' => $country,
        ]);
    }

    public static function makeDefault(int $userId, int $id): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE saved_locations SET is_default = 0 WHERE user_id = :user_id')->execute(['user_id' => $userId]);
        $pdo->prepare('UPDATE saved_locations SET is_default = 1 WHERE user_id = :user_id AND id = :id')->execute(['user_id' => $userId, 'id' => $id]);
        $pdo->commit();
    }

    public static function remove(int $userId, int $id): void
    {
        $stmt = db()->prepare('DELETE FROM saved_locations WHERE user_id = :user_id AND id = :id');
        $stmt->execute(['user_id' => $userId, 'id' => $id]);
    }

    public static function defaultForUser(int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM saved_locations WHERE user_id = :user_id AND is_default = 1 LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
