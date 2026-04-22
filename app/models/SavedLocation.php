<?php

class SavedLocation
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function allByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM saved_locations
            WHERE user_id = :user_id
            ORDER BY is_default DESC, created_at DESC, location_id DESC
        ');

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    public function exists(int $userId, string $cityName): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT location_id
            FROM saved_locations
            WHERE user_id = :user_id
              AND LOWER(city_name) = LOWER(:city_name)
            LIMIT 1
        ');

        $stmt->execute([
            'user_id' => $userId,
            'city_name' => $cityName,
        ]);

        return (bool) $stmt->fetch();
    }

    public function add(int $userId, string $cityName, ?string $country, ?float $latitude, ?float $longitude): void
    {
        $stmtCheck = $this->pdo->prepare('
            SELECT COUNT(*) AS total
            FROM saved_locations
            WHERE user_id = :user_id
        ');

        $stmtCheck->execute([
            'user_id' => $userId
        ]);

        $count = (int) ($stmtCheck->fetch()['total'] ?? 0);
        $isDefault = $count === 0 ? 1 : 0;

        $stmt = $this->pdo->prepare('
            INSERT INTO saved_locations (user_id, city_name, country, latitude, longitude, is_default)
            VALUES (:user_id, :city_name, :country, :latitude, :longitude, :is_default)
        ');

        $stmt->execute([
            'user_id' => $userId,
            'city_name' => trim($cityName),
            'country' => $country,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_default' => $isDefault,
        ]);
    }

    public function findById(int $locationId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM saved_locations
            WHERE location_id = :location_id
              AND user_id = :user_id
            LIMIT 1
        ');

        $stmt->execute([
            'location_id' => $locationId,
            'user_id' => $userId
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getDefaultByUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM saved_locations
            WHERE user_id = :user_id
              AND is_default = 1
            LIMIT 1
        ');

        $stmt->execute([
            'user_id' => $userId
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function setDefault(int $locationId, int $userId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $resetStmt = $this->pdo->prepare('
                UPDATE saved_locations
                SET is_default = 0
                WHERE user_id = :user_id
            ');

            $resetStmt->execute([
                'user_id' => $userId
            ]);

            $setStmt = $this->pdo->prepare('
                UPDATE saved_locations
                SET is_default = 1
                WHERE location_id = :location_id
                  AND user_id = :user_id
            ');

            $setStmt->execute([
                'location_id' => $locationId,
                'user_id' => $userId
            ]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function delete(int $locationId, int $userId): bool
    {
        $location = $this->findById($locationId, $userId);

        $stmt = $this->pdo->prepare('
            DELETE FROM saved_locations
            WHERE location_id = :location_id
              AND user_id = :user_id
        ');

        $deleted = $stmt->execute([
            'location_id' => $locationId,
            'user_id' => $userId
        ]);

        if ($deleted && $location && (int) $location['is_default'] === 1) {
            $nextStmt = $this->pdo->prepare('
                SELECT location_id
                FROM saved_locations
                WHERE user_id = :user_id
                ORDER BY created_at DESC, location_id DESC
                LIMIT 1
            ');

            $nextStmt->execute([
                'user_id' => $userId
            ]);

            $next = $nextStmt->fetch();

            if ($next) {
                $this->setDefault((int) $next['location_id'], $userId);
            }
        }

        return $deleted;
    }
}