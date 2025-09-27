<?php
// Check if user is already logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php';
?>
<!-- Login Section Start -->
<section class="login-section" style="margin-top: 76px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h2 class="login-title">Welcome Back</h2>
                        <p class="login-subtitle">Sign in to your account</p>
                    </div>

                    <form id="loginForm" class="login-form">
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
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                                <label class="form-check-label" for="rememberMe">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-login w-100">
                                Sign In
                            </button>
                        </div>

                        <div class="login-footer">
                            <div class="text-center mb-3">
                                <a href="#" class="forgot-password-link">Forgot your password?</a>
                            </div>
                            <p class="text-center">
                                Don't have an account?
                                <a href="/register.php" class="register-link">Sign up</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Login Section End -->

<?php include 'includes/footer.php'; ?>

<!-- Login JavaScript -->
<script src="assets/js/login.js"></script>