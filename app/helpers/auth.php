<?php
require_once __DIR__ . '/functions.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $config = app_config();
    session_name($config['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function login_user(array $user): void
{
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role_name'] ?? 'user',
    ];
}

function logout_user(): void
{
    start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function user(): ?array
{
    start_secure_session();
    return $_SESSION['user'] ?? null;
}

function auth_check(): bool
{
    return user() !== null;
}

function user_id(): ?int
{
    return auth_check() ? (int) user()['id'] : null;
}

function is_admin(): bool
{
    return auth_check() && (user()['role'] ?? '') === 'admin';
}
