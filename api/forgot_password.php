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

    // Email-bombing protection: 6 per account / 40 per IP every 15 minutes
    $mailKey = 'otpmail:email:' . strtolower($email);
    $mailIpKey = 'otpmail:ip:' . client_ip();
    if (rate_limit_exceeded($mailKey, 6, 900) || rate_limit_exceeded($mailIpKey, 40, 900)) {
        json_error(429, 'Too many requests. Please wait a few minutes and try again.');
    }
    rate_limit_record($mailKey, 6, 900);
    rate_limit_record($mailIpKey, 40, 900);

    $auth = new Auth();
    $account = $auth->findByEmail($email);
    $otp = $auth->startPasswordReset($email);

    $response = ['success' => true, 'message' => 'If that account exists, a reset code has been sent.'];

    if ($otp !== null) {
        require_once __DIR__ . '/../includes/mailer.php';
        send_otp_email(
            $email,
            (string) ($account['name'] ?? 'Traveler'),
            $otp,
            'password_reset'
        );
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log('[TourBan] Forgot password error');
    json_error(500, 'Server error. Please try again later.');
}
