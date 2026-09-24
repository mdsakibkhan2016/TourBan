<?php
require_once 'includes/auth.php';
require_once 'includes/bookings.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();

$bookingsHelper = new Bookings();
$userBookings = $bookingsHelper->listForUser((int) $user['id']);
$bookingCount = $bookingsHelper->countForUser((int) $user['id']);

$statusBadges = [
    'pending' => 'bg-warning text-dark',
    'confirmed' => 'bg-success',
    'cancelled' => 'bg-secondary',
    'completed' => 'bg-primary',
];
?>

<?php include 'includes/header.php'; ?>

<!-- Custom Dashboard Styles -->
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 0;
        margin-top: 76px;
        position: relative;
        overflow: hidden;
    }

    .dashboard-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .dashboard-hero .container {
        position: relative;
        z-index: 2;
    }

    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .user-profile-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        margin: 0 auto 1.5rem;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .quick-action-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }

    .quick-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        color: inherit;
        text-decoration: none;
    }

    .quick-action-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }

    .recent-activity {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: none;
    }

    .activity-item {
        display: flex;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #f8f9fa;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1rem;
    }

    .gradient-bg-1 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .gradient-bg-2 {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .gradient-bg-3 {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .gradient-bg-4 {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .gradient-bg-5 {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .gradient-bg-6 {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }

    .text-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .section-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 2rem;
        color: #2c3e50;
    }

    @media (max-width: 768px) {
        .dashboard-hero {
            padding: 2rem 0;
        }

        .stats-card {
            margin-bottom: 1rem;
        }
    }
</style>

<!-- Dashboard Hero Section -->
<section class="dashboard-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>! 👋
                </h1>
                <p class="lead mb-4">
                    Ready to plan your next adventure? Explore amazing destinations and create unforgettable memories.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="destinations.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-map-marked-alt me-2"></i>Explore Destinations
                    </a>
                    <a href="services.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-concierge-bell me-2"></i>Our Services
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Dashboard Content -->
<div class="container py-5">
    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card text-center">
                <div class="stats-icon gradient-bg-1 text-white mx-auto">
                    <i class="fas fa-globe-americas"></i>
                </div>
                <div class="stats-number text-gradient">50+</div>
                <div class="stats-label">Destinations</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card text-center">
                <div class="stats-icon gradient-bg-2 text-white mx-auto">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number text-gradient">500+</div>
                <div class="stats-label">Happy Travelers</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card text-center">
                <div class="stats-icon gradient-bg-3 text-white mx-auto">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stats-number text-gradient">4.9</div>
                <div class="stats-label">Rating</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card text-center">
                <div class="stats-icon gradient-bg-4 text-white mx-auto">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stats-number text-gradient"><?php echo (int) $bookingCount; ?></div>
                <div class="stats-label">My Bookings</div>
            </div>
        </div>
    </div>

    <!-- My Bookings -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0">My Bookings</h3>
            <a href="destinations.php" class="btn btn-sm btn-outline-primary">Book a New Trip</a>
        </div>

        <?php if (!$userBookings): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-luggage-cart fa-2x text-muted mb-3"></i>
                    <p class="text-muted mb-3">You have no bookings yet.</p>
                    <a href="destinations.php" class="btn btn-primary">Explore Destinations</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive card border-0 shadow-sm">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ref</th>
                            <th>Destination</th>
                            <th>Travel Date</th>
                            <th>Travelers</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userBookings as $b): ?>
                            <?php
                            $badge = $statusBadges[$b['status']] ?? 'bg-secondary';
                            $canCancel = in_array($b['status'], ['pending', 'confirmed'], true);
                            ?>
                            <tr data-booking-id="<?php echo (int) $b['id']; ?>">
                                <td class="fw-semibold"><?php echo htmlspecialchars($b['booking_ref'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['destination_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['travel_date'] ? date('M j, Y', strtotime($b['travel_date'])) : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $b['travelers']; ?></td>
                                <td>$<?php echo htmlspecialchars(number_format((float) $b['total_amount'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($b['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="text-end">
                                    <?php if ($canCancel): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger cancel-booking-btn" data-id="<?php echo (int) $b['id']; ?>">
                                            Cancel
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- User Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="user-profile-card">
                <div class="text-center mb-4">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </a>
                </div>

                <div class="border-top pt-3">
                    <h6 class="fw-bold mb-3">Account Information</h6>
                    <div class="mb-2">
                        <small class="text-muted">Full Name</small>
                        <div class="fw-medium"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Email Address</small>
                        <div class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Address</small>
                        <div class="fw-medium"><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></div>
                    </div>
                    <?php if (!empty($user['phone'])): ?>
                        <div class="mb-2">
                            <small class="text-muted">Phone</small>
                            <div class="fw-medium"><?php echo htmlspecialchars($user['phone']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($user['birthdate'])): ?>
                        <div class="mb-0">
                            <small class="text-muted">Birth Date</small>
                            <div class="fw-medium"><?php echo date('M j, Y', strtotime($user['birthdate'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <h3 class="section-title">Quick Actions</h3>
            <div class="row g-3">
                <div class="col-12">
                    <a href="destinations.php" class="quick-action-card d-block">
                        <div class="quick-action-icon gradient-bg-1 text-white">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Browse Destinations</h6>
                        <p class="text-muted small mb-0">Discover amazing places around the world</p>
                    </a>
                </div>
                <div class="col-12">
                    <a href="services.php" class="quick-action-card d-block">
                        <div class="quick-action-icon gradient-bg-2 text-white">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Our Services</h6>
                        <p class="text-muted small mb-0">Explore our travel services and packages</p>
                    </a>
                </div>
                <div class="col-12">
                    <a href="contact.php" class="quick-action-card d-block">
                        <div class="quick-action-icon gradient-bg-3 text-white">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Contact Support</h6>
                        <p class="text-muted small mb-0">Get help from our travel experts</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4 mb-4">
            <div class="recent-activity">
                <h5 class="fw-bold mb-4">Recent Activity</h5>
                <div class="activity-item">
                    <div class="activity-icon gradient-bg-1 text-white">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="fw-medium">Account Created</div>
                        <small class="text-muted">Welcome to TourBan!</small>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon gradient-bg-2 text-white">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div>
                        <div class="fw-medium">Last Login</div>
                        <small class="text-muted"><?php echo date('M j, Y \a\t g:i A'); ?></small>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon gradient-bg-3 text-white">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <div class="fw-medium">Profile Viewed</div>
                        <small class="text-muted">Dashboard accessed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Destinations Preview -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="section-title">Featured Destinations</h3>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1515859005217-8a1f08870f59?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                class="card-img-top" style="height: 200px; object-fit: cover;" alt="Rome">
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary">From $599</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title fw-bold">Rome, Italy</h6>
                            <p class="card-text small text-muted">Explore the eternal city with its ancient history</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1613395877344-13d4a8e0d49e?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                class="card-img-top" style="height: 200px; object-fit: cover;" alt="Santorini">
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary">From $799</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title fw-bold">Santorini, Greece</h6>
                            <p class="card-text small text-muted">Experience breathtaking sunsets and crystal waters</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                class="card-img-top" style="height: 200px; object-fit: cover;" alt="Bali">
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary">From $499</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title fw-bold">Bali, Indonesia</h6>
                            <p class="card-text small text-muted">Discover tropical beaches and ancient temples</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="destinations.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-globe me-2"></i>View All Destinations
                </a>
            </div>
        </div>
    </div>
</div>


<!-- Dashboard JavaScript -->
<script>
    // Add smooth animations and interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stats cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards for animation
        document.querySelectorAll('.stats-card, .user-profile-card, .quick-action-card, .recent-activity').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Add hover effects to destination cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.transition = 'transform 0.3s ease';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add click animation to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });

    // Add ripple effect styles
    const style = document.createElement('style');
    style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
    document.head.appendChild(style);
</script>

<script>
    document.querySelectorAll('.cancel-booking-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            if (!confirm('Cancel this booking?')) return;

            this.disabled = true;
            this.textContent = 'Cancelling...';

            fetch((window.BASE_URL || '') + '/api/cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN || ''
                },
                body: JSON.stringify({ booking_id: parseInt(id, 10) })
            })
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    if (result.success) {
                        if (window.showToast) showToast('Booking cancelled.', 'success');
                        setTimeout(function () { window.location.reload(); }, 1000);
                    } else {
                        if (window.showToast) showToast(result.message || 'Could not cancel.', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Cancel';
                    }
                })
                .catch(function () {
                    if (window.showToast) showToast('Network error.', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Cancel';
                });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>