<?php

/**
 * Database Initialization Script
 * Creates the users table and initial setup
 */

require_once 'database.php';

class DatabaseInit
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create users table
     */
    public function createUsersTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                address TEXT,
                phone VARCHAR(20),
                birthdate DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->db->exec($sql);
            echo "Users table created successfully!<br>";

            // Add new columns if they don't exist (for existing tables)
            $this->addNewColumns();

            return true;
        } catch (PDOException $e) {
            echo "Error creating users table: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    /**
     * Add new columns to existing users table
     */
    public function addNewColumns()
    {
        try {
            // Check if phone column exists
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'phone'");
            if ($result->rowCount() == 0) {
                $this->db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER address");
                echo "Added phone column to users table<br>";
            }

            // Check if birthdate column exists
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'birthdate'");
            if ($result->rowCount() == 0) {
                $this->db->exec("ALTER TABLE users ADD COLUMN birthdate DATE AFTER phone");
                echo "Added birthdate column to users table<br>";
            }
        } catch (PDOException $e) {
            echo "Error adding new columns: " . $e->getMessage() . "<br>";
        }
    }

    /**
     * Insert sample user for testing
     */
    public function insertSampleUser()
    {
        try {
            // Check if sample user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute(['demo@tourban.com']);

            if ($stmt->rowCount() > 0) {
                echo "Sample user already exists!<br>";
                return true;
            }

            // Insert sample user
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password, address) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'Demo User',
                'demo@tourban.com',
                $hashedPassword,
                '123 Demo Street, Demo City, DC 12345'
            ]);

            echo "Sample user created successfully!<br>";
            echo "Email: demo@tourban.com<br>";
            echo "Password: password123<br>";
            return true;
        } catch (PDOException $e) {
            echo "Error inserting sample user: " . $e->getMessage() . "<br>";
            return false;
        }
    }

    /**
     * Initialize complete database setup
     */
    public function initialize()
    {
        echo "<h2>Database Initialization</h2>";

        // Create database first
        $database = new Database();
        if ($database->createDatabase()) {
            echo "Database created/verified successfully!<br><br>";
        }

        // Reconnect to the specific database
        $this->db = $database->getConnection();

        // Create users table
        if ($this->createUsersTable()) {
            // Insert sample user
            $this->insertSampleUser();
        }

        echo "<br><strong>Database initialization complete!</strong><br>";
        echo "<a href='../login.php'>Go to Login Page</a>";
    }
}

// Run initialization if accessed directly
if (basename($_SERVER['PHP_SELF']) == 'init_database.php') {
    $init = new DatabaseInit();
    $init->initialize();
}
