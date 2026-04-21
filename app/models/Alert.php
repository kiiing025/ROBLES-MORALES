<?php

class Alert
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $userId, string $city, string $conditionType, ?float $thresholdValue = null): bool
    {
        $sql = "
            INSERT INTO alerts (user_id, city, condition_type, threshold_value, is_active, created_at)
            VALUES (:user_id, :city, :condition_type, :threshold_value, 1, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':user_id' => $userId,
            ':city' => trim($city),
            ':condition_type' => $conditionType,
            ':threshold_value' => $thresholdValue,
        ]);
    }

    public function getUserAlerts(int $userId): array
    {
        $sql = "
            SELECT alert_id, user_id, city, condition_type, threshold_value, is_active, created_at
            FROM alerts
            WHERE user_id = :user_id
              AND is_active = 1
            ORDER BY created_at DESC, alert_id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function getAllUserAlerts(int $userId): array
    {
        $sql = "
            SELECT alert_id, user_id, city, condition_type, threshold_value, is_active, created_at
            FROM alerts
            WHERE user_id = :user_id
            ORDER BY created_at DESC, alert_id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function findById(int $alertId, int $userId): ?array
    {
        $sql = "
            SELECT alert_id, user_id, city, condition_type, threshold_value, is_active, created_at
            FROM alerts
            WHERE alert_id = :alert_id
              AND user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':alert_id' => $alertId,
            ':user_id' => $userId,
        ]);

        $alert = $stmt->fetch();

        return $alert ?: null;
    }

    public function delete(int $alertId, int $userId): bool
    {
        $sql = "
            DELETE FROM alerts
            WHERE alert_id = :alert_id
              AND user_id = :user_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':alert_id' => $alertId,
            ':user_id' => $userId,
        ]);
    }

    public function setActiveStatus(int $alertId, int $userId, bool $isActive): bool
    {
        $sql = "
            UPDATE alerts
            SET is_active = :is_active
            WHERE alert_id = :alert_id
              AND user_id = :user_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':is_active' => $isActive ? 1 : 0,
            ':alert_id' => $alertId,
            ':user_id' => $userId,
        ]);
    }

    public function update(int $alertId, int $userId, string $city, string $conditionType, ?float $thresholdValue = null): bool
    {
        $sql = "
            UPDATE alerts
            SET city = :city,
                condition_type = :condition_type,
                threshold_value = :threshold_value
            WHERE alert_id = :alert_id
              AND user_id = :user_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':city' => trim($city),
            ':condition_type' => $conditionType,
            ':threshold_value' => $thresholdValue,
            ':alert_id' => $alertId,
            ':user_id' => $userId,
        ]);
    }
}