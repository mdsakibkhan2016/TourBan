<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/bookings.php';

$bookingHelper = new Bookings();
$destinations = $bookingHelper->allDestinations();
?>
<div>
    <!-- Hero Section -->
    <section class="bg-primary text-white py-5" style="margin-top: 76px">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-4">Amazing Destinations</h1>
                <p class="lead">Discover breathtaking places around the world with our curated travel packages</p>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h5 class="mb-3 mb-lg-0">Filter Destinations:</h5>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2" id="regionFilters">
                        <button type="button" class="btn btn-outline-primary btn-sm active" data-region="all">All</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-region="Europe">Europe</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-region="Asia">Asia</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-region="Africa">Africa</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-region="Americas">Americas</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-region="Oceania">Oceania</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Destinations -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Popular Destinations</h2>
                <p class="lead">Our most loved travel destinations</p>
            </div>

            <?php if (!$destinations): ?>
                <div class="text-center py-5">
                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                    <h5>No destinations available yet</h5>
                    <p class="text-muted">Please check back soon — our team is curating new trips.</p>
                    <a href="contact.php" class="btn btn-primary mt-2">Contact us for custom trips</a>
                </div>
            <?php else: ?>
                <div class="row g-4" id="destinationGrid">
                    <?php foreach ($destinations as $d): ?>
                        <div class="col-lg-4 col-md-6 destination-item" data-region="<?php echo htmlspecialchars($d['region'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="card h-100 shadow-sm border-0 destination-card-hover">
                                <div class="position-relative">
                                    <img
                                        src="<?php echo htmlspecialchars($d['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        class="card-img-top"
                                        style="height: 250px; object-fit: cover" />
                                    <div class="position-absolute top-0 start-0 m-3">
                                        <span class="badge bg-primary fs-6">From $<?php echo htmlspecialchars(number_format((float) $d['price_from'], 0), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                        <div class="text-warning">
                                            <i class="fas fa-star"></i>
                                            <small class="text-muted ms-1">(<?php echo htmlspecialchars($d['rating'], ENT_QUOTES, 'UTF-8'); ?>)</small>
                                        </div>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars(mb_substr((string) $d['description'], 0, 140), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="d-flex justify-content-between text-muted small mb-3">
                                        <span><i class="fas fa-clock me-1"></i> <?php echo (int) $d['duration_days']; ?> Days</span>
                                        <span><i class="fas fa-users me-1"></i> <?php echo htmlspecialchars($d['group_size'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($d['region'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="booking.php?dest=<?php echo urlencode($d['slug']); ?>" class="btn btn-primary flex-fill">Book Now</a>
                                        <a href="contact.php" class="btn btn-outline-primary">Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="noFilterResults" class="text-center py-4 d-none">
                    <p class="text-muted mb-0">No destinations in this region.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-4">Ready to Explore?</h2>
                    <p class="lead mb-4">
                        Choose your dream destination and let us create an unforgettable journey for you. Our travel
                        experts are ready to help you plan the perfect trip.
                    </p>
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                        <a href="contact.php" class="btn btn-light btn-lg px-5">Plan My Trip</a>
                        <a href="services.php" class="btn btn-outline-light btn-lg px-5">View Services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filters = document.getElementById('regionFilters');
    if (!filters) return;
    filters.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-region]');
        if (!btn) return;
        filters.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var region = btn.getAttribute('data-region');
        var shown = 0;
        document.querySelectorAll('.destination-item').forEach(function (item) {
            var match = region === 'all' || item.getAttribute('data-region') === region;
            item.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        var empty = document.getElementById('noFilterResults');
        if (empty) empty.classList.toggle('d-none', shown > 0);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
