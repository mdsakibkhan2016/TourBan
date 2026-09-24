<?php
require_once __DIR__ . '/config/env.php';

secure_session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php';
?>
<section class="login-section" style="margin-top: 76px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h2 class="login-title">Forgot Password</h2>
                        <p class="login-subtitle">We will email you a reset code</p>
                    </div>

                    <form id="forgotForm" class="login-form">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required />
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-login w-100">Send Reset Code</button>
                        </div>

                        <div class="login-footer text-center">
                            <p>
                                Remembered it?
                                <a href="<?php echo htmlspecialchars(asset('login.php'), ENT_QUOTES, 'UTF-8'); ?>" class="register-link">Sign in</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="<?php echo htmlspecialchars(asset('assets/js/forgot.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
