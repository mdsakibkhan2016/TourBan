<?php

/**
 * Registration API Endpoint
 * Handles user registration requests
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
    if (!isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name, email, and password are required']);
        exit;
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $password = $input['password'];
    $address = isset($input['address']) ? trim($input['address']) : '';

    // Validate name
    if (strlen($name) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name must be at least 2 characters long']);
        exit;
    }

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

    // Attempt registration
    $auth = new Auth();
    $result = $auth->register($name, $email, $password, $address);

    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
