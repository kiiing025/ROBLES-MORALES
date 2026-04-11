<?php
require_once __DIR__ . '/../app/middleware/admin.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('danger', 'Invalid request.');
    redirect('admin_dashboard.php');
}

$userIdToDelete = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$currentAdminId = (int) ($_SESSION['user']['user_id'] ?? 0);

if ($userIdToDelete <= 0) {
    setFlash('danger', 'Invalid user selected.');
    redirect('admin_dashboard.php');
}

if ($userIdToDelete === $currentAdminId) {
    setFlash('danger', 'You cannot delete your own admin account while logged in.');
    redirect('admin_dashboard.php');
}

$userModel = new User($pdo);
$targetUser = $userModel->findById($userIdToDelete);

if (!$targetUser) {
    setFlash('danger', 'User not found.');
    redirect('admin_dashboard.php');
}

$userModel->deleteById($userIdToDelete);

setFlash('success', 'User deleted successfully.');
redirect('admin_dashboard.php');