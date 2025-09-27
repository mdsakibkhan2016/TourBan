// ============================================
// Simple Login Form JavaScript
// ============================================

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeLoginForm();
});

function initializeLoginForm() {
    const form = document.getElementById('loginForm');
    const submitButton = document.querySelector('.btn-login');

    if (!form) return;

    // Add event listener for form submission
    form.addEventListener('submit', handleFormSubmit);

    // Add real-time validation
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (emailInput) {
        emailInput.addEventListener('blur', validateEmail);
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', validatePassword);
    }
}

function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('loginForm');
    if (!form) return;

    // Clear previous error messages
    clearErrorMessages();

    // Get form data
    const formData = new FormData(form);
    const data = {
        email: formData.get('email'),
        password: formData.get('password'),
        rememberMe: formData.get('rememberMe') === 'on'
    };

    // Validate form data
    if (!validateForm(data)) {
        return;
    }

    // Show loading state
    const submitButton = document.querySelector('.btn-login');
    setLoadingState(submitButton, true);

    // Simulate login API call (replace with actual API call)
    setTimeout(() => {
        // Simulate successful login
        if (data.email === 'demo@example.com' && data.password === 'password123') {
            showSuccessMessage('Login successful! Redirecting...');

            // Store login state if remember me is checked
            if (data.rememberMe) {
                localStorage.setItem('rememberMe', 'true');
                localStorage.setItem('userEmail', data.email);
            } else {
                sessionStorage.setItem('userEmail', data.email);
            }

            // Redirect to dashboard or home page
            setTimeout(() => {
                window.location.href = '/';
            }, 1500);
        } else {
            showErrorMessage('Invalid email or password. Please try again.');
        }

        setLoadingState(submitButton, false);
    }, 1500);
}

function validateForm(data) {
    let isValid = true;

    // Validate email
    if (!data.email) {
        showFieldError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(data.email)) {
        showFieldError('email', 'Please enter a valid email address');
        isValid = false;
    }

    // Validate password
    if (!data.password) {
        showFieldError('password', 'Password is required');
        isValid = false;
    } else if (data.password.length < 6) {
        showFieldError('password', 'Password must be at least 6 characters long');
        isValid = false;
    }

    return isValid;
}

function validateEmail() {
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();

    if (email && !isValidEmail(email)) {
        showFieldError('email', 'Please enter a valid email address');
    } else {
        clearFieldError('email');
    }
}

function validatePassword() {
    const passwordInput = document.getElementById('password');
    const password = passwordInput.value;

    if (password && password.length < 6) {
        showFieldError('password', 'Password must be at least 6 characters long');
    } else {
        clearFieldError('password');
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    // Remove existing error
    clearFieldError(fieldId);

    // Add error class
    field.classList.add('is-invalid');

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    errorDiv.id = `${fieldId}-error`;

    // Insert error message after the field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    // Remove error class
    field.classList.remove('is-invalid');

    // Remove error message
    const errorElement = document.getElementById(`${fieldId}-error`);
    if (errorElement) {
        errorElement.remove();
    }
}

function clearErrorMessages() {
    const errorElements = document.querySelectorAll('.invalid-feedback');
    errorElements.forEach((element) => element.remove());

    const invalidFields = document.querySelectorAll('.is-invalid');
    invalidFields.forEach((field) => field.classList.remove('is-invalid'));
}

function showErrorMessage(message) {
    const form = document.getElementById('loginForm');
    if (!form) return;

    // Remove existing alert
    const existingAlert = form.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create error alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.textContent = message;

    // Insert at the top of the form
    form.insertBefore(alertDiv, form.firstChild);
}

function showSuccessMessage(message) {
    const form = document.getElementById('loginForm');
    if (!form) return;

    // Remove existing alert
    const existingAlert = form.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create success alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.textContent = message;

    // Insert at the top of the form
    form.insertBefore(alertDiv, form.firstChild);
}

function setLoadingState(button, isLoading) {
    if (!button) return;

    if (isLoading) {
        button.disabled = true;
        button.classList.add('loading');
        button.textContent = 'Signing In...';
    } else {
        button.disabled = false;
        button.classList.remove('loading');
        button.textContent = 'Sign In';
    }
}

// Check for remembered login on page load
function checkRememberedLogin() {
    const rememberMe = localStorage.getItem('rememberMe');
    const userEmail = localStorage.getItem('userEmail') || sessionStorage.getItem('userEmail');

    if (rememberMe === 'true' && userEmail) {
        const emailInput = document.getElementById('email');
        const rememberCheckbox = document.getElementById('rememberMe');

        if (emailInput) {
            emailInput.value = userEmail;
        }

        if (rememberCheckbox) {
            rememberCheckbox.checked = true;
        }
    }
}

// Initialize remembered login check
document.addEventListener('DOMContentLoaded', function () {
    checkRememberedLogin();
});
