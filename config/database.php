<?php

/**
 * Database Configuration
 * TourBan Travel Website
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tourban_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

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
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
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

            $sql = "CREATE DATABASE IF NOT EXISTS " . $this->db_name . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $conn->exec($sql);

            return true;
        } catch (PDOException $exception) {
            echo "Database creation error: " . $exception->getMessage();
            return false;
        }
    }
}
