<?php
require_once __DIR__ . '/../helpers/functions.php';

if (!isLoggedIn()) {
    setFlash('danger', 'Please login first.');
    redirect('login.php');
}

if (!isAdmin()) {
    setFlash('danger', 'Access denied. Admins only.');
    redirect('dashboard.php');
}