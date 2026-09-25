<?php

/**
 * Admin layout header. Expects require_admin() already ran.
 * $adminUser = require_admin(); then include this file.
 */

if (!isset($adminUser) || !is_array($adminUser)) {
    // Defense in depth: never render admin chrome without a validated admin.
    require_admin();
}

$adminTitle = $adminPageTitle ?? 'Dashboard';

// Security headers (clickjacking / MIME sniffing / referrer leakage)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

$adminNav = [
    'index.php'        => ['icon' => 'fa-gauge-high',   'label' => 'Dashboard'],
    'users.php'        => ['icon' => 'fa-users',        'label' => 'Users'],
    'destinations.php' => ['icon' => 'fa-map-location', 'label' => 'Destinations'],
    'bookings.php'     => ['icon' => 'fa-ticket',       'label' => 'Bookings'],
    'settings.php'     => ['icon' => 'fa-gear',         'label' => 'Settings'],
];
$adminActive = $adminNavKey ?? 'index.php';
$adminFlash = admin_flash_pull();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo admin_e($adminTitle); ?> | TourBan Admin</title>
    <meta name="robots" content="noindex, nofollow" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo admin_e(asset('assets/css/admin.css')); ?>" />
    <script>
        window.BASE_URL = <?php echo json_encode(BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
        window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
    </script>
</head>

<body>
    <div class="admin-shell">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-brand">
                <i class="fas fa-suitcase-rolling"></i>
                <span>TourBan <em>Admin</em></span>
            </div>
            <nav class="admin-nav">
                <?php foreach ($adminNav as $file => $item): ?>
                    <a href="<?php echo admin_e(admin_url('admin/' . $file)); ?>"
                        class="admin-nav-link<?php echo $adminActive === $file ? ' active' : ''; ?>">
                        <i class="fas <?php echo admin_e($item['icon']); ?>"></i>
                        <span><?php echo admin_e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="admin-sidebar-footer">
                <a href="<?php echo admin_e(admin_url('index.php')); ?>" target="_blank" rel="noopener">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    <span>View site</span>
                </a>
                <a href="<?php echo admin_e(admin_url('dashboard.php')); ?>">
                    <i class="fas fa-user"></i>
                    <span>My dashboard</span>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="admin-main">
            <header class="admin-topbar">
                <button class="admin-burger" id="adminBurger" aria-label="Toggle menu" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-topbar-title"><?php echo admin_e($adminTitle); ?></div>
                <div class="admin-topbar-user dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" type="button">
                        <i class="fas fa-circle-user me-1"></i><?php echo admin_e($adminUser['name']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-item-text small text-muted">
                            <?php echo admin_e($adminUser['email']); ?>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" id="adminLogoutBtn">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="admin-content">
                <?php if (!empty($adminFlash)): ?>
                    <div class="alert alert-<?php echo $adminFlash['type'] === 'error' ? 'danger' : admin_e($adminFlash['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo admin_e($adminFlash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
