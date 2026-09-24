<?php

/**
 * Cancel booking API
 * POST { booking_id }
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/bookings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method not allowed');
}

require_csrf();

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        json_error(401, 'Please sign in');
    }

    $user = $auth->getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $bookingId = (int) ($input['booking_id'] ?? 0);

    if ($bookingId < 1) {
        json_error(400, 'Booking is required');
    }

    $bookings = new Bookings();
    $result = $bookings->cancel($bookingId, (int) $user['id']);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        json_error(400, $result['message']);
    }
} catch (Exception $e) {
    error_log('[TourBan] Cancel booking error');
    json_error(500, 'Server error. Please try again later.');
}
