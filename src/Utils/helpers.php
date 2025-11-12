<?php

/**
 * Helper Functions
 */

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     */
    function config(string $key, $default = null)
    {
        static $config = null;

        if ($config === null) {
            $config = [
                'database' => require __DIR__ . '/../../config/database.php',
                'app' => require __DIR__ . '/../../config/app.php',
            ];
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to URL
     */
    function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('json_response')) {
    /**
     * Send JSON response
     */
    function json_response($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('error_response')) {
    /**
     * Send error JSON response
     */
    function error_response(string $message, int $status = 400): void
    {
        json_response(['error' => $message], $status);
    }
}

if (!function_exists('view')) {
    /**
     * Render view file
     */
    function view(string $name, array $data = []): string
    {
        extract($data);
        ob_start();
        require __DIR__ . "/../../views/$name.php";
        return ob_get_clean();
    }
}

if (!function_exists('session_start_safe')) {
    /**
     * Start session safely
     */
    function session_start_safe(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Generate CSRF token
     */
    function csrf_token(): string
    {
        session_start_safe();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Verify CSRF token
     */
    function verify_csrf_token(string $token): bool
    {
        session_start_safe();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date
     */
    function format_date($date, string $format = 'Y-m-d H:i:s'): string
    {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        return $date->format($format);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize string
     */
    function sanitize(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('log_message')) {
    /**
     * Log message to file
     */
    function log_message(string $message, string $level = 'info'): void
    {
        $logFile = config('app.paths.logs') . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

if (!function_exists('calculate_eeat_score')) {
    /**
     * Calculate overall E-E-A-T score
     */
    function calculate_eeat_score(float $expertise, float $authoritativeness, float $trustworthiness, float $experience): float
    {
        // Weighted average: T=35%, A=30%, E=20%, E=15%
        return round(
            ($trustworthiness * 3.5) +
            ($authoritativeness * 3.0) +
            ($expertise * 2.0) +
            ($experience * 1.5),
            2
        );
    }
}

if (!function_exists('get_status_badge')) {
    /**
     * Get status badge HTML
     */
    function get_status_badge(float $score): string
    {
        if ($score >= 8) {
            return '<span class="badge badge-success">🟢 High</span>';
        } elseif ($score >= 5) {
            return '<span class="badge badge-warning">🟡 Medium</span>';
        } else {
            return '<span class="badge badge-danger">🔴 Low</span>';
        }
    }
}
