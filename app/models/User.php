<?php

declare(strict_types=1);

class User
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmailOrUsername(string $login): ?array
    {
        $sql = '
            SELECT 
                u.user_id,
                u.full_name,
                u.username,
                u.email,
                u.password_hash,
                COALESCE(r.role_name, "user") AS role_name
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ? OR u.username = ?
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['username'],
            $data['email'],
            $data['password_hash'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function assignDefaultRole(int $userId): void
    {
        $stmt = $this->pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'user' LIMIT 1");
        $stmt->execute();
        $role = $stmt->fetch();

        if ($role) {
            $insert = $this->pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            $insert->execute([
                $userId,
                $role['role_id'],
            ]);
        }
    }

    public function createDefaultPreferences(int $userId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO user_preferences (user_id) VALUES (?)');
        $stmt->execute([$userId]);
    }

    public function getAllUsers(): array
    {
        $sql = '
            SELECT 
                u.user_id,
                u.full_name,
                u.username,
                u.email,
                u.created_at,
                COALESCE(r.role_name, "user") AS role_name
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            ORDER BY u.user_id DESC
        ';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function findById(int $userId): ?array
    {
        $sql = '
            SELECT 
                u.user_id,
                u.full_name,
                u.username,
                u.email,
                u.created_at,
                COALESCE(r.role_name, "user") AS role_name
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.user_id = ?
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function deleteById(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public function getRoleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare('SELECT role_id FROM roles WHERE role_name = ? LIMIT 1');
        $stmt->execute([$roleName]);
        $row = $stmt->fetch();

        return $row ? (int) $row['role_id'] : null;
    }

    public function updateUserRole(int $userId, string $roleName): void
    {
        $roleId = $this->getRoleIdByName($roleName);

        if ($roleId === null) {
            throw new RuntimeException('Invalid role.');
        }

        $deleteStmt = $this->pdo->prepare('DELETE FROM user_roles WHERE user_id = ?');
        $deleteStmt->execute([$userId]);

        $insertStmt = $this->pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
        $insertStmt->execute([$userId, $roleId]);
    }
}