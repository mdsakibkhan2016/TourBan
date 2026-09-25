<?php

/**
 * Verify email OTP endpoint
 * POST { email, code }
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

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error(400, 'Invalid email format');
    }

    if (!preg_match('/^\d{6}$/', $code)) {
        json_error(400, 'Enter the 6-digit code from your email');
    }

    // Brute-force protection: 10 wrong codes per email every 15 minutes
    $codeKey = 'otpcode:' . strtolower($email);
    if (rate_limit_exceeded($codeKey, 10, 900)) {
        json_error(429, 'Too many attempts. Please wait a few minutes and request a new code.');
    }

    $auth = new Auth();

    if (!$auth->verifyOtp($email, $code, 'registration')) {
        rate_limit_record($codeKey, 10, 900);
        json_error(400, 'Invalid or expired code. Please try again.');
    }

    rate_limit_clear($codeKey);

    if (!$auth->markVerified($email)) {
        json_error(500, 'Could not activate account. Please contact support.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Email verified. You can now sign in.'
    ]);
} catch (Exception $e) {
    error_log('[TourBan] Verify OTP error');
    json_error(500, 'Server error. Please try again later.');
}
