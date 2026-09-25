<?php

/**
 * Booking data access — create, list, cancel bookings.
 */

require_once __DIR__ . '/../config/database.php';

class Bookings
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function allDestinations(): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->query(
                "SELECT id, slug, name, country, region, description, image_url,
                        price_from, duration_days, group_size, rating
                 FROM destinations
                 WHERE is_active = 1
                 ORDER BY rating DESC, name ASC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[TourBan] Destinations query error');
            return [];
        }
    }

    public function findDestinationBySlug(string $slug): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, slug, name, country, region, description, image_url,
                        price_from, duration_days, group_size, rating
                 FROM destinations WHERE slug = ? AND is_active = 1 LIMIT 1"
            );
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Create a booking with one destination line item.
     * Returns ['success'=>bool, 'booking'=>?, 'message'=>string]
     */
    public function create(
        int $userId,
        int $destinationId,
        string $travelDate,
        int $travelers,
        string $specialRequests = ''
    ): array {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

        if ($travelers < 1 || $travelers > 20) {
            return ['success' => false, 'message' => 'Travelers must be between 1 and 20'];
        }

        if ($travelDate === '' || strtotime($travelDate) === false) {
            return ['success' => false, 'message' => 'Valid travel date is required'];
        }

        if (strtotime($travelDate) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Travel date cannot be in the past'];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, price_from FROM destinations WHERE id = ? AND is_active = 1"
            );
            $stmt->execute([$destinationId]);
            $dest = $stmt->fetch();

            if (!$dest) {
                return ['success' => false, 'message' => 'Destination not found'];
            }

            $unit = (float) $dest['price_from'];
            $total = $unit * $travelers;
            $ref = 'TB-' . strtoupper(bin2hex(random_bytes(4)));

            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO bookings (user_id, booking_ref, status, travel_date, travelers, special_requests, total_amount)
                 VALUES (?, ?, 'pending', ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                $ref,
                $travelDate,
                $travelers,
                mb_substr($specialRequests, 0, 1000),
                $total
            ]);

            $bookingId = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO booking_items (booking_id, destination_id, destination_name, unit_price, quantity)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$bookingId, $destinationId, $dest['name'], $unit, $travelers]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Booking confirmed',
                'booking' => [
                    'id' => $bookingId,
                    'booking_ref' => $ref,
                    'status' => 'pending',
                    'destination' => $dest['name'],
                    'travel_date' => $travelDate,
                    'travelers' => $travelers,
                    'total_amount' => number_format($total, 2, '.', '')
                ]
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[TourBan] Booking create error');
            return ['success' => false, 'message' => 'Could not create booking. Please try again.'];
        }
    }

    public function listForUser(int $userId): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT b.id, b.booking_ref, b.status, b.travel_date, b.travelers,
                        b.total_amount, b.special_requests, b.created_at,
                        GROUP_CONCAT(bi.destination_name SEPARATOR ', ') AS destinations
                 FROM bookings b
                 LEFT JOIN booking_items bi ON bi.booking_id = b.id
                 WHERE b.user_id = ?
                 GROUP BY b.id
                 ORDER BY b.created_at DESC"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[TourBan] Booking list error');
            return [];
        }
    }

    public function findForUser(int $bookingId, int $userId): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1"
            );
            $stmt->execute([$bookingId, $userId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Look up a booking by its public reference (e.g. TB-1A2B3C4D).
     * Ownership must be checked by the caller against user_id.
     */
    public function findByRef(string $bookingRef): ?array
    {
        if (!$this->db || $bookingRef === '') {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM bookings WHERE booking_ref = ? LIMIT 1"
            );
            $stmt->execute([$bookingRef]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Cancel a booking that is not already cancelled or completed.
     */
    public function cancel(int $bookingId, int $userId): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Service temporarily unavailable'];
        }

        try {
            $booking = $this->findForUser($bookingId, $userId);

            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }

            if (in_array($booking['status'], ['cancelled', 'completed'], true)) {
                return ['success' => false, 'message' => 'This booking cannot be cancelled'];
            }

            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$bookingId, $userId]);

            return ['success' => true, 'message' => 'Booking cancelled'];
        } catch (PDOException $e) {
            error_log('[TourBan] Booking cancel error');
            return ['success' => false, 'message' => 'Could not cancel booking'];
        }
    }

    public function countForUser(int $userId): int
    {
        if (!$this->db) {
            return 0;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS c FROM bookings WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            return (int) ($row['c'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /** Line items for a booking (payment summary display). */
    public function itemsForBooking(int $bookingId): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT destination_name, unit_price, quantity
                 FROM booking_items WHERE booking_id = ? ORDER BY id ASC"
            );
            $stmt->execute([$bookingId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
