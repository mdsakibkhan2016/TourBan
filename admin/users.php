<?php

/**
 * Admin user management — search, profile details, role, activation.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$adminUser = require_admin();
$db = (new Database())->getConnection();

/** Number of active admins (used to protect the last admin account). */
function admin_count_admins(PDO $db, int $exceptId = 0): int
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id <> ?");
    $stmt->execute([$exceptId]);
    return (int) $stmt->fetchColumn();
}

// ---------------- POST actions (CSRF-protected) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_post();

    $action = (string) ($_POST['action'] ?? '');
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $selfId = (int) $adminUser['id'];

    if (!$db) {
        admin_flash('error', 'Database unavailable. Try again.');
        admin_redirect('admin/users.php');
    }

    if ($action === 'set_role' && $targetId > 0) {
        $role = (string) ($_POST['role'] ?? '');
        if (!in_array($role, ['admin', 'user'], true)) {
            admin_flash('error', 'Invalid role.');
        } elseif ($targetId === $selfId) {
            admin_flash('error', 'You cannot change your own role.');
        } else {
            $current = $db->prepare('SELECT role FROM users WHERE id = ?');
            $current->execute([$targetId]);
            $row = $current->fetch();

            if (!$row) {
                admin_flash('error', 'User not found.');
            } elseif ($row['role'] === 'admin' && $role !== 'admin' && admin_count_admins($db, $targetId) === 0) {
                admin_flash('error', 'Cannot demote the last administrator.');
            } else {
                $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
                $stmt->execute([$role, $targetId]);
                admin_flash('success', 'User role updated to ' . $role . '.');
            }
        }
    } elseif ($action === 'set_status' && $targetId > 0) {
        $isActive = (string) ($_POST['is_active'] ?? '');
        if (!in_array($isActive, ['0', '1'], true)) {
            admin_flash('error', 'Invalid status.');
        } elseif ($targetId === $selfId && $isActive === '0') {
            admin_flash('error', 'You cannot deactivate your own account.');
        } else {
            $current = $db->prepare('SELECT role FROM users WHERE id = ?');
            $current->execute([$targetId]);
            $row = $current->fetch();

            if (!$row) {
                admin_flash('error', 'User not found.');
            } elseif ($row['role'] === 'admin' && $isActive === '0' && admin_count_admins($db, $targetId) === 0) {
                admin_flash('error', 'Cannot deactivate the last administrator.');
            } else {
                $stmt = $db->prepare('UPDATE users SET is_active = ? WHERE id = ?');
                $stmt->execute([(int) $isActive, $targetId]);

                if ($isActive === '0') {
                    // Kill persistent sessions for this account.
                    $db->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$targetId]);
                }

                admin_flash('success', $isActive === '1' ? 'Account activated.' : 'Account deactivated.');
            }
        }
    } else {
        admin_flash('error', 'Unknown action.');
    }

    admin_redirect('admin/users.php');
}

// ---------------- GET state ----------------
$q = trim((string) ($_GET['q'] ?? ''));
$viewId = (int) ($_GET['view'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

$users = [];
$totalRows = 0;
$viewUser = null;

if ($db) {
    try {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM users u" . $whereSql);
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.is_verified, u.is_active, u.created_at,
                    (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count
             FROM users u" . $whereSql .
            " ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        if ($viewId > 0) {
            $stmt = $db->prepare(
                'SELECT id, name, email, address, phone, birthdate, role, is_verified, is_active, created_at
                 FROM users WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$viewId]);
            $viewUser = $stmt->fetch() ?: null;
        }
    } catch (PDOException $e) {
        error_log('[TourBan] Admin users query error: ' . $e->getMessage());
    }
}

$pages = $perPage > 0 ? max(1, (int) ceil($totalRows / $perPage)) : 1;

$adminPageTitle = 'Users';
$adminNavKey = 'users.php';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">User management</h4>
        <p class="text-muted mb-0"><?php echo (int) $totalRows; ?> user<?php echo $totalRows === 1 ? '' : 's'; ?> found.</p>
    </div>
</div>

<?php if ($viewUser): ?>
    <!-- Profile details -->
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <span><i class="fas fa-id-card me-2 text-primary"></i>Profile details</span>
            <a href="<?php echo admin_e(admin_url('admin/users.php')); ?>" class="btn btn-sm btn-outline-secondary">Close</a>
        </div>
        <div class="admin-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="text-muted small">Full name</div>
                        <div class="fw-semibold"><?php echo admin_e($viewUser['name']); ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Email address</div>
                        <div class="fw-semibold"><?php echo admin_e($viewUser['email']); ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Role</div>
                        <span class="admin-badge role-<?php echo admin_e($viewUser['role']); ?>">
                            <?php echo admin_e(ucfirst($viewUser['role'])); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="text-muted small">Status</div>
                        <?php if ((int) $viewUser['is_active'] === 1): ?>
                            <span class="admin-badge status-active">Active</span>
                        <?php else: ?>
                            <span class="admin-badge status-inactive">Deactivated</span>
                        <?php endif; ?>
                        <?php if ((int) $viewUser['is_verified'] === 1): ?>
                            <span class="admin-badge status-confirmed">Verified</span>
                        <?php else: ?>
                            <span class="admin-badge status-pending">Unverified</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Phone</div>
                        <div class="fw-semibold"><?php echo admin_e($viewUser['phone'] ?: '—'); ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Address</div>
                        <div class="fw-semibold"><?php echo admin_e($viewUser['address'] ?: '—'); ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Birth date</div>
                        <div class="fw-semibold">
                            <?php echo $viewUser['birthdate'] ? admin_e(date('M j, Y', strtotime($viewUser['birthdate']))) : '—'; ?>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="text-muted small">Registered</div>
                        <div class="fw-semibold"><?php echo admin_e(date('M j, Y H:i', strtotime($viewUser['created_at']))); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <span>All users</span>
        <form method="get" action="<?php echo admin_e(admin_url('admin/users.php')); ?>" class="admin-filters">
            <input
                type="search"
                name="q"
                class="form-control"
                placeholder="Search name or email…"
                value="<?php echo admin_e($q); ?>"
                aria-label="Search users" />
            <button type="submit" class="btn btn-admin-primary">
                <i class="fas fa-magnifying-glass me-1"></i>Search
            </button>
            <?php if ($q !== ''): ?>
                <a href="<?php echo admin_e(admin_url('admin/users.php')); ?>" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$users): ?>
        <div class="admin-empty">
            <i class="fas fa-users-slash"></i>
            No users match your search.
        </div>
    <?php else: ?>
        <div class="table-responsive-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Bookings</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo admin_e($u['name']); ?></div>
                                <div class="small text-muted"><?php echo admin_e($u['email']); ?></div>
                            </td>
                            <td>
                                <span class="admin-badge role-<?php echo admin_e($u['role']); ?>">
                                    <?php echo admin_e(ucfirst($u['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int) $u['is_active'] === 1): ?>
                                    <span class="admin-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="admin-badge status-inactive">Deactivated</span>
                                <?php endif; ?>
                                <?php if ((int) $u['is_verified'] !== 1): ?>
                                    <span class="admin-badge status-pending">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int) $u['booking_count']; ?></td>
                            <td class="small text-muted">
                                <?php echo admin_e(date('M j, Y', strtotime($u['created_at']))); ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary admin-inline-form"
                                    href="<?php echo admin_e(admin_url('admin/users.php?view=' . (int) $u['id'])); ?>"
                                    title="View profile">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if ((int) $u['id'] !== (int) $adminUser['id']): ?>
                                    <form method="post" class="admin-inline-form"
                                        action="<?php echo admin_e(admin_url('admin/users.php')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                                        <input type="hidden" name="action" value="set_role" />
                                        <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>" />
                                        <select name="role" class="form-select form-select-sm d-inline-block w-auto"
                                            onchange="this.form.submit()" aria-label="Change role">
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        </select>
                                    </form>

                                    <form method="post" class="admin-inline-form"
                                        action="<?php echo admin_e(admin_url('admin/users.php')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                                        <input type="hidden" name="action" value="set_status" />
                                        <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>" />
                                        <input type="hidden" name="is_active"
                                            value="<?php echo (int) $u['is_active'] === 1 ? '0' : '1'; ?>" />
                                        <?php if ((int) $u['is_active'] === 1): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Deactivate account"
                                                onclick="return confirm('Deactivate <?php echo admin_e($u['name']); ?>? They will no longer be able to sign in.');">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                title="Activate account">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted" title="This is you">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="admin-card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small text-muted">
                    Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?>
                </span>
                <div class="btn-group">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-sm btn-outline-primary"
                            href="<?php echo admin_e(admin_url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1])))); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a class="btn btn-sm btn-outline-primary"
                            href="<?php echo admin_e(admin_url('admin/users.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1])))); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
