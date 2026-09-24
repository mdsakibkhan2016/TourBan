<?php

/**
 * Admin settings — system status, security posture, admin password.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$adminUser = require_admin();
$db = (new Database())->getConnection();

// ---------------- POST: change admin password ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_post();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($new) < 8) {
            admin_flash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            admin_flash('error', 'New passwords do not match.');
        } else {
            require_once __DIR__ . '/../includes/auth.php';
            $auth = new Auth();
            $result = $auth->changePassword((int) $adminUser['id'], $current, $new);
            admin_flash($result['success'] ? 'success' : 'error', $result['message']);
        }
    } else {
        admin_flash('error', 'Unknown action.');
    }

    admin_redirect('admin/settings.php');
}

// ---------------- Status data (never exposes secrets) ----------------
$mailHost = (string) env('MAIL_HOST', '');
$mailPort = (string) env('MAIL_PORT', '');
$smtpConfigured = $mailHost !== '';
$groqConfigured = (string) env('GROQ_API_KEY', '') !== '';
$dbVersion = '—';
$dbOk = false;

if ($db) {
    try {
        $dbVersion = (string) $db->query('SELECT VERSION()')->fetchColumn();
        $dbOk = true;
    } catch (PDOException $e) {
        // keep defaults
    }
}

$counts = ['users' => 0, 'destinations' => 0, 'bookings' => 0, 'payments' => 0];
if ($db) {
    try {
        $counts['users'] = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $counts['destinations'] = (int) $db->query('SELECT COUNT(*) FROM destinations')->fetchColumn();
        $counts['bookings'] = (int) $db->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
        if ($db->query("SHOW TABLES LIKE 'payments'")->fetch()) {
            $counts['payments'] = (int) $db->query('SELECT COUNT(*) FROM payments')->fetchColumn();
        }
    } catch (PDOException $e) {
        // ignore
    }
}

$adminPageTitle = 'Settings';
$adminNavKey = 'settings.php';
include __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">System settings</h4>
    <p class="text-muted mb-0">Environment status and administrator account.</p>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span><i class="fas fa-server me-2 text-primary"></i>Environment status</span>
            </div>
            <div class="admin-card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted">App URL</td>
                            <td class="fw-semibold text-break"><?php echo admin_e(BASE_URL ?: '(not set)'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Environment</td>
                            <td class="fw-semibold"><?php echo admin_e(env('APP_ENV', 'production')); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Debug mode</td>
                            <td>
                                <?php if (APP_DEBUG): ?>
                                    <span class="admin-badge status-pending">ON (disable in production)</span>
                                <?php else: ?>
                                    <span class="admin-badge status-active">Off</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Database</td>
                            <td>
                                <?php if ($dbOk): ?>
                                    <span class="admin-badge status-active">Connected</span>
                                    <span class="small text-muted ms-1"><?php echo admin_e($dbVersion); ?></span>
                                <?php else: ?>
                                    <span class="admin-badge status-inactive">Unavailable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mail transport</td>
                            <td>
                                <?php if ($smtpConfigured): ?>
                                    <span class="admin-badge status-active">SMTP configured</span>
                                    <span class="small text-muted ms-1">
                                        <?php echo admin_e($mailHost); ?><?php echo $mailPort ? ':' . admin_e($mailPort) : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="admin-badge status-pending">Fallback (PHP mail)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Groq AI chatbot</td>
                            <td>
                                <?php if ($groqConfigured): ?>
                                    <span class="admin-badge status-active">Configured</span>
                                <?php else: ?>
                                    <span class="admin-badge status-pending">Not configured</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span><i class="fas fa-database me-2 text-success"></i>Records</span>
            </div>
            <div class="admin-card-body">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <div class="admin-stat-number"><?php echo (int) $counts['users']; ?></div>
                        <div class="admin-stat-label">Users</div>
                    </div>
                    <div class="col-6">
                        <div class="admin-stat-number"><?php echo (int) $counts['destinations']; ?></div>
                        <div class="admin-stat-label">Destinations</div>
                    </div>
                    <div class="col-6">
                        <div class="admin-stat-number"><?php echo (int) $counts['bookings']; ?></div>
                        <div class="admin-stat-label">Bookings</div>
                    </div>
                    <div class="col-6">
                        <div class="admin-stat-number"><?php echo (int) $counts['payments']; ?></div>
                        <div class="admin-stat-label">Payments</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span><i class="fas fa-circle-user me-2 text-primary"></i>Administrator account</span>
            </div>
            <div class="admin-card-body">
                <div class="mb-3">
                    <div class="text-muted small">Signed in as</div>
                    <div class="fw-semibold"><?php echo admin_e($adminUser['name']); ?></div>
                    <div class="small text-muted"><?php echo admin_e($adminUser['email']); ?></div>
                </div>
                <div class="mb-3">
                    <span class="admin-badge role-admin">Administrator</span>
                    <?php if ((int) $adminUser['is_verified'] === 1): ?>
                        <span class="admin-badge status-active">Verified</span>
                    <?php endif; ?>
                </div>
                <div class="text-muted small">
                    Member since <?php echo admin_e(date('M j, Y', strtotime($adminUser['created_at']))); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span><i class="fas fa-key me-2 text-warning"></i>Change password</span>
            </div>
            <div class="admin-card-body">
                <form method="post" action="<?php echo admin_e(admin_url('admin/settings.php')); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                    <input type="hidden" name="action" value="change_password" />

                    <div class="mb-3">
                        <label class="admin-form-label" for="cur-pass">Current password</label>
                        <input class="form-control" type="password" id="cur-pass" name="current_password"
                            autocomplete="current-password" required />
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label" for="new-pass">New password</label>
                        <input class="form-control" type="password" id="new-pass" name="new_password"
                            autocomplete="new-password" minlength="8" required />
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label" for="conf-pass">Confirm new password</label>
                        <input class="form-control" type="password" id="conf-pass" name="confirm_password"
                            autocomplete="new-password" minlength="8" required />
                    </div>
                    <button type="submit" class="btn btn-admin-primary px-4">
                        <i class="fas fa-save me-2"></i>Update password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
