<?php

/**
 * Update Profile API Endpoint
 * Handles user profile update requests
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
    // Check if user is logged in
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        // Fallback to form data
        $input = $_POST;
    }

    // Validate required fields
    if (!isset($input['name']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        exit;
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $address = isset($input['address']) ? trim($input['address']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $birthdate = isset($input['birthdate']) ? trim($input['birthdate']) : '';

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

    // Get current user
    $user = $auth->getCurrentUser();
    $userId = $user['id'];

    // Attempt profile update
    $result = $auth->updateProfile($userId, $name, $email, $address, $phone, $birthdate);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user['id'],
                'name' => $name,
                'email' => $email,
                'address' => $address,
                'phone' => $phone,
                'birthdate' => $birthdate
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
