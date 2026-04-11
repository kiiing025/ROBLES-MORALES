<?php
require_once __DIR__ . '/../app/middleware/admin.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('danger', 'Invalid request.');
    redirect('admin_dashboard.php');
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$newRole = trim($_POST['role_name'] ?? '');
$currentAdminId = (int) ($_SESSION['user']['user_id'] ?? 0);

if ($userId <= 0 || !in_array($newRole, ['admin', 'user'], true)) {
    setFlash('danger', 'Invalid role update request.');
    redirect('admin_dashboard.php');
}

if ($userId === $currentAdminId) {
    setFlash('danger', 'You cannot change your own active role.');
    redirect('admin_dashboard.php');
}

$userModel = new User($pdo);
$targetUser = $userModel->findById($userId);

if (!$targetUser) {
    setFlash('danger', 'User not found.');
    redirect('admin_dashboard.php');
}

$userModel->updateUserRole($userId, $newRole);

setFlash('success', 'User role updated successfully.');
redirect('admin_dashboard.php');