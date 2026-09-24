<?php

/**
 * Admin dashboard — overview statistics and recent activity.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$adminUser = require_admin();

$db = (new Database())->getConnection();

$stats = [
    'users' => 0, 'verified' => 0, 'destinations' => 0,
    'bookings' => 0, 'pending' => 0, 'completed' => 0,
];

$recentBookings = [];
$recentUsers = [];

if ($db) {
    try {
        $stats['users'] = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['verified'] = (int) $db->query('SELECT COUNT(*) FROM users WHERE is_verified = 1')->fetchColumn();
        $stats['destinations'] = (int) $db->query('SELECT COUNT(*) FROM destinations WHERE is_active = 1')->fetchColumn();
        $stats['bookings'] = (int) $db->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
        $stats['pending'] = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
        $stats['completed'] = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();

        $recentBookings = $db->query(
            "SELECT b.id, b.booking_ref, b.status, b.travel_date, b.total_amount, b.created_at,
                    u.name AS user_name, u.email AS user_email,
                    GROUP_CONCAT(bi.destination_name SEPARATOR ', ') AS destinations
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN booking_items bi ON bi.booking_id = b.id
             GROUP BY b.id
             ORDER BY b.created_at DESC
             LIMIT 8"
        )->fetchAll();

        $recentUsers = $db->query(
            'SELECT id, name, email, role, is_verified, created_at
             FROM users ORDER BY created_at DESC LIMIT 8'
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('[TourBan] Admin dashboard query error: ' . $e->getMessage());
    }
}

$adminPageTitle = 'Dashboard';
$adminNavKey = 'index.php';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Welcome back, <?php echo admin_e($adminUser['name']); ?></h4>
        <p class="text-muted mb-0">Here is what is happening on TourBan today.</p>
    </div>
    <a href="<?php echo admin_e(admin_url('admin/bookings.php')); ?>" class="btn btn-admin-primary">
        <i class="fas fa-ticket me-2"></i>Manage bookings
    </a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon blue"><i class="fas fa-users"></i></div>
            <div>
                <div class="admin-stat-number"><?php echo (int) $stats['users']; ?></div>
                <div class="admin-stat-label">Total users</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon green"><i class="fas fa-user-check"></i></div>
            <div>
                <div class="admin-stat-number"><?php echo (int) $stats['verified']; ?></div>
                <div class="admin-stat-label">Verified users</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon purple"><i class="fas fa-map-location-dot"></i></div>
            <div>
                <div class="admin-stat-number"><?php echo (int) $stats['destinations']; ?></div>
                <div class="admin-stat-label">Destinations</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon amber"><i class="fas fa-suitcase-rolling"></i></div>
            <div>
                <div class="admin-stat-number"><?php echo (int) $stats['bookings']; ?></div>
                <div class="admin-stat-label">Total bookings</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon teal"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="admin-stat-number"><?php echo (int) $stats['pending']; ?></div>
                <div class="admin-stat-label">Pending bookings</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon rose"><i class="fas fa-circle-check"></i></div>
            <div>
                <div class="admin-stat-number"><?php echo (int) $stats['completed']; ?></div>
                <div class="admin-stat-label">Completed bookings</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent bookings -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-clock-rotate-left me-2 text-primary"></i>Recent bookings</span>
                <a href="<?php echo admin_e(admin_url('admin/bookings.php')); ?>" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <?php if (!$recentBookings): ?>
                <div class="admin-empty">
                    <i class="fas fa-inbox"></i>
                    No bookings yet.
                </div>
            <?php else: ?>
                <div class="table-responsive-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ref</th>
                                <th>Customer</th>
                                <th>Trip</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo admin_e($b['booking_ref']); ?></td>
                                    <td>
                                        <?php echo admin_e($b['user_name']); ?>
                                        <div class="small text-muted"><?php echo admin_e($b['user_email']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo admin_e($b['destinations'] ?: '—'); ?>
                                        <div class="small text-muted">
                                            <?php echo $b['travel_date'] ? admin_e(date('M j, Y', strtotime($b['travel_date']))) : ''; ?>
                                        </div>
                                    </td>
                                    <td>$<?php echo admin_e(number_format((float) $b['total_amount'], 2)); ?></td>
                                    <td>
                                        <span class="admin-badge status-<?php echo admin_e($b['status']); ?>">
                                            <?php echo admin_e(ucfirst($b['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent users -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-user-plus me-2 text-success"></i>Newest users</span>
                <a href="<?php echo admin_e(admin_url('admin/users.php')); ?>" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <?php if (!$recentUsers): ?>
                <div class="admin-empty">
                    <i class="fas fa-users-slash"></i>
                    No users yet.
                </div>
            <?php else: ?>
                <div class="table-responsive-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>
                                        <?php echo admin_e($u['name']); ?>
                                        <div class="small text-muted"><?php echo admin_e($u['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="admin-badge role-<?php echo admin_e($u['role']); ?>">
                                            <?php echo admin_e(ucfirst($u['role'])); ?>
                                        </span>
                                        <?php if ((int) $u['is_verified'] !== 1): ?>
                                            <span class="admin-badge status-pending">Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo admin_e(date('M j, Y', strtotime($u['created_at']))); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
