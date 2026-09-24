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
                        <h2 class="login-title">Reset Password</h2>
                        <p class="login-subtitle">Enter the code from your email and a new password</p>
                    </div>

                    <form id="resetForm" class="login-form">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($presetEmail, ENT_QUOTES, 'UTF-8'); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="code" class="form-label">Reset Code</label>
                            <input type="text" class="form-control" id="code" name="code"
                                   inputmode="numeric" maxlength="6" pattern="\d{6}"
                                   placeholder="000000" required />
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="6" required />
                        </div>

                        <div class="form-group">
                            <label for="password2" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="password2" name="password2"
                                   minlength="6" required />
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-login w-100">Reset Password</button>
                        </div>

                        <div class="login-footer text-center">
                            <p>
                                <a href="<?php echo htmlspecialchars(asset('login.php'), ENT_QUOTES, 'UTF-8'); ?>" class="register-link">Back to sign in</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="<?php echo htmlspecialchars(asset('assets/js/reset.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
