<?php include 'includes/header.php'; ?>

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
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary btn-sm active">All</button>
                        <button class="btn btn-outline-primary btn-sm">Europe</button>
                        <button class="btn btn-outline-primary btn-sm">Asia</button>
                        <button class="btn btn-outline-primary btn-sm">Africa</button>
                        <button class="btn btn-outline-primary btn-sm">Americas</button>
                        <button class="btn btn-outline-primary btn-sm">Oceania</button>
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
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 destination-card-hover">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1515859005217-8a1f08870f59?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Rome, Italy"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-danger fs-6">20% OFF</span>
                            </div>
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary fs-6">From $599</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">Rome, Italy</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <small class="text-muted ms-1">(4.9)</small>
                                </div>
                            </div>
                            <p class="card-text">
                                Explore the eternal city with its ancient history, magnificent architecture, and
                                world-renowned cuisine.
                            </p>
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-clock me-1"></i> 7 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-8 People</span>
                                <span><i class="fas fa-map-marker-alt me-1"></i> Europe</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary flex-fill">Book Now</a>
                                <a href="#" class="btn btn-outline-primary">Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 destination-card-hover">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1613395877344-13d4a8e0d49e?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Santorini, Greece"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary fs-6">From $799</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">Santorini, Greece</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <small class="text-muted ms-1">(4.8)</small>
                                </div>
                            </div>
                            <p class="card-text">
                                Experience breathtaking sunsets and crystal-clear waters in this stunning island
                                paradise.
                            </p>
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-clock me-1"></i> 5 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-6 People</span>
                                <span><i class="fas fa-map-marker-alt me-1"></i> Europe</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary flex-fill">Book Now</a>
                                <a href="#" class="btn btn-outline-primary">Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 destination-card-hover">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Bali, Indonesia"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary fs-6">From $499</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">Bali, Indonesia</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <small class="text-muted ms-1">(4.7)</small>
                                </div>
                            </div>
                            <p class="card-text">
                                Discover tropical beaches, ancient temples, and rich cultural heritage in this
                                exotic destination.
                            </p>
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-clock me-1"></i> 6 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-10 People</span>
                                <span><i class="fas fa-map-marker-alt me-1"></i> Asia</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary flex-fill">Book Now</a>
                                <a href="#" class="btn btn-outline-primary">Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 destination-card-hover">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1549144511-f099e773c147?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Paris, France"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary fs-6">From $899</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">Paris, France</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <small class="text-muted ms-1">(4.9)</small>
                                </div>
                            </div>
                            <p class="card-text">
                                The city of love and lights, featuring iconic landmarks, world-class museums, and
                                exquisite cuisine.
                            </p>
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-clock me-1"></i> 6 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-8 People</span>
                                <span><i class="fas fa-map-marker-alt me-1"></i> Europe</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary flex-fill">Book Now</a>
                                <a href="#" class="btn btn-outline-primary">Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 destination-card-hover">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Tokyo, Japan"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary fs-6">From $1099</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">Tokyo, Japan</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <small class="text-muted ms-1">(4.8)</small>
                                </div>
                            </div>
                            <p class="card-text">
                                Experience the perfect blend of ancient traditions and cutting-edge technology in
                                Japan's vibrant capital.
                            </p>
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-clock me-1"></i> 8 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-6 People</span>
                                <span><i class="fas fa-map-marker-alt me-1"></i> Asia</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary flex-fill">Book Now</a>
                                <a href="#" class="btn btn-outline-primary">Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 destination-card-hover">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Dubai, UAE"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-success fs-6">Best Seller</span>
                            </div>
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-primary fs-6">From $749</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">Dubai, UAE</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <small class="text-muted ms-1">(4.9)</small>
                                </div>
                            </div>
                            <p class="card-text">
                                Luxury shopping, ultramodern architecture, and desert adventures in this glamorous
                                Middle Eastern city.
                            </p>
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-clock me-1"></i> 5 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-8 People</span>
                                <span><i class="fas fa-map-marker-alt me-1"></i> Asia</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary flex-fill">Book Now</a>
                                <a href="#" class="btn btn-outline-primary">Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- More Destinations -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">More Amazing Places</h2>
                <p class="lead">Explore our complete collection of destinations</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Maldives" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Maldives</h6>
                            <p class="text-muted small mb-2">From $1299</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Switzerland" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Switzerland</h6>
                            <p class="text-muted small mb-2">From $999</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="New York" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">New York, USA</h6>
                            <p class="text-muted small mb-2">From $849</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Thailand" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Thailand</h6>
                            <p class="text-muted small mb-2">From $649</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1549144511-f099e773c147?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Egypt" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Egypt</h6>
                            <p class="text-muted small mb-2">From $699</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Turkey" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Turkey</h6>
                            <p class="text-muted small mb-2">From $549</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1581833971358-2c8b550f87b3?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Brazil" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Brazil</h6>
                            <p class="text-muted small mb-2">From $899</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm destination-card-hover">
                        <img
                            src="https://images.unsplash.com/photo-1523482580672-f109ba8cb9be?w=300&h=200&fit=crop&crop=center&auto=format&q=80"
                            class="card-img-top"
                            style="height: 200px; object-fit: cover"
                            alt="Australia" />
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold">Australia</h6>
                            <p class="text-muted small mb-2">From $1199</p>
                            <a href="#" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="#" class="btn btn-primary btn-lg">Load More Destinations</a>
            </div>
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
                        <a href="contact.html" class="btn btn-light btn-lg px-5">Plan My Trip</a>
                        <a href="services.html" class="btn btn-outline-light btn-lg px-5">View Services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>