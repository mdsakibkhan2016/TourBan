<?php

/**
 * Resend email verification OTP
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
    $purpose = trim((string) ($input['purpose'] ?? 'registration'));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error(400, 'Invalid email format');
    }

    if (!in_array($purpose, ['registration', 'password_reset'], true)) {
        json_error(400, 'Invalid purpose');
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
    $user = $auth->findByEmail($email);

    // Same generic response whether or not the account exists
    $generic = ['success' => true, 'message' => 'If that account exists, a new code has been sent.'];

    if (!$user) {
        echo json_encode($generic);
        exit;
    }

    if ($purpose === 'registration' && (int) ($user['is_verified'] ?? 0) === 1) {
        echo json_encode(['success' => true, 'message' => 'This email is already verified. Please sign in.']);
        exit;
    }

    $otp = $auth->createOtp((int) $user['id'], $email, $purpose);

    if ($otp !== null) {
        require_once __DIR__ . '/../includes/mailer.php';
        send_otp_email($email, (string) ($user['name'] ?? 'Traveler'), $otp, $purpose);
    }

    echo json_encode($generic);
} catch (Exception $e) {
    error_log('[TourBan] Resend OTP error');
    json_error(500, 'Server error. Please try again later.');
}
