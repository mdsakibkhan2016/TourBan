<?php

/**
 * Admin booking management — list, filter, status updates.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$adminUser = require_admin();
$db = (new Database())->getConnection();

const ADMIN_BOOKING_STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

// ---------------- POST actions (CSRF-protected) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_post();

    $action = (string) ($_POST['action'] ?? '');

    if (!$db) {
        admin_flash('error', 'Database unavailable. Try again.');
        admin_redirect('admin/bookings.php');
    }

    if ($action === 'set_status') {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if ($bookingId < 1 || !in_array($status, ADMIN_BOOKING_STATUSES, true)) {
            admin_flash('error', 'Invalid booking or status.');
            admin_redirect('admin/bookings.php');
        }

        $stmt = $db->prepare('SELECT id, status FROM bookings WHERE id = ?');
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            admin_flash('error', 'Booking not found.');
        } elseif ($booking['status'] === $status) {
            admin_flash('error', 'Booking already has that status.');
        } else {
            $db->prepare('UPDATE bookings SET status = ? WHERE id = ?')
                ->execute([$status, $bookingId]);

            // Email notification hook (sends when SMTP is configured).
            require_once __DIR__ . '/../includes/mailer.php';
            if (function_exists('send_booking_status_notification')) {
                send_booking_status_notification($bookingId, $status);
            }

            admin_flash('success', 'Booking status updated to ' . $status . '.');
        }
    } else {
        admin_flash('error', 'Unknown action.');
    }

    // Preserve filters after redirect.
    $back = 'admin/bookings.php';
    $keep = ['status', 'from', 'to', 'destination', 'page'];
    $qs = [];
    foreach ($keep as $k) {
        if (isset($_POST['filter_' . $k]) && $_POST['filter_' . $k] !== '') {
            $qs[$k] = (string) $_POST['filter_' . $k];
        }
    }
    if ($qs) {
        $back .= '?' . http_build_query($qs);
    }
    admin_redirect($back);
}

// ---------------- GET state ----------------
$fStatus = (string) ($_GET['status'] ?? '');
$fFrom = (string) ($_GET['from'] ?? '');
$fTo = (string) ($_GET['to'] ?? '');
$fDestination = (int) ($_GET['destination'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

if ($fFrom !== '' && strtotime($fFrom) === false) {
    $fFrom = '';
}
if ($fTo !== '' && strtotime($fTo) === false) {
    $fTo = '';
}

$where = [];
$params = [];

if ($fStatus !== '' && in_array($fStatus, ADMIN_BOOKING_STATUSES, true)) {
    $where[] = 'b.status = ?';
    $params[] = $fStatus;
}
if ($fFrom !== '') {
    $where[] = 'b.created_at >= ?';
    $params[] = $fFrom . ' 00:00:00';
}
if ($fTo !== '') {
    $where[] = 'b.created_at <= ?';
    $params[] = $fTo . ' 23:59:59';
}
if ($fDestination > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM booking_items bx WHERE bx.booking_id = b.id AND bx.destination_id = ?)';
    $params[] = $fDestination;
}
$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

$bookings = [];
$totalRows = 0;
$filterDestinations = [];

if ($db) {
    try {
        $countStmt = $db->prepare('SELECT COUNT(*) FROM bookings b' . $whereSql);
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT b.id, b.booking_ref, b.status, b.travel_date, b.travelers, b.total_amount,
                    b.special_requests, b.created_at,
                    u.id AS user_id, u.name AS user_name, u.email AS user_email,
                    GROUP_CONCAT(bi.destination_name SEPARATOR ', ') AS destinations
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN booking_items bi ON bi.booking_id = b.id" .
            $whereSql .
            ' GROUP BY b.id ORDER BY b.created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
        );
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

        $filterDestinations = $db->query(
            'SELECT d.id, d.name FROM destinations d
             JOIN booking_items bi ON bi.destination_id = d.id
             GROUP BY d.id ORDER BY d.name'
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('[TourBan] Admin bookings query error: ' . $e->getMessage());
    }
}

$pages = max(1, (int) ceil($totalRows / $perPage));
$filterQs = array_filter([
    'status' => $fStatus,
    'from' => $fFrom,
    'to' => $fTo,
    'destination' => $fDestination ?: '',
]);

$adminPageTitle = 'Bookings';
$adminNavKey = 'bookings.php';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Booking management</h4>
        <p class="text-muted mb-0"><?php echo (int) $totalRows; ?> booking<?php echo $totalRows === 1 ? '' : 's'; ?> found.</p>
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span><i class="fas fa-filter me-2 text-primary"></i>Filters</span>
        <?php if ($filterQs): ?>
            <a href="<?php echo admin_e(admin_url('admin/bookings.php')); ?>" class="btn btn-sm btn-outline-secondary">Clear all</a>
        <?php endif; ?>
    </div>
    <div class="admin-card-body">
        <form method="get" action="<?php echo admin_e(admin_url('admin/bookings.php')); ?>" class="admin-filters">
            <div>
                <label class="admin-form-label" for="f-status">Status</label>
                <select class="form-select" id="f-status" name="status">
                    <option value="">Any status</option>
                    <?php foreach (ADMIN_BOOKING_STATUSES as $s): ?>
                        <option value="<?php echo admin_e($s); ?>" <?php echo $fStatus === $s ? 'selected' : ''; ?>>
                            <?php echo admin_e(ucfirst($s)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="admin-form-label" for="f-from">From date</label>
                <input class="form-control" type="date" id="f-from" name="from" value="<?php echo admin_e($fFrom); ?>" />
            </div>
            <div>
                <label class="admin-form-label" for="f-to">To date</label>
                <input class="form-control" type="date" id="f-to" name="to" value="<?php echo admin_e($fTo); ?>" />
            </div>
            <div>
                <label class="admin-form-label" for="f-destination">Destination</label>
                <select class="form-select" id="f-destination" name="destination">
                    <option value="">All destinations</option>
                    <?php foreach ($filterDestinations as $fd): ?>
                        <option value="<?php echo (int) $fd['id']; ?>" <?php echo $fDestination === (int) $fd['id'] ? 'selected' : ''; ?>>
                            <?php echo admin_e($fd['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary">
                    <i class="fas fa-magnifying-glass me-1"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

<div class="admin-card">
    <?php if (!$bookings): ?>
        <div class="admin-empty">
            <i class="fas fa-inbox"></i>
            No bookings match your filters.
        </div>
    <?php else: ?>
        <div class="table-responsive-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>User</th>
                        <th>Destination</th>
                        <th>Travel date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">#<?php echo (int) $b['id']; ?></div>
                                <div class="small text-muted"><?php echo admin_e($b['booking_ref']); ?></div>
                                <div class="small text-muted">
                                    Booked <?php echo admin_e(date('M j, Y', strtotime($b['created_at']))); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo admin_e($b['user_name']); ?>
                                <div class="small text-muted"><?php echo admin_e($b['user_email']); ?></div>
                            </td>
                            <td>
                                <?php echo admin_e($b['destinations'] ?: '—'); ?>
                                <div class="small text-muted"><?php echo (int) $b['travelers']; ?> traveler(s)</div>
                            </td>
                            <td class="text-nowrap">
                                <?php echo $b['travel_date'] ? admin_e(date('M j, Y', strtotime($b['travel_date']))) : '—'; ?>
                            </td>
                            <td class="text-nowrap">$<?php echo admin_e(number_format((float) $b['total_amount'], 2)); ?></td>
                            <td>
                                <span class="admin-badge status-<?php echo admin_e($b['status']); ?>">
                                    <?php echo admin_e(ucfirst($b['status'])); ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <form method="post" class="admin-inline-form"
                                    action="<?php echo admin_e(admin_url('admin/bookings.php')); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                                    <input type="hidden" name="action" value="set_status" />
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>" />
                                    <?php foreach ($filterQs as $fk => $fv): ?>
                                        <input type="hidden" name="filter_<?php echo admin_e($fk); ?>" value="<?php echo admin_e($fv); ?>" />
                                    <?php endforeach; ?>
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto"
                                        aria-label="New status">
                                        <?php foreach (ADMIN_BOOKING_STATUSES as $s): ?>
                                            <option value="<?php echo admin_e($s); ?>" <?php echo $b['status'] === $s ? 'selected' : ''; ?>>
                                                <?php echo admin_e(ucfirst($s)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-admin-primary"
                                        onclick="return confirm('Update booking <?php echo admin_e($b['booking_ref']); ?> status?');">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="admin-card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small text-muted">Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?></span>
                <div class="btn-group">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-sm btn-outline-primary"
                            href="<?php echo admin_e(admin_url('admin/bookings.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1])))); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a class="btn btn-sm btn-outline-primary"
                            href="<?php echo admin_e(admin_url('admin/bookings.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1])))); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
