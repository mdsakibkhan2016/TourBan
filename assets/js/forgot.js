document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('forgotForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = form.email.value.trim();
        var btn = form.querySelector('button[type="submit"]');

        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch((window.BASE_URL || '') + '/api/forgot_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({ email: email })
        })
            .then(function (r) { return r.json(); })
            .then(function (result) {
                if (result.success) {
                    setTimeout(function () {
                        window.location.href = (window.BASE_URL || '') + '/reset-password.php?email=' + encodeURIComponent(email);
                    }, 1000);
                    var old = form.querySelector('.alert');
                    if (old) old.remove();
                    var div = document.createElement('div');
                    div.className = 'alert alert-success';
                    div.textContent = result.message;
                    form.insertBefore(div, form.firstChild);
                } else {
                    alert(result.message || 'Request failed.');
                }
            })
            .catch(function () {
                alert('Network error. Please try again.');
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = 'Send Reset Code';
            });
    });
});
