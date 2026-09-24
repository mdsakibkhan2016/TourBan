document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('resetForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = form.email.value.trim();
        var code = form.code.value.trim();
        var password = form.password.value;
        var password2 = form.password2.value;
        var btn = form.querySelector('button[type="submit"]');

        if (password !== password2) {
            alert('Passwords do not match.');
            return;
        }
        if (password.length < 6) {
            alert('Password must be at least 6 characters long.');
            return;
        }
        if (!/^\d{6}$/.test(code)) {
            alert('Enter the 6-digit reset code.');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Resetting...';

        fetch((window.BASE_URL || '') + '/api/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({ email: email, code: code, password: password })
        })
            .then(function (r) { return r.json(); })
            .then(function (result) {
                if (result.success) {
                    alert(result.message || 'Password updated.');
                    window.location.href = (window.BASE_URL || '') + '/login.php';
                } else {
                    alert(result.message || 'Reset failed.');
                }
            })
            .catch(function () {
                alert('Network error. Please try again.');
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = 'Reset Password';
            });
    });
});
