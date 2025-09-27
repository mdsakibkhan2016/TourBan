<?php include 'includes/header.php'; ?>
<!-- Registration Section Start -->
<section class="registration-section" style="margin-top: 76px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="registration-card">
                    <div class="registration-header">
                        <h2 class="registration-title">Sign Up</h2>
                        <p class="registration-subtitle">Create your account to get started</p>
                    </div>

                    <form id="registrationForm" class="registration-form">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required />
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required />
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                required />
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-register w-100">
                                Create Account
                            </button>
                        </div>

                        <div class="registration-footer">
                            <p class="text-center">
                                Already have an account?
                                <a href="/login.php" class="login-link">Sign in</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Registration Section End -->

<?php include 'includes/footer.php'; ?>