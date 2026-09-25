document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('bookingForm');
    if (!form) return;

    var unit = window.BOOKING_UNIT_PRICE || 0;
    var travelers = document.getElementById('travelers');
    var totalEl = document.getElementById('estimatedTotal');
    var btn = document.getElementById('bookingSubmitBtn');

    function updateTotal() {
        var n = parseInt(travelers.value, 10) || 1;
        if (n < 1) n = 1;
        if (n > 20) n = 20;
        totalEl.textContent = '$' + (unit * n).toFixed(2);
    }

    if (travelers) travelers.addEventListener('input', updateTotal);

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var payload = {
            destination_id: parseInt(document.getElementById('destination_id').value, 10),
            travel_date: document.getElementById('travel_date').value,
            travelers: parseInt(travelers.value, 10) || 1,
            special_requests: (document.getElementById('special_requests') || {}).value || ''
        };

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        fetch((window.BASE_URL || '') + '/api/create_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json(); })
            .then(function (result) {
                if (result.success) {
                    if (window.showToast) showToast('Booking ' + result.booking.booking_ref + ' created!', 'success');
                    form.reset();
                    updateTotal();
                    setTimeout(function () {
                        window.location.href = (window.BASE_URL || '') + '/payment.php?ref=' + encodeURIComponent(result.booking.booking_ref);
                    }, 1200);
                } else {
                    if (window.showToast) showToast(result.message || 'Booking failed.', 'error');
                }
            })
            .catch(function () {
                if (window.showToast) showToast('Network error. Please try again.', 'error');
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = 'Confirm Booking';
            });
    });
});
