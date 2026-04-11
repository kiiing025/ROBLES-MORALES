<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect(string $path): void {
    header("Location: $path");
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

function isAdmin(): bool {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function old(string $key): string {
    return htmlspecialchars($_SESSION['old'][$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function setOld(array $data): void {
    $_SESSION['old'] = $data;
}

function clearOld(): void {
    unset($_SESSION['old']);
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}