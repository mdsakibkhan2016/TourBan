<?php

/**
 * User Authentication Class
 * Handles user login, registration, and session management
 */

require_once __DIR__ . '/../config/database.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Register a new user
     */
    public function register($name, $email, $password, $address = '')
    {
        try {
            // Check if email already exists
            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $sql = "INSERT INTO users (name, email, password, address) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$name, $email, $hashedPassword, $address]);

            if ($result) {
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password)
    {
        try {
            $sql = "SELECT id, name, email, password, address, phone, birthdate FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();

                if (password_verify($password, $user['password'])) {
                    // Start session if not already started
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_address'] = $user['address'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_birthdate'] = $user['birthdate'];
                    $_SESSION['logged_in'] = true;

                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $user['id'],
                            'name' => $user['name'],
                            'email' => $user['email'],
                            'address' => $user['address'],
                            'phone' => $user['phone'],
                            'birthdate' => $user['birthdate']
                        ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'address' => $_SESSION['user_address'],
            'phone' => $_SESSION['user_phone'] ?? '',
            'birthdate' => $_SESSION['user_birthdate'] ?? ''
        ];
    }

    /**
     * Check if email exists
     */
    private function emailExists($email)
    {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $name, $email, $address, $phone = '', $birthdate = '')
    {
        try {
            // Check if email is being changed and if it already exists
            if ($email !== $_SESSION['user_email'] && $this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            $sql = "UPDATE users SET name = ?, email = ?, address = ?, phone = ?, birthdate = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$name, $email, $address, $phone, $birthdate, $userId]);

            if ($result) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_address'] = $address;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['user_birthdate'] = $birthdate;

                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Profile update failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$hashedPassword, $userId]);

            if ($result) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Password change failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
