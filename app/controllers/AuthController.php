<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new User($pdo);
    }

    public function register(array $data): void
    {
        $fullName = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        setOld([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email
        ]);

        if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
            setFlash('danger', 'All fields are required.');
            redirect('register.php');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Invalid email format.');
            redirect('register.php');
        }

        if (strlen($password) < 8) {
            setFlash('danger', 'Password must be at least 8 characters.');
            redirect('register.php');
        }

        if ($password !== $confirmPassword) {
            setFlash('danger', 'Passwords do not match.');
            redirect('register.php');
        }

        if ($this->userModel->emailExists($email)) {
            setFlash('danger', 'Email is already registered.');
            redirect('register.php');
        }

        if ($this->userModel->usernameExists($username)) {
            setFlash('danger', 'Username is already taken.');
            redirect('register.php');
        }

        $userId = $this->userModel->create([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ]);

        $this->userModel->assignDefaultRole($userId);
        $this->userModel->createDefaultPreferences($userId);

        clearOld();
        setFlash('success', 'Registration successful. You can now login.');
        redirect('login.php');
    }

    public function login(array $data): void
    {
        $login = trim($data['login'] ?? '');
        $password = $data['password'] ?? '';

        setOld(['login' => $login]);

        if ($login === '' || $password === '') {
            setFlash('danger', 'Login credentials are required.');
            redirect('login.php');
        }

        $user = $this->userModel->findByEmailOrUsername($login);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            setFlash('danger', 'Invalid login credentials.');
            redirect('login.php');
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role_name']
        ];

        clearOld();

        if ($user['role_name'] === 'admin') {
            redirect('admin_dashboard.php');
        }

        redirect('dashboard.php');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        redirect('login.php');
    }
}