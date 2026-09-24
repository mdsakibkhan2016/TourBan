<?php

/**
 * Environment configuration loader
 * Loads .env file values and provides the env() helper.
 * Never commit the real .env file.
 */

if (!function_exists('env')) {
    /**
     * Read an environment variable.
     * Real server/environment variables take precedence over .env values.
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }
}

/**
 * Parse .env file into process environment (does not override existing vars).
 */
if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $value = trim($value, "\"'");
            }

            if ($name === '' || getenv($name) !== false) {
                continue;
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

load_env_file(dirname(__DIR__) . '/.env');

// Base URL for the app (no trailing slash). Empty string = domain root.
// Example: https://example.com  or  https://example.com/tourban
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim((string) env('APP_URL', ''), '/'));
}

// Display detailed errors only when explicitly enabled.
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));
}

/**
 * Start a session with production-safe cookie settings.
 */
if (!function_exists('secure_session_start')) {
    function secure_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
