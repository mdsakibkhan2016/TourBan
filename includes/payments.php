<?php

/**
 * Payment architecture — data model + gateway-ready structure.
 *
 * Flow: booking created -> payment pending -> gateway charge -> paid -> booking confirmed.
 *
 * Gateways:
 *   sandbox     — simulated charge (works without credentials; default)
 *   sslcommerz  — ready for SSLCommerz credentials
 *   stripe      — ready for Stripe credentials
 *   paypal      — ready for PayPal credentials
 *
 * Real gateways never charge without credentials; they return a
 * "not configured" result until the matching env vars exist.
 */

require_once __DIR__ . '/../config/database.php';

// ------------------------------------------------------------------
// Data access
// ------------------------------------------------------------------

class Payments
{
    public const METHODS = ['sandbox', 'sslcommerz', 'stripe', 'paypal'];
    public const STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get (or lazily create) the pending payment for a booking.
     * Amount always comes from the booking — never from client input.
     */
    public function getOrCreatePending(int $bookingId, string $method): ?array
    {
        if (!$this->db || !in_array($method, self::METHODS, true)) {
            return null;
        }

        try {
            $existing = $this->findByBooking($bookingId);
            if ($existing) {
                return $existing;
            }

            $stmt = $this->db->prepare('SELECT total_amount FROM bookings WHERE id = ? LIMIT 1');
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                return null;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO payments (booking_id, transaction_id, amount, payment_method, payment_status)
                 VALUES (?, ?, ?, ?, \'pending\')'
            );
            $stmt->execute([$bookingId, '', (float) $booking['total_amount'], $method]);

            return $this->find((int) $this->db->lastInsertId());
        } catch (PDOException $e) {
            error_log('[TourBan] Payment create error');
            return null;
        }
    }

    public function find(int $paymentId): ?array
    {
        if (!$this->db) {
            return null;
        }
        try {
            $stmt = $this->db->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
            $stmt->execute([$paymentId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function findByBooking(int $bookingId): ?array
    {
        if (!$this->db) {
            return null;
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute([$bookingId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Mark a payment paid and confirm its booking in one transaction.
     * Idempotent: refuses when the payment is not pending.
     */
    public function markPaid(int $paymentId, string $transactionId, string $method): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

        try {
            $payment = $this->find($paymentId);
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            if ($payment['payment_status'] === 'paid') {
                return ['success' => false, 'message' => 'Payment already completed'];
            }
            if (!in_array($payment['payment_status'], ['pending', 'failed'], true)) {
                return ['success' => false, 'message' => 'Payment cannot be completed in its current state'];
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE payments SET payment_status = 'paid', transaction_id = ?, payment_method = ?
                 WHERE id = ? AND payment_status <> 'paid'"
            );
            $stmt->execute([
                mb_substr($transactionId, 0, 100),
                in_array($method, self::METHODS, true) ? $method : $payment['payment_method'],
                $paymentId,
            ]);

            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'confirmed'
                 WHERE id = ? AND status IN ('pending', 'confirmed')"
            );
            $stmt->execute([(int) $payment['booking_id']]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Payment completed',
                'payment' => $this->find($paymentId),
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[TourBan] Payment markPaid error');
            return ['success' => false, 'message' => 'Could not record payment'];
        }
    }

    public function markFailed(int $paymentId): void
    {
        if (!$this->db) {
            return;
        }
        try {
            $this->db->prepare(
                "UPDATE payments SET payment_status = 'failed' WHERE id = ? AND payment_status = 'pending'"
            )->execute([$paymentId]);
        } catch (PDOException $e) {
            error_log('[TourBan] Payment markFailed error');
        }
    }
}

// ------------------------------------------------------------------
// Gateway abstraction
// ------------------------------------------------------------------

abstract class PaymentGateway
{
    /** Machine name (matches payments.payment_method). */
    abstract public function name(): string;

    /** Human label for UI. */
    abstract public function label(): string;

    /** Whether the required credentials exist in the environment. */
    abstract public function isConfigured(): bool;

    /**
     * Attempt to charge a payment.
     *
     * Returns one of:
     *   ['result' => 'paid',     'transaction_id' => '...']
     *   ['result' => 'not_configured']
     *   ['result' => 'not_implemented', 'message' => '...']
     *   ['result' => 'failed',   'message' => '...']
     */
    abstract public function charge(array $payment, array $booking): array;

    protected function bookingRef(array $booking): string
    {
        return (string) ($booking['booking_ref'] ?? '');
    }
}

/** Simulated gateway — default, no credentials required, never contacts a real processor. */
class SandboxGateway extends PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function label(): string
    {
        return 'Test payment (sandbox)';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function charge(array $payment, array $booking): array
    {
        return [
            'result' => 'paid',
            'transaction_id' => 'SANDBOX-' . strtoupper(bin2hex(random_bytes(6))),
        ];
    }
}

/**
 * SSLCommerz gateway skeleton (Bangladesh: Visa/Master/bKash/Nagad/Rocket).
 * Activation: set SSLCOMMERZ_STORE_ID + SSLCOMMERZ_STORE_PASSWORD, then
 * implement the Session API call in charge().
 */
class SSLCommerzGateway extends PaymentGateway
{
    public function name(): string
    {
        return 'sslcommerz';
    }

    public function label(): string
    {
        return 'SSLCommerz';
    }

    public function isConfigured(): bool
    {
        return trim((string) env('SSLCOMMERZ_STORE_ID', '')) !== ''
            && trim((string) env('SSLCOMMERZ_STORE_PASSWORD', '')) !== '';
    }

    public function charge(array $payment, array $booking): array
    {
        if (!$this->isConfigured()) {
            return ['result' => 'not_configured'];
        }

        // Gateway-ready structure: POST https://securepay.sslcommerz.com/gwprocess/v4/api.php
        // with post_body {store_id, store_passwd, total_amount, currency, tran_id, success_url, fail_url, cancel_url}
        // then redirect the buyer to the returned GatewayPageURL and confirm via Validate API.
        return [
            'result' => 'not_implemented',
            'message' => 'SSLCommerz credentials detected — API handler not enabled yet.',
        ];
    }
}

/** Stripe gateway skeleton (cards, Checkout Session flow). */
class StripeGateway extends PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return trim((string) env('STRIPE_SECRET_KEY', '')) !== '';
    }

    public function charge(array $payment, array $booking): array
    {
        if (!$this->isConfigured()) {
            return ['result' => 'not_configured'];
        }

        // Gateway-ready structure: POST https://api.stripe.com/v1/checkout/sessions
        // with line_items, success_url, cancel_url, then redirect to session.url
        // and confirm via webhook/signature or session retrieval.
        return [
            'result' => 'not_implemented',
            'message' => 'Stripe credentials detected — API handler not enabled yet.',
        ];
    }
}

/** PayPal gateway skeleton (Orders v2 API). */
class PayPalGateway extends PaymentGateway
{
    public function name(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        return trim((string) env('PAYPAL_CLIENT_ID', '')) !== ''
            && trim((string) env('PAYPAL_CLIENT_SECRET', '')) !== '';
    }

    public function charge(array $payment, array $booking): array
    {
        if (!$this->isConfigured()) {
            return ['result' => 'not_configured'];
        }

        // Gateway-ready structure: POST /v2/checkout/orders then /v2/checkout/orders/{id}/capture,
        // redirect buyer to approve link returned in links[] (approve rel).
        return [
            'result' => 'not_implemented',
            'message' => 'PayPal credentials detected — API handler not enabled yet.',
        ];
    }
}

if (!function_exists('payment_gateway')) {
    /** Factory: resolve a gateway by method name (defaults to sandbox). */
    function payment_gateway(string $method): PaymentGateway
    {
        switch ($method) {
            case 'sslcommerz':
                return new SSLCommerzGateway();
            case 'stripe':
                return new StripeGateway();
            case 'paypal':
                return new PayPalGateway();
            default:
                return new SandboxGateway();
        }
    }
}

if (!function_exists('payment_available_methods')) {
    /**
     * Methods for the payment UI: name, label, configured flag.
     * Sandbox is always first; real gateways show as unavailable until configured.
     */
    function payment_available_methods(): array
    {
        $methods = [];
        foreach (Payments::METHODS as $m) {
            $gateway = payment_gateway($m);
            $methods[] = [
                'name' => $m,
                'label' => $gateway->label(),
                'configured' => $gateway->isConfigured(),
            ];
        }
        return $methods;
    }
}
