<?php

/**
 * List bookings for current user
 * GET
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/bookings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error(405, 'Method not allowed');
}

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        json_error(401, 'Please sign in');
    }

    $user = $auth->getCurrentUser();
    $bookings = new Bookings();
    $list = $bookings->listForUser((int) $user['id']);

    echo json_encode([
        'success' => true,
        'bookings' => array_map(static function (array $b): array {
            return [
                'id' => (int) $b['id'],
                'booking_ref' => $b['booking_ref'],
                'status' => $b['status'],
                'destinations' => $b['destinations'] ?? '',
                'travel_date' => $b['travel_date'],
                'travelers' => (int) $b['travelers'],
                'total_amount' => (float) $b['total_amount'],
                'created_at' => $b['created_at']
            ];
        }, $list)
    ]);
} catch (Exception $e) {
    error_log('[TourBan] List bookings error');
    json_error(500, 'Server error. Please try again later.');
}
