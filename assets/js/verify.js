document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('verifyForm');
    if (form) form.addEventListener('submit', handleVerify);

    const resend = document.getElementById('resendLink');
    if (resend) resend.addEventListener('click', handleResend);
});

function apiPost(url, data) {
    return fetch((window.BASE_URL || '') + url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CSRF_TOKEN || ''
        },
        body: JSON.stringify(data)
    }).then(function (r) { return r.json(); });
}

function showMsg(form, message, ok) {
    var old = form.querySelector('.alert');
    if (old) old.remove();
    var div = document.createElement('div');
    div.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
    div.textContent = message;
    form.insertBefore(div, form.firstChild);
}

function handleVerify(e) {
    e.preventDefault();
    var form = e.target;
    var email = form.email.value.trim();
    var code = form.code.value.trim();
    var btn = form.querySelector('button[type="submit"]');

    if (!/^\d{6}$/.test(code)) {
        showMsg(form, 'Enter the 6-digit code', false);
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Verifying...';

    apiPost('/api/verify_otp.php', { email: email, code: code })
        .then(function (result) {
            if (result.success) {
                showMsg(form, result.message, true);
                setTimeout(function () {
                    window.location.href = (window.BASE_URL || '') + '/login.php';
                }, 1200);
            } else {
                showMsg(form, result.message || 'Verification failed.', false);
            }
        })
        .catch(function () {
            showMsg(form, 'Network error. Please try again.', false);
        })
        .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Verify Email';
        });
}

function handleResend(e) {
    e.preventDefault();
    var form = document.getElementById('verifyForm');
    var email = form.email.value.trim();
    if (!email) {
        showMsg(form, 'Enter your email first', false);
        return;
    }
    apiPost('/api/resend_otp.php', { email: email, purpose: 'registration' })
        .then(function (result) {
            showMsg(form, result.message || 'Request submitted.', !!result.success);
        })
        .catch(function () {
            showMsg(form, 'Network error. Please try again.', false);
        });
}
