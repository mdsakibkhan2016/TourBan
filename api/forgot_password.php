<?php

/**
 * Forgot password — send reset OTP
 * POST { email }
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

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error(400, 'Invalid email format');
    }

    $auth = new Auth();
    $otp = $auth->startPasswordReset($email);

    $response = ['success' => true, 'message' => 'If that account exists, a reset code has been sent.'];

    if ($otp !== null) {
        require_once __DIR__ . '/../includes/mailer.php';
        send_app_mail(
            $email,
            'Your TourBan password reset code',
            "Your password reset code is: {$otp}\n\nIt expires in 15 minutes. If you did not request this, ignore this email.\n\n— TourBan"
        );
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log('[TourBan] Forgot password error');
    json_error(500, 'Server error. Please try again later.');
}
