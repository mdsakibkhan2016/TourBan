<?php

/**
 * Create booking API
 * POST { destination_id, travel_date, travelers, special_requests? }
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
        json_error(401, 'Please sign in to create a booking');
    }

    $user = $auth->getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $destinationId = (int) ($input['destination_id'] ?? 0);
    $travelDate = trim((string) ($input['travel_date'] ?? ''));
    $travelers = (int) ($input['travelers'] ?? 1);
    $special = trim((string) ($input['special_requests'] ?? ''));

    if ($destinationId < 1) {
        json_error(400, 'Please choose a destination');
    }

    $bookings = new Bookings();
    $result = $bookings->create((int) $user['id'], $destinationId, $travelDate, $travelers, $special);

    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        json_error(400, $result['message']);
    }
} catch (Exception $e) {
    error_log('[TourBan] Create booking error');
    json_error(500, 'Server error. Please try again later.');
}
