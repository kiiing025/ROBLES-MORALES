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
}
