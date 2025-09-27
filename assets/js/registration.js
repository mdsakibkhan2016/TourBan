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
        password: formData.get('password'),
        address: formData.get('address')
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

    // Make API call to registration endpoint
    fetch('api/register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                alert('Account created successfully! Redirecting to login...');
                form.reset();
                // Redirect to login page
                setTimeout(() => {
                    window.location.href = '/login.php';
                }, 1000);
            } else {
                alert(result.message || 'Registration failed. Please try again.');
            }
        })
        .catch((error) => {
            console.error('Registration error:', error);
            alert('Network error. Please check your connection and try again.');
        })
        .finally(() => {
            submitButton.textContent = 'Create Account';
            submitButton.disabled = false;
        });
}
