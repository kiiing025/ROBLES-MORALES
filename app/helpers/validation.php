<?php
function validate_registration(array $data): array
{
    $errors = [];

    if (trim($data['name'] ?? '') === '') {
        $errors['name'] = 'Full name is required.';
    }

    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $password = $data['password'] ?? '';
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Password must be at least 8 characters and include upper, lower, and number.';
    }

    if (($data['password_confirmation'] ?? '') !== $password) {
        $errors['password_confirmation'] = 'Passwords do not match.';
    }

    return $errors;
}

function validate_login(array $data): array
{
    $errors = [];
    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (($data['password'] ?? '') === '') {
        $errors['password'] = 'Password is required.';
    }
    return $errors;
}

function validate_city(array $data): array
{
    $errors = [];
    if (trim($data['city'] ?? '') === '') {
        $errors['city'] = 'City is required.';
    }
    return $errors;
}
