<?php

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserPreference.php';

if (!isLoggedIn()) {
    setFlash('danger', 'Please login first.');
    redirect('login.php');
}

$userId = (int) ($_SESSION['user']['user_id'] ?? 0);

if ($userId > 0) {
    $preferenceModel = new UserPreference($pdo);
    $preferences = $preferenceModel->findByUser($userId);

    if ($preferences && isset($preferences['theme_mode'])) {
        setThemeMode($preferences['theme_mode']);
    } else {
        setThemeMode('light');
    }
}