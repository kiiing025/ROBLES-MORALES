<?php

class Alert
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        int $userId,
        string $city,
        string $conditionType,
        ?float $thresholdValue = null,
        bool $emailEnabled = false
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO alerts (
                user_id,
                city,
                condition_type,
                threshold_value,
                is_triggered,
                last_triggered_at,
                email_enabled,
                last_email_sent_at,
                created_at
            )
            VALUES (
                :user_id,
                :city,
                :condition_type,
                :threshold_value,
                0,
                NULL,
                :email_enabled,
                NULL,
                NOW()
            )
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'city' => $city,
            'condition_type' => $conditionType,
            'threshold_value' => $thresholdValue,
            'email_enabled' => $emailEnabled ? 1 : 0
        ]);
    }

    public function getUserAlerts(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM alerts
            WHERE user_id = :user_id
            ORDER BY is_triggered DESC, created_at DESC, alert_id DESC
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserAlertsByCity(int $userId, string $city): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM alerts
            WHERE user_id = :user_id
              AND LOWER(city) = LOWER(:city)
            ORDER BY created_at DESC, alert_id DESC
        ");

        $stmt->execute([
            'user_id' => $userId,
            'city' => $city
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTriggerStatus(int $alertId, int $userId, bool $isTriggered): bool
    {
        if ($isTriggered) {
            $stmt = $this->pdo->prepare("
                UPDATE alerts
                SET is_triggered = 1,
                    last_triggered_at = NOW()
                WHERE alert_id = :alert_id
                  AND user_id = :user_id
            ");

            return $stmt->execute([
                'alert_id' => $alertId,
                'user_id' => $userId
            ]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE alerts
            SET is_triggered = 0
            WHERE alert_id = :alert_id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            'alert_id' => $alertId,
            'user_id' => $userId
        ]);
    }

    public function markEmailSent(int $alertId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE alerts
            SET last_email_sent_at = NOW()
            WHERE alert_id = :alert_id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            'alert_id' => $alertId,
            'user_id' => $userId
        ]);
    }

    public function delete(int $alertId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM alerts
            WHERE alert_id = :alert_id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            'alert_id' => $alertId,
            'user_id' => $userId
        ]);
    }
}