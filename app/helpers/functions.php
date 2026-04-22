<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function isAdmin(): bool
{
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $requestToken = $_POST['csrf_token'] ?? '';

    if (
        !$sessionToken ||
        !$requestToken ||
        !hash_equals($sessionToken, $requestToken)
    ) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function setOld($key, $value = null): void
{
    if (!isset($_SESSION['old'])) {
        $_SESSION['old'] = [];
    }

    if (is_array($key)) {
        $_SESSION['old'] = $key;
        return;
    }

    $_SESSION['old'][$key] = $value;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function clearOld(): void
{
    unset($_SESSION['old']);
}

function getLoginRateLimitData(): array
{
    if (!isset($_SESSION['login_rate_limit'])) {
        $_SESSION['login_rate_limit'] = [
            'attempts' => 0,
            'blocked_until' => 0,
        ];
    }

    return $_SESSION['login_rate_limit'];
}

function isLoginBlocked(): bool
{
    $rateLimit = getLoginRateLimitData();
    return time() < (int) $rateLimit['blocked_until'];
}

function getRemainingBlockSeconds(): int
{
    $rateLimit = getLoginRateLimitData();
    $remaining = (int) $rateLimit['blocked_until'] - time();
    return max(0, $remaining);
}

function recordFailedLoginAttempt(): void
{
    $rateLimit = getLoginRateLimitData();
    $rateLimit['attempts']++;

    if ($rateLimit['attempts'] >= 5) {
        $rateLimit['blocked_until'] = time() + 60;
        $rateLimit['attempts'] = 0;
    }

    $_SESSION['login_rate_limit'] = $rateLimit;
}

function clearLoginRateLimit(): void
{
    $_SESSION['login_rate_limit'] = [
        'attempts' => 0,
        'blocked_until' => 0,
    ];
}

function setThemeMode(string $themeMode): void
{
    $_SESSION['user_theme'] = in_array($themeMode, ['light', 'dark'], true) ? $themeMode : 'light';
}

function currentThemeMode(): string
{
    return $_SESSION['user_theme'] ?? 'light';
}

function isDarkMode(): bool
{
    return currentThemeMode() === 'dark';
}