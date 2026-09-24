<?php
require_once __DIR__ . '/config/env.php';

secure_session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$presetEmail = isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)
    ? $_GET['email']
    : '';

include 'includes/header.php';
?>
<section class="login-section" style="margin-top: 76px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h2 class="login-title">Verify Your Email</h2>
                        <p class="login-subtitle">Enter the 6-digit code we sent to your inbox</p>
                    </div>

                    <form id="verifyForm" class="login-form">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($presetEmail, ENT_QUOTES, 'UTF-8'); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="code" class="form-label">Verification Code</label>
                            <input type="text" class="form-control" id="code" name="code"
                                   inputmode="numeric" maxlength="6" pattern="\d{6}"
                                   placeholder="000000" autocomplete="one-time-code" required />
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-login w-100">Verify Email</button>
                        </div>

                        <div class="login-footer text-center">
                            <p>
                                <a href="<?php echo htmlspecialchars(asset('login.php'), ENT_QUOTES, 'UTF-8'); ?>" class="register-link">Back to sign in</a>
                            </p>
                            <p class="mt-2">
                                <a href="#" id="resendLink" class="forgot-password-link">Resend code</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="<?php echo htmlspecialchars(asset('assets/js/verify.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
