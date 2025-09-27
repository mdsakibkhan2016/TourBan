<?php

/**
 * Login API Endpoint
 * Handles user login requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
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
        // Set remember me cookie if requested
        if ($rememberMe) {
            $cookieValue = base64_encode($email . '|' . time());
            setcookie('remember_me', $cookieValue, time() + (30 * 24 * 60 * 60), '/'); // 30 days
        }

        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
