<?php

declare(strict_types=1);

class SearchHistory
{
    public function __construct(private PDO $pdo)
    {
    }

    public function allByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM search_history WHERE user_id = :user_id ORDER BY searched_at DESC LIMIT 10');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $cityName): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO search_history (user_id, city_name) VALUES (:user_id, :city_name)');
        $stmt->execute([
            'user_id' => $userId,
            'city_name' => $cityName,
        ]);
    }
}
