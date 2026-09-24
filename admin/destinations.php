<?php

/**
 * Admin destination management — create, edit, delete, enable/disable.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$adminUser = require_admin();
$db = (new Database())->getConnection();

const ADMIN_REGIONS = ['Europe', 'Asia', 'Africa', 'Americas', 'Oceania'];
const ADMIN_CATEGORIES = ['Beach', 'City', 'Island', 'Cultural', 'Adventure', 'Luxury', 'Wildlife', 'Nature', 'Winter', 'Family'];

/** Auto-generate a URL slug from a destination name. */
function admin_dest_slug(PDO $db, string $name, int $ignoreId = 0): string
{
    $base = strtolower(trim($name));
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim((string) $base, '-');
    if ($base === '') {
        $base = 'destination';
    }
    $base = substr($base, 0, 110);

    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = $db->prepare('SELECT id FROM destinations WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $ignoreId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
        if ($i > 500) {
            return $base . '-' . bin2hex(random_bytes(3));
        }
    }
}

/** Validate and normalize a destination payload. Returns [data, errors]. */
function admin_dest_input(array $in): array
{
    $errors = [];
    $name = trim((string) ($in['name'] ?? ''));
    $description = trim((string) ($in['description'] ?? ''));
    $country = trim((string) ($in['country'] ?? ''));
    $region = trim((string) ($in['region'] ?? ''));
    $category = trim((string) ($in['category'] ?? ''));
    $imageUrl = trim((string) ($in['image_url'] ?? ''));
    $price = (float) ($in['price_from'] ?? 0);
    $duration = (int) ($in['duration_days'] ?? 0);
    $groupSize = trim((string) ($in['group_size'] ?? ''));
    $rating = (float) ($in['rating'] ?? 5);
    $isActive = !empty($in['is_active']) ? 1 : 0;

    if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
        $errors[] = 'Title must be 2–150 characters.';
    }
    if (mb_strlen($description) > 5000) {
        $errors[] = 'Description must be under 5000 characters.';
    }
    if ($country === '' || mb_strlen($country) > 100) {
        $errors[] = 'Location (country) is required.';
    }
    if ($price < 0 || $price > 999999) {
        $errors[] = 'Price must be between 0 and 999,999.';
    }
    if ($duration < 1 || $duration > 365) {
        $errors[] = 'Duration must be 1–365 days.';
    }
    if (mb_strlen($groupSize) > 50) {
        $errors[] = 'Group size must be under 50 characters.';
    }
    if ($rating < 0 || $rating > 5) {
        $errors[] = 'Rating must be between 0 and 5.';
    }
    if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
        $errors[] = 'Image must be an http(s) URL.';
    }
    if (mb_strlen($imageUrl) > 500) {
        $errors[] = 'Image URL is too long.';
    }

    return [
        [
            'name' => $name,
            'description' => $description,
            'country' => $country,
            'region' => mb_strlen($region) > 50 ? substr($region, 0, 50) : $region,
            'category' => mb_strlen($category) > 50 ? substr($category, 0, 50) : $category,
            'image_url' => $imageUrl,
            'price_from' => $price,
            'duration_days' => $duration,
            'group_size' => $groupSize,
            'rating' => $rating,
            'is_active' => $isActive,
        ],
        $errors,
    ];
}

// ---------------- POST actions (CSRF-protected) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_post();

    $action = (string) ($_POST['action'] ?? '');
    $targetId = (int) ($_POST['destination_id'] ?? 0);

    if (!$db) {
        admin_flash('error', 'Database unavailable. Try again.');
        admin_redirect('admin/destinations.php');
    }

    if ($action === 'create' || $action === 'update') {
        [$data, $errors] = admin_dest_input($_POST);

        if ($errors) {
            admin_flash('error', implode(' ', $errors));
            admin_redirect('admin/destinations.php' . ($action === 'update' ? '?edit=' . $targetId : '?add=1'));
        }

        if ($action === 'create') {
            $slug = admin_dest_slug($db, $data['name']);
            $stmt = $db->prepare(
                'INSERT INTO destinations (slug, name, country, region, category, description, image_url,
                                           price_from, duration_days, group_size, rating, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $slug, $data['name'], $data['country'], $data['region'], $data['category'],
                $data['description'], $data['image_url'], $data['price_from'],
                $data['duration_days'], $data['group_size'], $data['rating'], $data['is_active'],
            ]);
            admin_flash('success', 'Destination "' . $data['name'] . '" created.');
        } else {
            if ($targetId < 1) {
                admin_flash('error', 'Invalid destination.');
                admin_redirect('admin/destinations.php');
            }
            $exists = $db->prepare('SELECT id FROM destinations WHERE id = ?');
            $exists->execute([$targetId]);
            if (!$exists->fetch()) {
                admin_flash('error', 'Destination not found.');
                admin_redirect('admin/destinations.php');
            }
            $slug = admin_dest_slug($db, $data['name'], $targetId);
            $stmt = $db->prepare(
                'UPDATE destinations SET slug = ?, name = ?, country = ?, region = ?, category = ?,
                        description = ?, image_url = ?, price_from = ?, duration_days = ?,
                        group_size = ?, rating = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $slug, $data['name'], $data['country'], $data['region'], $data['category'],
                $data['description'], $data['image_url'], $data['price_from'],
                $data['duration_days'], $data['group_size'], $data['rating'], $data['is_active'],
                $targetId,
            ]);
            admin_flash('success', 'Destination updated.');
        }
    } elseif ($action === 'toggle' && $targetId > 0) {
        $stmt = $db->prepare('UPDATE destinations SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$targetId]);
        admin_flash('success', $stmt->rowCount() ? 'Destination status toggled.' : 'Destination not found.');
    } elseif ($action === 'delete' && $targetId > 0) {
        $check = $db->prepare('SELECT COUNT(*) FROM booking_items WHERE destination_id = ?');
        $check->execute([$targetId]);
        $booked = (int) $check->fetchColumn();

        if ($booked > 0) {
            // FK: bookings reference this destination — disable instead of deleting.
            $db->prepare('UPDATE destinations SET is_active = 0 WHERE id = ?')->execute([$targetId]);
            admin_flash('error', 'This destination has ' . $booked . ' booking line(s), so it was disabled instead of deleted.');
        } else {
            $stmt = $db->prepare('DELETE FROM destinations WHERE id = ?');
            $stmt->execute([$targetId]);
            admin_flash('success', $stmt->rowCount() ? 'Destination deleted.' : 'Destination not found.');
        }
    } else {
        admin_flash('error', 'Unknown action.');
    }

    admin_redirect('admin/destinations.php');
}

// ---------------- GET state ----------------
$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$editId = (int) ($_GET['edit'] ?? 0);
$showAdd = isset($_GET['add']);

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR country LIKE ? OR slug LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($statusFilter === 'active') {
    $where[] = 'is_active = 1';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'is_active = 0';
}
$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

$destinations = [];
$editDest = null;

if ($db) {
    try {
        $stmt = $db->prepare(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM booking_items bi WHERE bi.destination_id = d.id) AS booking_count
             FROM destinations d" . $whereSql . ' ORDER BY d.is_active DESC, d.name ASC'
        );
        $stmt->execute($params);
        $destinations = $stmt->fetchAll();

        if ($editId > 0) {
            $stmt = $db->prepare('SELECT * FROM destinations WHERE id = ? LIMIT 1');
            $stmt->execute([$editId]);
            $editDest = $stmt->fetch() ?: null;
        }
    } catch (PDOException $e) {
        error_log('[TourBan] Admin destinations query error: ' . $e->getMessage());
    }
}

$adminPageTitle = 'Destinations';
$adminNavKey = 'destinations.php';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Destination management</h4>
        <p class="text-muted mb-0"><?php echo count($destinations); ?> destination<?php echo count($destinations) === 1 ? '' : 's'; ?> shown.</p>
    </div>
    <a href="<?php echo admin_e(admin_url('admin/destinations.php?add=1')); ?>" class="btn btn-admin-primary">
        <i class="fas fa-plus me-2"></i>Add destination
    </a>
</div>

<?php if ($showAdd || $editDest): $d = $editDest; ?>
    <!-- Create / edit form -->
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <span>
                <i class="fas <?php echo $editDest ? 'fa-pen' : 'fa-plus'; ?> me-2 text-primary"></i>
                <?php echo $editDest ? 'Edit destination' : 'New destination'; ?>
            </span>
            <a href="<?php echo admin_e(admin_url('admin/destinations.php')); ?>" class="btn btn-sm btn-outline-secondary">Cancel</a>
        </div>
        <div class="admin-card-body">
            <form method="post" action="<?php echo admin_e(admin_url('admin/destinations.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                <input type="hidden" name="action" value="<?php echo $editDest ? 'update' : 'create'; ?>" />
                <?php if ($editDest): ?>
                    <input type="hidden" name="destination_id" value="<?php echo (int) $editDest['id']; ?>" />
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="admin-form-label" for="d-name">Title *</label>
                        <input class="form-control" id="d-name" name="name" required maxlength="150"
                            value="<?php echo admin_e($d['name'] ?? ''); ?>" placeholder="e.g. Rome, Italy" />
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-country">Location (country) *</label>
                        <input class="form-control" id="d-country" name="country" required maxlength="100"
                            value="<?php echo admin_e($d['country'] ?? ''); ?>" placeholder="Italy" />
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-region">Region</label>
                        <select class="form-select" id="d-region" name="region">
                            <option value="">—</option>
                            <?php foreach (ADMIN_REGIONS as $r): ?>
                                <option value="<?php echo admin_e($r); ?>"
                                    <?php echo (($d['region'] ?? '') === $r) ? 'selected' : ''; ?>>
                                    <?php echo admin_e($r); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-category">Category</label>
                        <select class="form-select" id="d-category" name="category">
                            <option value="">—</option>
                            <?php foreach (ADMIN_CATEGORIES as $c): ?>
                                <option value="<?php echo admin_e($c); ?>"
                                    <?php echo (($d['category'] ?? '') === $c) ? 'selected' : ''; ?>>
                                    <?php echo admin_e($c); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-price">Price from ($) *</label>
                        <input class="form-control" id="d-price" name="price_from" type="number" step="0.01" min="0"
                            max="999999" required value="<?php echo admin_e($d['price_from'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-duration">Duration (days) *</label>
                        <input class="form-control" id="d-duration" name="duration_days" type="number" min="1" max="365"
                            required value="<?php echo admin_e($d['duration_days'] ?? 7); ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-group">Group size</label>
                        <input class="form-control" id="d-group" name="group_size" maxlength="50"
                            value="<?php echo admin_e($d['group_size'] ?? ''); ?>" placeholder="2-8 People" />
                    </div>
                    <div class="col-md-3">
                        <label class="admin-form-label" for="d-rating">Rating (0–5)</label>
                        <input class="form-control" id="d-rating" name="rating" type="number" step="0.1" min="0" max="5"
                            value="<?php echo admin_e($d['rating'] ?? '4.8'); ?>" />
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label" for="d-image">Image URL</label>
                        <input class="form-control" id="d-image" name="image_url" maxlength="500"
                            value="<?php echo admin_e($d['image_url'] ?? ''); ?>" placeholder="https://…" />
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="d-active"
                                name="is_active" value="1" <?php echo ($d['is_active'] ?? 1) ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="d-active">Active (visible on the public site)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="admin-form-label" for="d-description">Description</label>
                        <textarea class="form-control" id="d-description" name="description" rows="4"
                            maxlength="5000" placeholder="What makes this trip special?"><?php echo admin_e($d['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-admin-primary px-4">
                        <i class="fas fa-save me-2"></i><?php echo $editDest ? 'Save changes' : 'Create destination'; ?>
                    </button>
                    <a href="<?php echo admin_e(admin_url('admin/destinations.php')); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <span>All destinations</span>
        <form method="get" action="<?php echo admin_e(admin_url('admin/destinations.php')); ?>" class="admin-filters">
            <input type="search" name="q" class="form-control" placeholder="Search…"
                value="<?php echo admin_e($q); ?>" aria-label="Search destinations" />
            <select name="status" class="form-select" aria-label="Filter by status">
                <option value="">Any status</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <button type="submit" class="btn btn-admin-primary">
                <i class="fas fa-filter me-1"></i>Filter
            </button>
            <?php if ($q !== '' || $statusFilter !== ''): ?>
                <a href="<?php echo admin_e(admin_url('admin/destinations.php')); ?>" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$destinations): ?>
        <div class="admin-empty">
            <i class="fas fa-map-location-dot"></i>
            No destinations found.
        </div>
    <?php else: ?>
        <div class="table-responsive-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Bookings</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($destinations as $d): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo admin_e($d['name']); ?></div>
                                <div class="small text-muted">
                                    <?php echo admin_e(trim($d['country'] . ($d['region'] ? ' · ' . $d['region'] : ''), ' ·')); ?>
                                    · /<?php echo admin_e($d['slug']); ?>
                                </div>
                            </td>
                            <td><?php echo admin_e($d['category'] ?: '—'); ?></td>
                            <td>$<?php echo admin_e(number_format((float) $d['price_from'], 2)); ?></td>
                            <td><?php echo (int) $d['duration_days']; ?>d</td>
                            <td><?php echo (int) $d['booking_count']; ?></td>
                            <td>
                                <?php if ((int) $d['is_active'] === 1): ?>
                                    <span class="admin-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="admin-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary admin-inline-form"
                                    href="<?php echo admin_e(admin_url('admin/destinations.php?edit=' . (int) $d['id'])); ?>"
                                    title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="post" class="admin-inline-form"
                                    action="<?php echo admin_e(admin_url('admin/destinations.php')); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                                    <input type="hidden" name="action" value="toggle" />
                                    <input type="hidden" name="destination_id" value="<?php echo (int) $d['id']; ?>" />
                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                        title="<?php echo (int) $d['is_active'] === 1 ? 'Disable' : 'Enable'; ?>">
                                        <i class="fas <?php echo (int) $d['is_active'] === 1 ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                    </button>
                                </form>
                                <form method="post" class="admin-inline-form"
                                    action="<?php echo admin_e(admin_url('admin/destinations.php')); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_e(csrf_token()); ?>" />
                                    <input type="hidden" name="action" value="delete" />
                                    <input type="hidden" name="destination_id" value="<?php echo (int) $d['id']; ?>" />
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        onclick="return confirm('Delete <?php echo admin_e($d['name']); ?>? This cannot be undone.');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
