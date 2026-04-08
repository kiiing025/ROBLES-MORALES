<?php

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByEmailOrUsername(string $login): ?array {
        $sql = "SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function emailExists(string $email): bool {
        $sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public function usernameExists(string $username): bool {
        $sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): int {
        $sql = "INSERT INTO users (full_name, username, email, password_hash)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['username'],
            $data['email'],
            $data['password_hash']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function assignDefaultRole(int $userId): void {
        $sql = "SELECT role_id FROM roles WHERE role_name = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user']);
        $role = $stmt->fetch();

        if ($role) {
            $insertSql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
            $insertStmt = $this->pdo->prepare($insertSql);
            $insertStmt->execute([$userId, $role['role_id']]);
        }
    }

    public function createDefaultPreferences(int $userId): void {
        $sql = "INSERT INTO user_preferences (user_id) VALUES (?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
}