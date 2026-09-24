<?php

/**
 * Database Configuration
 * TourBan Travel Website
 *
 * All credentials come from environment variables (.env or hosting panel).
 */

require_once __DIR__ . '/env.php';

// Database configuration (environment-driven with local dev defaults)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'tourban_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    /**
     * Get database connection
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $exception) {
            error_log('[TourBan] Database connection failed: ' . $exception->getMessage());

            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "Connection error: " . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
            }
            // Production: stay silent here; callers handle a null connection.
        }

        return $this->conn;
    }

    /**
     * Create database if it doesn't exist
     */
    public function createDatabase()
    {
        try {
            $dsn = "mysql:host=" . $this->host . ";charset=" . $this->charset;
            $conn = new PDO($dsn, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '', $this->db_name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $conn->exec($sql);

            return true;
        } catch (PDOException $exception) {
            error_log('[TourBan] Database creation failed: ' . $exception->getMessage());

            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "Database creation error: " . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
            } else {
                echo "Database initialization failed.";
            }

            return false;
        }
    }
}
