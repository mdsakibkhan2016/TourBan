// ============================================
// Registration Form JavaScript
// Immediate account creation (no mandatory email OTP)
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    initializeRegistrationForm();
});

function initializeRegistrationForm() {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    form.addEventListener('submit', handleFormSubmit);

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (nameInput) nameInput.addEventListener('blur', validateNameField);
    if (emailInput) emailInput.addEventListener('blur', validateEmailField);
    if (passwordInput) passwordInput.addEventListener('input', validatePasswordField);
}

function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('registrationForm');
    if (!form) return;

    clearErrorMessages(form);

    const formData = new FormData(form);
    const data = {
        name: (formData.get('name') || '').trim(),
        email: (formData.get('email') || '').trim(),
        password: formData.get('password'),
        address: formData.get('address')
    };

    if (!validateForm(data, form)) return;

    const submitButton = document.querySelector('.btn-register');
    setLoadingState(submitButton, true, 'Creating Account...');

    fetch((window.BASE_URL || '') + '/api/register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CSRF_TOKEN || ''
        },
        body: JSON.stringify(data)
    })
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                form.reset();
                const message = result.message || 'Account created successfully. You can sign in now.';
                if (window.showToast) showToast(message, 'success');
                showSuccessMessage(form, 'Account created! Redirecting to sign in...');
                setTimeout(() => {
                    window.location.href = (window.BASE_URL || '') + '/login.php?msg=registered';
                }, 1200);
            } else {
                showErrorMessage(form, result.message || 'Registration failed. Please try again.');
                if (window.showToast) showToast(result.message || 'Registration failed.', 'error');
            }
        })
        .catch((error) => {
            console.error('Registration error:', error);
            showErrorMessage(form, 'Network error. Please check your connection and try again.');
        })
        .finally(() => {
            setLoadingState(submitButton, false, 'Create Account');
        });
}

function validateForm(data, form) {
    let isValid = true;

    if (!data.name) {
        showFieldError('name', 'Full name is required');
        isValid = false;
    } else if (data.name.length < 2) {
        showFieldError('name', 'Name must be at least 2 characters long');
        isValid = false;
    }

    if (!data.email) {
        showFieldError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(data.email)) {
        showFieldError('email', 'Please enter a valid email address');
        isValid = false;
    }

    if (!data.password) {
        showFieldError('password', 'Password is required');
        isValid = false;
    } else if (data.password.length < 8) {
        showFieldError('password', 'Password must be at least 8 characters long');
        isValid = false;
    }

    return isValid;
}

function validateNameField() {
    const input = document.getElementById('name');
    if (!input) return;
    const value = input.value.trim();
    if (value && value.length < 2) showFieldError('name', 'Name must be at least 2 characters long');
    else clearFieldError('name');
}

function validateEmailField() {
    const input = document.getElementById('email');
    if (!input) return;
    const value = input.value.trim();
    if (value && !isValidEmail(value)) showFieldError('email', 'Please enter a valid email address');
    else clearFieldError('email');
}

function validatePasswordField() {
    const input = document.getElementById('password');
    if (!input) return;
    if (input.value && input.value.length < 8) showFieldError('password', 'Password must be at least 8 characters long');
    else clearFieldError('password');
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    clearFieldError(fieldId);
    field.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    errorDiv.id = fieldId + '-error';
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.classList.remove('is-invalid');
    const errorElement = document.getElementById(fieldId + '-error');
    if (errorElement) errorElement.remove();
}

function clearErrorMessages(form) {
    form.querySelectorAll('.invalid-feedback').forEach((element) => element.remove());
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    const alert = form.querySelector('.alert');
    if (alert) alert.remove();
}

function showErrorMessage(form, message) {
    const existing = form.querySelector('.alert');
    if (existing) existing.remove();
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.textContent = message;
    form.insertBefore(alertDiv, form.firstChild);
}

function showSuccessMessage(form, message) {
    const existing = form.querySelector('.alert');
    if (existing) existing.remove();
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.textContent = message;
    form.insertBefore(alertDiv, form.firstChild);
}

function setLoadingState(button, isLoading, label) {
    if (!button) return;
    button.disabled = isLoading;
    button.classList.toggle('loading', isLoading);
    button.textContent = isLoading ? label : 'Create Account';
}
