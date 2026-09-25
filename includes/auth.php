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
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

        try {
            // Check if email already exists
            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user (unverified until OTP confirmation)
            $sql = "INSERT INTO users (name, email, password, address, is_verified) VALUES (?, ?, ?, ?, 0)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$name, $email, $hashedPassword, $address]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => (int) $this->db->lastInsertId(),
                    'email' => $email
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            error_log('[TourBan] Register error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed due to a server error'];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password)
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

        try {
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();

                if (password_verify($password, $user['password'])) {
                    // Admin deactivation (column added by migrations.sql)
                    if (array_key_exists('is_active', $user) && (int) $user['is_active'] !== 1) {
                        return [
                            'success' => false,
                            'deactivated' => true,
                            'message' => 'Your account has been deactivated. Please contact support.'
                        ];
                    }

                    // Start session if not already started
                    secure_session_start();
                    // Prevent session fixation
                    session_regenerate_id(true);

                    // Require email verification when the column exists
                    if (array_key_exists('is_verified', $user) && (int) $user['is_verified'] !== 1) {
                        return [
                            'success' => false,
                            'requires_verification' => true,
                            'message' => 'Please verify your email before signing in.',
                            'email' => $user['email']
                        ];
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_address'] = $user['address'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_birthdate'] = $user['birthdate'];
                    $_SESSION['user_role'] = $user['role'] ?? 'user';
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
                    return ['success' => false, 'message' => 'Invalid email or password'];
                }
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
        } catch (PDOException $e) {
            error_log('[TourBan] Login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed due to a server error'];
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        secure_session_start();

        // Remove session data and the session cookie itself
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 4200,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        secure_session_start();

        // Auto-login via remember-me cookie when session is empty
        if (empty($_SESSION['logged_in']) && !empty($_COOKIE['remember_me'])) {
            $this->loginWithRememberToken((string) $_COOKIE['remember_me']);
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
            'birthdate' => $_SESSION['user_birthdate'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    /**
     * Check if email exists
     */
    private function emailExists($email)
    {
        if (!$this->db) {
            return false;
        }

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
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

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
            error_log('[TourBan] Profile update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Profile update failed due to a server error'];
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

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
            error_log('[TourBan] Password change error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed due to a server error'];
        }
    }

    // ------------------------------------------------------------------
    // Email OTP verification
    // ------------------------------------------------------------------

    /**
     * Create a 6-digit OTP for a user. Returns ['success','code'] (code only for caller to email).
     */
    public function createOtp(int $userId, string $email, string $purpose = 'registration'): ?string
    {
        if (!$this->db) {
            return null;
        }

        try {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Invalidate previous unused codes for same purpose
            $stmt = $this->db->prepare(
                "UPDATE otp_verifications SET used_at = NOW()
                 WHERE user_id = ? AND purpose = ? AND used_at IS NULL"
            );
            $stmt->execute([$userId, $purpose]);

            // Use MySQL NOW() so expiry matches verify queries regardless of PHP timezone
            $stmt = $this->db->prepare(
                "INSERT INTO otp_verifications (user_id, email, code, purpose, expires_at)
                 VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))"
            );
            $stmt->execute([$userId, $email, $code, $purpose]);

            return $code;
        } catch (PDOException $e) {
            error_log('[TourBan] OTP create error');
            return null;
        }
    }

    /**
     * Verify an OTP code. Returns true when valid, unused and not expired.
     */
    public function verifyOtp(string $email, string $code, string $purpose = 'registration'): bool
    {
        if (!$this->db || $code === '') {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, user_id FROM otp_verifications
                 WHERE email = ? AND code = ? AND purpose = ? AND used_at IS NULL
                   AND expires_at > NOW()
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$email, $code, $purpose]);
            $row = $stmt->fetch();

            if (!$row) {
                return false;
            }

            $stmt = $this->db->prepare("UPDATE otp_verifications SET used_at = NOW() WHERE id = ?");
            $stmt->execute([$row['id']]);

            return true;
        } catch (PDOException $e) {
            error_log('[TourBan] OTP verify error');
            return false;
        }
    }

    /**
     * Mark a user's email as verified.
     */
    public function markVerified(string $email): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
            return $stmt->execute([$email]);
        } catch (PDOException $e) {
            error_log('[TourBan] Verify user error');
            return false;
        }
    }

    /**
     * Find user id + verified flag by email.
     */
    public function findByEmail(string $email): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, email, is_verified FROM users WHERE email = ?"
            );
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Password reset (OTP based)
    // ------------------------------------------------------------------

    /**
     * Start forgot-password flow. Returns OTP code when a user exists (null otherwise).
     */
    public function startPasswordReset(string $email): ?string
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        return $this->createOtp((int) $user['id'], $email, 'password_reset');
    }

    /**
     * Complete password reset with OTP + new password.
     */
    public function completePasswordReset(string $email, string $code, string $newPassword): bool
    {
        if (strlen($newPassword) < 6) {
            return false;
        }

        if (!$this->verifyOtp($email, $code, 'password_reset')) {
            return false;
        }

        if (!$this->db) {
            return false;
        }

        try {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE email = ?");
            return $stmt->execute([$hashed, $email]);
        } catch (PDOException $e) {
            error_log('[TourBan] Password reset error');
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Persistent remember-me tokens (DB-backed, hashed)
    // ------------------------------------------------------------------

    /**
     * Issue a remember-me cookie value for the user.
     */
    public function createRememberToken(int $userId): ?string
    {
        if (!$this->db) {
            return null;
        }

        try {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);

            // One active token set per user on new login
            $this->db->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$userId]);

            $stmt = $this->db->prepare(
                "INSERT INTO remember_tokens (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))"
            );
            $stmt->execute([$userId, $hash]);

            return $token;
        } catch (PDOException $e) {
            error_log('[TourBan] Remember token error');
            return null;
        }
    }

    /**
     * Log the user in from a raw remember-me cookie token.
     */
    public function loginWithRememberToken(string $token): bool
    {
        if (!$this->db || $token === '') {
            return false;
        }

        try {
            $hash = hash('sha256', $token);
            $stmt = $this->db->prepare(
                "SELECT u.*
                 FROM remember_tokens rt
                 JOIN users u ON u.id = rt.user_id
                 WHERE rt.token_hash = ? AND rt.expires_at > NOW() LIMIT 1"
            );
            $stmt->execute([$hash]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            if (array_key_exists('is_verified', $user) && (int) $user['is_verified'] !== 1) {
                return false;
            }

            if (array_key_exists('is_active', $user) && (int) $user['is_active'] !== 1) {
                return false;
            }

            secure_session_start();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_address'] = $user['address'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_birthdate'] = $user['birthdate'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['logged_in'] = true;

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete all remember tokens for a user (used on logout).
     */
    public function clearRememberTokens(int $userId): void
    {
        if (!$this->db) {
            return;
        }

        try {
            $this->db->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$userId]);
        } catch (PDOException $e) {
            // silent
        }
    }
}
