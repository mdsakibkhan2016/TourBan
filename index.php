<?php include 'includes/header.php'; ?>

<div>
    <!--Hero Section Start -->
    <div class="hero-section" style="margin-top: 76px">
        <div class="hero-content">
            <h1 class="hero-heading">Discover Your Dream Destinations</h1>
            <p class="hero-subheading">
                Embark on extraordinary journeys with personalized travel experiences crafted just for you
            </p>

            <div class="hero-features">
                <div class="feature-highlight">
                    <i class="fas fa-globe-americas"></i>
                    <span>500+ Destinations</span>
                </div>
                <div class="feature-highlight">
                    <i class="fas fa-star"></i>
                    <span>Expert Guides</span>
                </div>
                <div class="feature-highlight">
                    <i class="fas fa-shield-alt"></i>
                    <span>Safe Travel</span>
                </div>
            </div>

            <div class="search-container" role="search">
                <input
                    type="text"
                    id="searchInput"
                    class="search-input"
                    placeholder="Where would you like to go?"
                    aria-label="Search destinations"
                    aria-describedby="search-instructions"
                    aria-expanded="false"
                    aria-autocomplete="list"
                    autocomplete="off"
                    spellcheck="false" />
                <button type="button" class="search-button" aria-label="Search destinations">
                    <span>Explore</span>
                </button>
                <div
                    id="suggestionsBox"
                    class="suggestions-box"
                    role="listbox"
                    aria-label="Search suggestions"
                    aria-hidden="true"></div>
                <div id="search-instructions" class="sr-only">
                    Use arrow keys to navigate suggestions, Enter to select, Escape to close
                </div>
            </div>
        </div>
    </div>
    <!--Hero Section End -->

    <!--About part Start-->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <span class="badge bg-primary mb-3">About TourBan</span>
                    <h2 class="display-5 fw-bold text-primary mb-4">Your Perfect Travel Partner</h2>
                    <p class="lead mb-4">
                        At TourBan, we believe that every journey should be extraordinary. With years of experience in the
                        travel industry, we specialize in creating personalized travel experiences that match your dreams
                        and exceed your expectations.
                    </p>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                <span>Expert Travel Planning</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                <span>24/7 Customer Support</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                <span>Best Price Guarantee</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                <span>Secure Booking Process</span>
                            </div>
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary btn-lg">Learn More About Us</a>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img
                            src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600&h=400&fit=crop&crop=center&auto=format&q=80"
                            alt="Travel planning and consultation"
                            class="img-fluid rounded shadow-lg" />
                        <div class="position-absolute bottom-0 end-0 p-3">
                            <div class="card shadow">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 border-end">
                                            <h5 class="text-primary fw-bold mb-0">500+</h5>
                                            <small class="text-muted">Happy Customers</small>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="text-primary fw-bold mb-0">50+</h5>
                                            <small class="text-muted">Destinations</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--About part End-->

    <!--service part Start-->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold text-primary">Our Services</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 service-card">
                        <div class="card-body text-center p-4">
                            <div class="service-icon mb-3">
                                <i class="fas fa-globe fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title fw-bold">Worldwide Tours</h5>
                            <p class="card-text">
                                Experience amazing destinations around the world with our expertly crafted tour packages.
                                Discover new cultures, breathtaking landscapes, and unforgettable adventures.
                            </p>
                            <a href="#" class="btn btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 service-card">
                        <div class="card-body text-center p-4">
                            <div class="service-icon mb-3">
                                <i class="fas fa-hotel fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title fw-bold">Hotel Reservation</h5>
                            <p class="card-text">
                                Book premium accommodations worldwide with our extensive network of partner hotels. From
                                luxury resorts to boutique properties, find your perfect stay.
                            </p>
                            <a href="#" class="btn btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 service-card">
                        <div class="card-body text-center p-4">
                            <div class="service-icon mb-3">
                                <i class="fas fa-user-friends fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title fw-bold">Travel Guides</h5>
                            <p class="card-text">
                                Get expert guidance from our professional travel guides who know every destination inside
                                out. Enjoy personalized experiences and local insights.
                            </p>
                            <a href="#" class="btn btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--service part End-->

    <!--Featured Destinations Start-->
    <section id="destinations" class="py-5 bg-primary text-white">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-light text-primary mb-3">Explore</span>
                <h2 class="display-4 fw-bold mb-3">Featured Destinations</h2>
                <p class="lead">Discover amazing places around the world with our curated travel packages</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-lg border-0">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1515859005217-8a1f08870f59?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Rome Colosseum and Ancient Architecture"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary fs-6">From $599</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Rome, Italy</h5>
                            <p class="card-text">
                                Explore the eternal city with its ancient history and magnificent architecture
                            </p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="fas fa-clock me-1"></i> 7 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-8 People</span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-primary w-100">Book Now</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-lg border-0">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1613395877344-13d4a8e0d49e?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Santorini White Buildings and Blue Sea"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary fs-6">From $799</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Santorini, Greece</h5>
                            <p class="card-text">
                                Experience breathtaking sunsets and crystal-clear waters in this island paradise
                            </p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="fas fa-clock me-1"></i> 5 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-6 People</span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-primary w-100">Book Now</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-lg border-0">
                        <div class="position-relative">
                            <img
                                src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=250&fit=crop&crop=center&auto=format&q=80"
                                alt="Bali Tropical Paradise and Beaches"
                                class="card-img-top"
                                style="height: 250px; object-fit: cover" />
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary fs-6">From $499</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Bali, Indonesia</h5>
                            <p class="card-text">Discover tropical beaches, ancient temples, and rich cultural heritage</p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="fas fa-clock me-1"></i> 6 Days</span>
                                <span><i class="fas fa-users me-1"></i> 2-10 People</span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-primary w-100">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="#" class="btn btn-light btn-lg">View All Destinations</a>
            </div>
        </div>
    </section>
    <!--Featured Destinations End-->

</div>

<?php include 'includes/footer.php'; ?>