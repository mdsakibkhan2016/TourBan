<?php

/**
 * Admin bootstrap — environment, security, session, authorization.
 *
 * Every /admin/*.php page must call require_admin() before output.
 * Authorization is checked against the database on every request
 * (never trusts a session-only role flag).
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

secure_session_start();

if (!function_exists('admin_e')) {
    /** Shorthand HTML escaper. */
    function admin_e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('admin_current_user')) {
    /**
     * Load the authoritative user row for the current session.
     * Returns null when not logged in or the user row disappeared.
     */
    function admin_current_user(): ?array
    {
        if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
            return null;
        }

        $db = (new Database())->getConnection();
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare('SELECT id, name, email, role, is_verified, created_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $_SESSION['user_id']]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('[TourBan] Admin user lookup failed');
            return null;
        }
    }
}

if (!function_exists('require_admin')) {
    /**
     * Gate an admin page.
     * - Not logged in  -> redirect to login (comes back here afterwards).
     * - Logged but not admin -> 403 page.
     * Returns the admin user row on success.
     */
    function require_admin(): array
    {
        if (empty($_SESSION['logged_in'])) {
            // Remember where to send them after login.
            $current = (string) ($_SERVER['REQUEST_URI'] ?? '/admin/');
            $path = parse_url($current, PHP_URL_PATH) ?: '/admin/';
            // Only same-site absolute paths (no protocol/host) are kept.
            if (strpos($path, '/') !== 0 || strpos($path, '//') === 0) {
                $path = '/admin/';
            }
            header('Location: ' . admin_url('login.php') . '?redirect=' . urlencode($path));
            exit;
        }

        $user = admin_current_user();

        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            http_response_code(403);
            $name = $user ? admin_e($user['name']) : 'Guest';
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<title>403 — Access denied | TourBan</title>'
                . '<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;'
                . 'justify-content:center;min-height:100vh;margin:0;background:#f4f6fb;color:#1f2937}'
                . '.box{text-align:center;max-width:420px;padding:2rem}h1{font-size:3rem;margin:0 0 .5rem}'
                . 'a{display:inline-block;margin-top:1rem;padding:.6rem 1.4rem;background:#2563eb;'
                . 'color:#fff;text-decoration:none;border-radius:8px}</style></head><body><div class="box">'
                . '<h1>403</h1><p><strong>' . $name . '</strong>, this area is restricted to administrators.</p>'
                . '<a href="' . admin_e(admin_url('dashboard.php')) . '">Back to my dashboard</a>'
                . '</div></body></html>';
            exit;
        }

        return $user;
    }
}

if (!function_exists('admin_flash')) {
    /** Queue a flash message for the next page render. */
    function admin_flash(string $type, string $message): void
    {
        $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
    }

    /** Read and clear the flash message. */
    function admin_flash_pull(): ?array
    {
        if (empty($_SESSION['admin_flash'])) {
            return null;
        }
        $flash = $_SESSION['admin_flash'];
        unset($_SESSION['admin_flash']);
        return $flash;
    }
}

if (!function_exists('admin_require_post')) {
    /**
     * Validate CSRF on a form POST and continue, or abort with 403.
     */
    function admin_require_post(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed';
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!csrf_verify($token)) {
            http_response_code(403);
            echo 'Security check failed. Please go back, refresh the page and try again.';
            exit;
        }
    }
}

if (!function_exists('admin_redirect')) {
    function admin_redirect(string $path): void
    {
        header('Location: ' . admin_url($path));
        exit;
    }
}
