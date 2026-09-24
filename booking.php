<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/bookings.php';

$auth = new Auth();
$loggedIn = $auth->isLoggedIn();

$slug = isset($_GET['dest']) ? trim((string) $_GET['dest']) : '';
$helper = new Bookings();
$destination = $slug !== '' ? $helper->findDestinationBySlug($slug) : null;

include 'includes/header.php';

if (!$destination && empty($_GET['dest'])) {
    // No destination selected — show picker style empty state
}
?>
<section class="py-5" style="margin-top: 76px; min-height: 70vh;">
    <div class="container">
        <?php if (!$destination): ?>
            <div class="row justify-content-center">
                <div class="col-md-8 text-center py-5">
                    <i class="fas fa-route fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold">Choose a destination</h2>
                    <p class="text-muted">Pick a destination from our catalog to start your booking.</p>
                    <a href="destinations.php" class="btn btn-primary px-4">Browse Destinations</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <img src="<?php echo htmlspecialchars($destination['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars($destination['name'], ENT_QUOTES, 'UTF-8'); ?>"
                             class="card-img-top" style="height: 280px; object-fit: cover;">
                        <div class="card-body">
                            <h2 class="fw-bold"><?php echo htmlspecialchars($destination['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($destination['region'] . ', ' . $destination['country'], ENT_QUOTES, 'UTF-8'); ?>
                                &nbsp;·&nbsp;
                                <i class="fas fa-clock me-1"></i><?php echo (int) $destination['duration_days']; ?> Days
                                &nbsp;·&nbsp;
                                <i class="fas fa-star text-warning me-1"></i><?php echo htmlspecialchars($destination['rating'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p><?php echo htmlspecialchars($destination['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-3">Book This Trip</h4>

                            <?php if (!$loggedIn): ?>
                                <div class="alert alert-info">
                                    Please <a href="login.php">sign in</a> to complete your booking.
                                </div>
                                <a href="login.php" class="btn btn-primary w-100">Sign In to Book</a>
                            <?php else: ?>
                                <form id="bookingForm">
                                    <input type="hidden" id="destination_id" value="<?php echo (int) $destination['id']; ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Price per person</label>
                                        <p class="fs-4 text-primary fw-bold mb-0">
                                            $<?php echo htmlspecialchars(number_format((float) $destination['price_from'], 2), ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                    </div>

                                    <div class="mb-3">
                                        <label for="travel_date" class="form-label fw-semibold">Travel date</label>
                                        <input type="date" class="form-control" id="travel_date" name="travel_date"
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="travelers" class="form-label fw-semibold">Travelers</label>
                                        <input type="number" class="form-control" id="travelers" name="travelers"
                                               min="1" max="20" value="2" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="special_requests" class="form-label fw-semibold">Special requests (optional)</label>
                                        <textarea class="form-control" id="special_requests" rows="3"
                                                  placeholder="Dietary needs, hotel preferences…"></textarea>
                                    </div>

                                    <div class="mb-3 d-flex justify-content-between border-top pt-3">
                                        <span class="fw-semibold">Estimated total</span>
                                        <strong id="estimatedTotal">$<?php echo htmlspecialchars(number_format((float) $destination['price_from'] * 2, 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100" id="bookingSubmitBtn">
                                        Confirm Booking
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<?php if ($destination && $loggedIn): ?>
<script>
window.BOOKING_UNIT_PRICE = <?php echo json_encode((float) $destination['price_from']); ?>;
</script>
<script src="<?php echo htmlspecialchars(asset('assets/js/booking.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
