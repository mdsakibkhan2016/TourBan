// ============================================
// Simple Registration Form JavaScript
// ============================================

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeRegistrationForm();
});

function initializeRegistrationForm() {
    const form = document.getElementById('registrationForm');
    const submitButton = document.querySelector('.btn-register');

    if (!form) return;

    // Add event listener for form submission
    form.addEventListener('submit', handleFormSubmit);
}

function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('registrationForm');
    if (!form) return;

    // Get form data
    const formData = new FormData(form);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password')
    };

    // Basic validation
    if (!data.name || !data.email || !data.password) {
        alert('Please fill in all fields');
        return;
    }

    if (data.password.length < 6) {
        alert('Password must be at least 6 characters long');
        return;
    }

    // Show loading state
    const submitButton = document.querySelector('.btn-register');
    submitButton.textContent = 'Creating Account...';
    submitButton.disabled = true;

    // Simulate registration (replace with actual API call)
    setTimeout(() => {
        alert('Account created successfully!');
        form.reset();
        submitButton.textContent = 'Create Account';
        submitButton.disabled = false;
    }, 1500);
}
