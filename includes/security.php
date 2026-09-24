<?php

/**
 * Security helpers: CSRF tokens, login rate limiting, safe output.
 */

if (!function_exists('csrf_token')) {
    /**
     * Get (or create) the CSRF token for this session.
     */
    function csrf_token(): string
    {
        secure_session_start();

        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Constant-time CSRF token check.
     */
    function csrf_verify($token): bool
    {
        secure_session_start();

        if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('require_csrf')) {
    /**
     * Abort a state-changing JSON request when the CSRF token is invalid.
     * Token is read from the X-CSRF-Token header.
     */
    function require_csrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!csrf_verify($token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Security check failed. Please refresh the page and try again.'
            ]);
            exit;
        }
    }
}

if (!function_exists('login_rate_limit_check')) {
    /**
     * Simple session-based login throttle.
     * Blocks after 5 failures for 5 minutes.
     * Returns true when the attempt is allowed.
     */
    function login_rate_limit_check(): bool
    {
        secure_session_start();

        $failures = (int) ($_SESSION['login_failures'] ?? 0);
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

        if ($lockedUntil > time()) {
            return false;
        }

        if ($lockedUntil > 0 && $lockedUntil <= time()) {
            $_SESSION['login_failures'] = 0;
            $_SESSION['login_locked_until'] = 0;
            return true;
        }

        return $failures < 5;
    }
}

if (!function_exists('login_rate_limit_fail')) {
    function login_rate_limit_fail(): void
    {
        secure_session_start();

        $_SESSION['login_failures'] = (int) ($_SESSION['login_failures'] ?? 0) + 1;

        if ($_SESSION['login_failures'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 300;
        }
    }
}

if (!function_exists('login_rate_limit_reset')) {
    function login_rate_limit_reset(): void
    {
        secure_session_start();
        $_SESSION['login_failures'] = 0;
        $_SESSION['login_locked_until'] = 0;
    }
}

if (!function_exists('json_error')) {
    /**
     * Emit a JSON error and stop. Never includes secrets or stack traces.
     */
    function json_error(int $status, string $message): void
    {
        http_response_code($status);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
