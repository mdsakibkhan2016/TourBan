<?php

/**
 * Payment API
 * POST { booking_ref, payment_method }
 *
 * Creates/reuses the booking's pending payment, runs it through the
 * selected gateway, and confirms the booking on success.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../includes/payments.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method not allowed');
}

require_csrf();

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        json_error(401, 'Please sign in to pay');
    }

    $user = $auth->getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $bookingRef = trim((string) ($input['booking_ref'] ?? ''));
    $method = strtolower(trim((string) ($input['payment_method'] ?? '')));

    if ($bookingRef === '' || !preg_match('/^TB-[A-F0-9]{8}$/i', $bookingRef)) {
        json_error(400, 'Valid booking reference is required');
    }

    if (!in_array($method, Payments::METHODS, true)) {
        json_error(400, 'Invalid payment method');
    }

    $bookings = new Bookings();
    $booking = $bookings->findByRef(strtoupper($bookingRef));

    if (!$booking) {
        json_error(404, 'Booking not found');
    }

    if ((int) $booking['user_id'] !== (int) $user['id']) {
        json_error(403, 'You do not have access to this booking');
    }

    if ($booking['status'] === 'cancelled') {
        json_error(409, 'This booking has been cancelled');
    }

    if ($booking['status'] === 'completed') {
        json_error(409, 'This booking is already completed');
    }

    $payments = new Payments();

    $payment = $payments->findByBooking((int) $booking['id']);
    if ($payment && ($payment['payment_status'] ?? '') === 'paid') {
        json_error(409, 'This booking has already been paid');
    }

    // Amount always comes from the booking row — never client input
    $payment = $payments->getOrCreatePending((int) $booking['id'], $method);

    if (!$payment) {
        json_error(500, 'Could not prepare payment');
    }

    $gateway = payment_gateway($method);
    $result = $gateway->charge($payment, $booking);

    switch ($result['result']) {
        case 'paid':
            $outcome = $payments->markPaid(
                (int) $payment['id'],
                (string) $result['transaction_id'],
                $method
            );

            if (!$outcome['success']) {
                json_error(500, $outcome['message']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Payment completed',
                'payment' => [
                    'transaction_id' => $outcome['payment']['transaction_id'],
                    'payment_method' => $outcome['payment']['payment_method'],
                    'payment_status' => $outcome['payment']['payment_status'],
                    'amount' => $outcome['payment']['amount'],
                ],
                'booking' => [
                    'booking_ref' => $booking['booking_ref'],
                    'status' => 'confirmed',
                ],
            ]);
            break;

        case 'not_configured':
            $payments->markFailed((int) $payment['id']);
            json_error(501, $gateway->label() . ' payments are not available yet');
            break;

        case 'not_implemented':
            $payments->markFailed((int) $payment['id']);
            json_error(501, $result['message'] ?? 'Payment method not enabled yet');
            break;

        case 'failed':
        default:
            $payments->markFailed((int) $payment['id']);
            json_error(502, $result['message'] ?? 'Payment failed. Please try again or choose another method.');
            break;
    }
} catch (Exception $e) {
    error_log('[TourBan] Payment API error');
    json_error(500, 'Server error. Please try again later.');
}
