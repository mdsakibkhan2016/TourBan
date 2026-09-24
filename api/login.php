<?php

/**
 * Login API Endpoint
 * Handles user login requests
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_csrf();

// Throttle brute-force attempts (5 failures / 5 minutes per session)
if (!login_rate_limit_check()) {
    json_error(429, 'Too many login attempts. Please wait a few minutes and try again.');
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        // Fallback to form data
        $input = $_POST;
    }

    // Validate required fields
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }

    $email = trim($input['email']);
    $password = $input['password'];
    $rememberMe = isset($input['rememberMe']) ? (bool)$input['rememberMe'] : false;

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Validate password
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit;
    }

    // Attempt login
    $auth = new Auth();
    $result = $auth->login($email, $password);

    if ($result['success']) {
        login_rate_limit_reset();

        // Persistent remember-me (DB-backed hashed token)
        if ($rememberMe && isset($result['user']['id'])) {
            $token = $auth->createRememberToken((int) $result['user']['id']);
            if ($token !== null) {
                setcookie('remember_me', $token, [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
        }

        http_response_code(200);
        echo json_encode($result);
    } elseif (!empty($result['requires_verification'])) {
        http_response_code(403);
        echo json_encode($result);
    } else {
        login_rate_limit_fail();
        http_response_code(401);
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log('[TourBan] Login API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
