<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/functions.php';

if (!isLoggedIn()) {
    setFlash('danger', 'Please login first.');
    redirect('login.php');
}
