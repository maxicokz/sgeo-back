<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Services\AuditService;
use App\Services\MfaService;

class AuthController
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_WINDOW     = 15 * 60;    // 15 minutes in seconds
    private const SESSION_IDLE       = 15 * 60;    // 15 minutes idle timeout
    private const SESSION_LIFETIME   = 10 * 3600;  // 10 hours absolute lifetime

    private AuditService $audit;
    private MfaService   $mfa;

    public function __construct()
    {
        $this->audit = new AuditService();
        $this->mfa   = new MfaService();
    }

    // =========================================================================
    // Show login page
    // =========================================================================

    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            redirect('/');
            return;
        }

        echo view('login', []);
    }

    // =========================================================================
    // Handle login POST
    // =========================================================================

    public function login(): void
    {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            error_response('Invalid CSRF token', 403);
        }

        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($username === '' || $password === '') {
            redirect('/login?error=invalid_credentials');
            return;
        }

        // ------------------------------------------------------------------
        // Brute-force protection: count recent failed attempts
        // ------------------------------------------------------------------
        if ($this->isLoginBlocked($username, $ip)) {
            $this->audit->logLoginBlocked($username, $ip);
            redirect('/login?error=invalid_credentials');
            return;
        }

        // ------------------------------------------------------------------
        // Credential check
        // ------------------------------------------------------------------
        $user = Connection::queryOne(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Record failed attempt
            $this->recordLoginAttempt($username, $ip, false);
            $this->audit->logLoginFailed($username, $ip, $userAgent);
            redirect('/login?error=invalid_credentials');
            return;
        }

        // ------------------------------------------------------------------
        // Success — record attempt, clear old failures
        // ------------------------------------------------------------------
        $this->recordLoginAttempt($username, $ip, true);
        $this->clearOldAttempts($username, $ip);

        // ------------------------------------------------------------------
        // MFA check
        // ------------------------------------------------------------------
        if ($this->mfa->isMfaRequired($user)) {
            $mfaRecord = Connection::queryOne(
                'SELECT enabled FROM mfa_secrets WHERE user_id = ?',
                [$user['id']]
            );

            if ($mfaRecord && (bool) $mfaRecord['enabled']) {
                session_start_safe();
                $_SESSION['mfa_pending_user_id'] = $user['id'];
                redirect('/login/mfa');
                return;
            }
        }

        // ------------------------------------------------------------------
        // Start authenticated session
        // ------------------------------------------------------------------
        $this->startSession($user);

        // ------------------------------------------------------------------
        // Force password change
        // ------------------------------------------------------------------
        if (!empty($user['force_password_change'])) {
            redirect('/change-password');
            return;
        }

        $this->audit->logLogin($user['id'], $user['username'], $ip, $userAgent);

        redirect('/');
    }

    // =========================================================================
    // MFA form
    // =========================================================================

    public function showMfaForm(): void
    {
        session_start_safe();

        if (empty($_SESSION['mfa_pending_user_id'])) {
            redirect('/login');
            return;
        }

        echo view('login_mfa', []);
    }

    public function verifyMfa(): void
    {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            error_response('Invalid CSRF token', 403);
        }

        session_start_safe();
        $pendingUserId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$pendingUserId) {
            redirect('/login');
            return;
        }

        $code = trim($_POST['mfa_code'] ?? '');
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $mfaRecord = Connection::queryOne(
            'SELECT secret, backup_codes FROM mfa_secrets WHERE user_id = ? AND enabled = TRUE',
            [$pendingUserId]
        );

        $verified = false;

        if ($mfaRecord) {
            // Verify TOTP
            if ($this->mfa->verifyCode($mfaRecord['secret'], $code)) {
                $verified = true;
            } else {
                // Check backup codes
                $backupCodes = json_decode($mfaRecord['backup_codes'] ?? '[]', true);
                $codeUpper   = strtoupper($code);
                $codeIndex   = array_search($codeUpper, $backupCodes, true);

                if ($codeIndex !== false) {
                    $verified = true;
                    // Consume used backup code
                    array_splice($backupCodes, $codeIndex, 1);
                    Connection::update(
                        'mfa_secrets',
                        ['backup_codes' => json_encode($backupCodes)],
                        'user_id = ?',
                        [$pendingUserId]
                    );
                }
            }
        }

        if (!$verified) {
            redirect('/login/mfa?error=invalid_code');
            return;
        }

        // MFA passed — load user and complete login
        $user = Connection::queryOne('SELECT * FROM users WHERE id = ?', [$pendingUserId]);
        if (!$user) {
            redirect('/login');
            return;
        }

        unset($_SESSION['mfa_pending_user_id']);
        $this->startSession($user);

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->audit->logLogin($user['id'], $user['username'], $ip, $userAgent);

        if (!empty($user['force_password_change'])) {
            redirect('/change-password');
            return;
        }

        redirect('/');
    }

    // =========================================================================
    // Logout
    // =========================================================================

    public function logout(): void
    {
        session_start_safe();

        $userId   = $_SESSION['user_id']   ?? 0;
        $username = $_SESSION['username']  ?? '';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($userId) {
            $this->audit->logLogout($userId, $username, $ip);
        }

        session_destroy();
        redirect('/login');
    }

    // =========================================================================
    // Authentication checks
    // =========================================================================

    public function isAuthenticated(): bool
    {
        session_start_safe();

        if (empty($_SESSION['user_id'])) {
            return false;
        }

        $now = time();

        // Idle timeout (15 min)
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > self::SESSION_IDLE) {
            session_destroy();
            return false;
        }

        // Absolute session lifetime (10 h)
        if (isset($_SESSION['created_at']) && ($now - $_SESSION['created_at']) > self::SESSION_LIFETIME) {
            session_destroy();
            return false;
        }

        $_SESSION['last_activity'] = $now;
        return true;
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

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Check if login attempts from this IP or username exceed the threshold.
     */
    private function isLoginBlocked(string $username, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - self::LOCKOUT_WINDOW);

        $row = Connection::queryOne(
            'SELECT COUNT(*) AS cnt FROM login_attempts
             WHERE (ip = ? OR username = ?)
               AND attempted_at >= ?
               AND success = FALSE',
            [$ip, $username, $since]
        );

        return (int) ($row['cnt'] ?? 0) >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record a login attempt (success or failure).
     */
    private function recordLoginAttempt(string $username, string $ip, bool $success): void
    {
        Connection::insert('login_attempts', [
            'username'     => $username,
            'ip'           => $ip,
            'attempted_at' => date('Y-m-d H:i:s'),
            'success'      => $success ? 'TRUE' : 'FALSE',
        ]);
    }

    /**
     * Clear old failed attempts after a successful login.
     */
    private function clearOldAttempts(string $username, string $ip): void
    {
        $since = date('Y-m-d H:i:s', time() - self::LOCKOUT_WINDOW);

        try {
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare(
                'DELETE FROM login_attempts
                 WHERE (ip = ? OR username = ?)
                   AND attempted_at < ?'
            );
            $stmt->execute([$ip, $username, $since]);
        } catch (\Throwable $e) {
            error_log('[AuthController] clearOldAttempts error: ' . $e->getMessage());
        }
    }

    /**
     * Initialise $_SESSION for the authenticated user.
     */
    private function startSession(array $user): void
    {
        session_start_safe();
        session_regenerate_id(true);

        $now = time();
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = $now;
        $_SESSION['created_at']    = $now;

        // Update last_login timestamp
        Connection::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    }
}
