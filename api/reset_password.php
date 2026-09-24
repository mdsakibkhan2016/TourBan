<?php

/**
 * Reset password with OTP
 * POST { email, code, password }
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method not allowed');
}

require_csrf();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $email = trim((string) ($input['email'] ?? ''));
    $code = trim((string) ($input['code'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error(400, 'Invalid email format');
    }

    if (!preg_match('/^\d{6}$/', $code)) {
        json_error(400, 'Enter the 6-digit reset code');
    }

    if (strlen($password) < 6) {
        json_error(400, 'Password must be at least 6 characters long');
    }

    $auth = new Auth();

    if (!$auth->completePasswordReset($email, $code, $password)) {
        json_error(400, 'Invalid or expired code, or password is too short.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Password updated. You can now sign in with your new password.'
    ]);
} catch (Exception $e) {
    error_log('[TourBan] Reset password error');
    json_error(500, 'Server error. Please try again later.');
}
