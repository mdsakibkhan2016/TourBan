<?php

/**
 * Security helpers: CSRF tokens, login rate limiting, safe output.
 */

require_once __DIR__ . '/../config/database.php';

if (!function_exists('client_ip')) {
    /**
     * Best-effort client IP (REMOTE_ADDR only — never trust forwarded headers,
     * which are attacker-controlled outside a trusted proxy).
     */
    function client_ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}

if (!function_exists('rate_limit_conn')) {
    function rate_limit_conn()
    {
        static $db = null;
        if ($db === null) {
            $db = (new Database())->getConnection();
        }
        return $db;
    }
}

if (!function_exists('rate_limit_exceeded')) {
    /**
     * True when $key has reached $max hits inside the sliding $windowSeconds.
     * Fails open (allows the request) if the rate_limits table is unavailable,
     * so a missing migration can never lock users out — it is logged instead.
     */
    function rate_limit_exceeded(string $key, int $max, int $windowSeconds): bool
    {
        $db = rate_limit_conn();
        if (!$db) {
            return false;
        }

        try {
            $stmt = $db->prepare(
                'SELECT hits, first_hit_at FROM rate_limits WHERE attempt_key = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            if (!$row) {
                return false;
            }

            // Window expired -> the counter is stale and will be reset on record
            $stale = strtotime($row['first_hit_at']) !== false
                && (time() - strtotime($row['first_hit_at'])) >= $windowSeconds;
            if ($stale) {
                return false;
            }

            return (int) $row['hits'] >= $max;
        } catch (PDOException $e) {
            error_log('[TourBan] Rate limit check error');
            return false;
        }
    }
}

if (!function_exists('rate_limit_record')) {
    /**
     * Count one attempt for $key. Resets the window when it has expired.
     * Uses MySQL NOW() so timing is consistent with OTP expiry checks.
     */
    function rate_limit_record(string $key, int $max, int $windowSeconds): void
    {
        $db = rate_limit_conn();
        if (!$db) {
            return;
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO rate_limits (attempt_key, hits, first_hit_at, last_hit_at)
                 VALUES (?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                     hits = IF(first_hit_at < NOW() - INTERVAL ? SECOND, 1, hits + 1),
                     first_hit_at = IF(first_hit_at < NOW() - INTERVAL ? SECOND, NOW(), first_hit_at),
                     last_hit_at = NOW()"
            );
            $stmt->execute([$key, $windowSeconds, $windowSeconds]);
        } catch (PDOException $e) {
            error_log('[TourBan] Rate limit record error');
        }
    }
}

if (!function_exists('rate_limit_clear')) {
    /** Forget all counters for $key (used after a successful auth). */
    function rate_limit_clear(string $key): void
    {
        $db = rate_limit_conn();
        if (!$db) {
            return;
        }

        try {
            $db->prepare('DELETE FROM rate_limits WHERE attempt_key = ?')->execute([$key]);
        } catch (PDOException $e) {
            error_log('[TourBan] Rate limit clear error');
        }
    }
}

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
