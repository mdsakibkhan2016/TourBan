<?php include 'includes/header.php'; ?>

<div>
    <!-- Hero Section -->
    <section class="bg-primary text-white py-5" style="margin-top: 76px">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-4">Contact Us</h1>
                <p class="lead">
                    Get in touch with our travel experts. We're here to help you plan your perfect journey.
                </p>
            </div>
        </div>
    </section>

    <!-- Contact Form & Info Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Send Us a Message</h4>
                        </div>
                        <div class="card-body p-4">
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="firstName" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="lastName" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" />
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subject *</label>
                                        <select class="form-select" id="subject" required>
                                            <option value="">Choose a subject...</option>
                                            <option value="general">General Inquiry</option>
                                            <option value="booking">Booking Assistance</option>
                                            <option value="custom">Custom Tour Package</option>
                                            <option value="support">Customer Support</option>
                                            <option value="feedback">Feedback</option>
                                            <option value="partnership">Partnership</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="destination" class="form-label">Interested Destination (Optional)</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="destination"
                                            placeholder="e.g., Rome, Italy" />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="travelDate" class="form-label">Preferred Travel Date</label>
                                        <input type="date" class="form-control" id="travelDate" />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="travelers" class="form-label">Number of Travelers</label>
                                        <select class="form-select" id="travelers">
                                            <option value="">Select...</option>
                                            <option value="1">1 Person</option>
                                            <option value="2">2 People</option>
                                            <option value="3-5">3-5 People</option>
                                            <option value="6-10">6-10 People</option>
                                            <option value="10+">More than 10</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message *</label>
                                        <textarea
                                            class="form-control"
                                            id="message"
                                            rows="5"
                                            placeholder="Tell us about your travel plans, preferences, or any specific requirements..."
                                            required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="newsletter" />
                                            <label class="form-check-label" for="newsletter">
                                                Subscribe to our newsletter for travel deals and updates
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-lg-5">
                    <div class="h-100">
                        <h3 class="fw-bold text-primary mb-4">Get in Touch</h3>
                        <p class="lead mb-4">
                            Ready to start planning your dream vacation? Our travel experts are here to help you
                            every step of the way.
                        </p>

                        <!-- Contact Details -->
                        <div class="mb-4">
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-map-marker-alt text-white"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Our Office</h6>
                                    <p class="text-muted mb-0">343/1, Nakhalpara<br />Dhaka, Bangladesh</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-phone text-white"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Phone</h6>
                                    <p class="text-muted mb-0">+096 345 67890<br />Available 24/7</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-envelope text-white"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Email</h6>
                                    <p class="text-muted mb-0">tourban@gmail.com<br />We reply within 24 hours</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-4">
                                <div class="bg-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Business Hours</h6>
                                    <p class="text-muted mb-0">
                                        Mon - Fri: 9:00 AM - 6:00 PM<br />Weekend: 10:00 AM - 4:00 PM
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Follow Us</h5>
                            <div class="d-flex gap-3">
                                <a
                                    href="#"
                                    class="btn btn-outline-primary rounded-circle"
                                    style="width: 50px; height: 50px">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a
                                    href="#"
                                    class="btn btn-outline-primary rounded-circle"
                                    style="width: 50px; height: 50px">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a
                                    href="#"
                                    class="btn btn-outline-primary rounded-circle"
                                    style="width: 50px; height: 50px">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a
                                    href="#"
                                    class="btn btn-outline-primary rounded-circle"
                                    style="width: 50px; height: 50px">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a
                                    href="#"
                                    class="btn btn-outline-primary rounded-circle"
                                    style="width: 50px; height: 50px">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="card border-warning bg-warning bg-opacity-10">
                            <div class="card-body">
                                <h6 class="card-title fw-bold text-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Emergency Contact
                                </h6>
                                <p class="card-text mb-0">
                                    For travel emergencies: <strong>+880 1234 567890</strong><br />
                                    Available 24/7 for existing customers
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Find Us</h2>
                <p class="lead">Visit our office for personalized travel consultation</p>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <!-- Placeholder for Google Maps -->
                            <div class="bg-secondary rounded" style="height: 400px; position: relative">
                                <div
                                    class="position-absolute top-50 start-50 translate-middle text-center text-white">
                                    <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                                    <h5>Interactive Map</h5>
                                    <p class="mb-0">Google Maps integration would go here</p>
                                    <small class="text-light">343/1, Nakhalpara, Dhaka, Bangladesh</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Frequently Asked Questions</h2>
                <p class="lead">Quick answers to common questions</p>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq1">
                                    How do I book a tour package?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can book a tour package by contacting us through this form, calling our
                                    office, or visiting us in person. Our travel experts will help you choose the
                                    perfect package and handle all the booking details.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq2">
                                    What is included in your tour packages?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our tour packages typically include accommodation, transportation, guided tours,
                                    and some meals. Specific inclusions vary by package. We'll provide a detailed
                                    itinerary with all inclusions and exclusions before booking.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq3">
                                    Can I customize my travel itinerary?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutely! We specialize in creating custom travel experiences tailored to your
                                    preferences, budget, and schedule. Contact us to discuss your specific
                                    requirements.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq4">
                                    What is your cancellation policy?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Cancellation policies vary depending on the destination and type of booking.
                                    Generally, we offer flexible cancellation options. We'll provide detailed terms
                                    and conditions before confirming your booking.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq5">
                                    Do you provide travel insurance?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, we offer comprehensive travel insurance options to protect your investment
                                    and provide peace of mind during your travels. We highly recommend travel
                                    insurance for all international trips.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-4">Let's Plan Your Next Adventure</h2>
                    <p class="lead mb-4">
                        Our experienced travel consultants are ready to help you create unforgettable memories. Get
                        personalized recommendations and expert advice for your dream vacation.
                    </p>
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                        <a href="tel:+0963456789" class="btn btn-light btn-lg px-5">
                            <i class="fas fa-phone me-2"></i>Call Now
                        </a>
                        <a href="mailto:tourban@gmail.com" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-envelope me-2"></i>Email Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>