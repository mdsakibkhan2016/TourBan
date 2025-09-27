<?php
require_once 'includes/auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
?>

<?php include 'includes/header.php'; ?>

<!-- Custom Profile Styles -->
<style>
    .profile-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 0;
        margin-top: 76px;
        position: relative;
        overflow: hidden;
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .profile-hero .container {
        position: relative;
        z-index: 2;
    }

    .profile-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border: none;
        margin-top: -50px;
        position: relative;
        z-index: 3;
    }

    .profile-avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        margin: 0 auto 2rem;
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        border: 5px solid white;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
    }

    .btn-custom {
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .section-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 2rem;
        color: #2c3e50;
        text-align: center;
    }

    @media (max-width: 768px) {
        .profile-hero {
            padding: 2rem 0;
        }

        .profile-card {
            margin-top: -30px;
            padding: 2rem;
        }
    }
</style>

<!-- Profile Hero Section -->
<section class="profile-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-user-edit me-3"></i>Edit Profile
                </h1>
                <p class="lead mb-0">
                    Update your personal information and preferences
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Profile Form -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="profile-card">
                <h2 class="section-title">Personal Information</h2>

                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Full Name
                                </label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label">
                            <i class="fas fa-map-marker-alt me-2"></i>Address
                        </label>
                        <textarea class="form-control" id="address" name="address" rows="4"
                            placeholder="Enter your full address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Phone Number
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    placeholder="Enter your phone number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="birthdate" class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Date of Birth
                                </label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-custom">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            address: formData.get('address'),
            phone: formData.get('phone'),
            birthdate: formData.get('birthdate')
        };

        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';

        // Make API call to update profile
        fetch('api/update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Show success message
                    showNotification('Profile updated successfully!', 'success');

                    // Update the page content in real-time
                    updatePageContent(result.user);

                    // Update navbar if name changed
                    updateNavbar(result.user.name);

                } else {
                    showNotification(result.message || 'Profile update failed', 'error');
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                showNotification('Network error. Please try again.', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            });
    });

    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;

        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    function updatePageContent(user) {
        // Update the profile avatar initial if name changed
        const avatar = document.querySelector('.profile-avatar-large');
        if (avatar) {
            avatar.textContent = user.name.charAt(0).toUpperCase();
        }

        // Update the page title if needed
        const pageTitle = document.querySelector('.display-4');
        if (pageTitle) {
            const firstName = user.name.split(' ')[0];
            pageTitle.innerHTML = `<i class="fas fa-user-edit me-3"></i>Edit Profile - ${firstName}`;
        }
    }

    function updateNavbar(userName) {
        // Update navbar user name if it exists
        const navbarUserName = document.querySelector('#userDropdown');
        if (navbarUserName) {
            const icon = navbarUserName.querySelector('i');
            navbarUserName.innerHTML = `<i class="fas fa-user me-2"></i>${userName}`;
        }
    }

    // Add real-time validation
    document.getElementById('email').addEventListener('blur', function() {
        const email = this.value;
        if (email && !isValidEmail(email)) {
            this.classList.add('is-invalid');
            showFieldError('email', 'Please enter a valid email address');
        } else {
            this.classList.remove('is-invalid');
            clearFieldError('email');
        }
    });

    document.getElementById('name').addEventListener('blur', function() {
        const name = this.value.trim();
        if (name && name.length < 2) {
            this.classList.add('is-invalid');
            showFieldError('name', 'Name must be at least 2 characters long');
        } else {
            this.classList.remove('is-invalid');
            clearFieldError('name');
        }
    });

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        clearFieldError(fieldId);

        field.classList.add('is-invalid');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        errorDiv.id = `${fieldId}-error`;

        field.parentNode.insertBefore(errorDiv, field.nextSibling);
    }

    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        field.classList.remove('is-invalid');

        const errorElement = document.getElementById(`${fieldId}-error`);
        if (errorElement) {
            errorElement.remove();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>