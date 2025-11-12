<?php

namespace App\Controllers;

use App\Database\Connection;

class AuthController
{
    /**
     * Show login page
     */
    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            redirect('/');
            return;
        }

        echo view('login', []);
    }

    /**
     * Handle login
     */
    public function login(): void
    {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            error_response('Invalid CSRF token', 403);
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            redirect('/login?error=empty_fields');
            return;
        }

        $user = Connection::queryOne(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            redirect('/login?error=invalid_credentials');
            return;
        }

        // Set session
        session_start_safe();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Update last login
        Connection::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        redirect('/');
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        session_start_safe();
        session_destroy();
        redirect('/login');
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        session_start_safe();
        return isset($_SESSION['user_id']);
    }

    /**
     * Middleware: require authentication
     */
    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            redirect('/login');
            exit;
        }
    }
}
