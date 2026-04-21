<?php
require_once __DIR__ . '/../helpers/auth.php';

start_secure_session();
if (auth_check()) {
    redirect('dashboard.php');
}
