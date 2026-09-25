<?php
require_once 'includes/auth.php';
require_once 'includes/security.php';
require_once 'includes/bookings.php';
require_once 'includes/payments.php';

$auth = new Auth();

$ref = trim((string) ($_GET['ref'] ?? ''));

// Guests are bounced to login and returned here afterwards
if (!$auth->isLoggedIn()) {
    $redirect = 'payment.php' . ($ref !== '' ? '?ref=' . urlencode($ref) : '');
    header('Location: login.php?redirect=' . urlencode($redirect));
    exit;
}

$user = $auth->getCurrentUser();

$bookingsHelper = new Bookings();
$paymentsHelper = new Payments();

$booking = $ref !== '' ? $bookingsHelper->findByRef($ref) : null;
$bookingError = '';
$payment = null;
$items = [];
$methods = payment_available_methods();

if ($ref === '') {
    $bookingError = 'Missing booking reference.';
} elseif (!$booking) {
    $bookingError = 'Booking not found.';
} elseif ((int) $booking['user_id'] !== (int) $user['id']) {
    http_response_code(403);
    $bookingError = 'You do not have access to this booking.';
} else {
    $items = $bookingsHelper->itemsForBooking((int) $booking['id']);
    $payment = $paymentsHelper->findByBooking((int) $booking['id']);
}

$isPaid = $payment && ($payment['payment_status'] ?? '') === 'paid';
$isCancelled = $booking && ($booking['status'] ?? '') === 'cancelled';
$total = $booking ? number_format((float) $booking['total_amount'], 2, '.', '') : '0.00';
?>

<?php include 'includes/header.php'; ?>

<style>
    .payment-wrap {
        min-height: 60vh;
        padding: 2rem 0 3rem;
        margin-top: 76px;
        background: #f6f8fb;
    }

    .payment-card {
        background: #fff;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 12px 34px rgba(0, 0, 0, .08);
        overflow: hidden;
    }

    .payment-head {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 1.4rem 1.6rem;
    }

    .payment-head h1 {
        font-size: 1.35rem;
        margin: 0;
        font-weight: 600;
    }

    .payment-body {
        padding: 1.6rem;
    }

    .pay-line {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .55rem 0;
        border-bottom: 1px dashed #e5e7eb;
        font-size: .95rem;
    }

    .pay-line:last-child {
        border-bottom: 0;
    }

    .pay-line .label {
        color: #6b7280;
    }

    .pay-total {
        font-size: 1.4rem;
        font-weight: 700;
        color: #4f46e5;
    }

    .method-option {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: .8rem 1rem;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .method-option.active {
        border-color: #4f46e5;
        background: #eef2ff;
    }

    .method-option.disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    .method-option .method-name {
        font-weight: 600;
        font-size: .95rem;
    }

    .method-option .badge-off {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: #f3f4f6;
        color: #6b7280;
        border-radius: 999px;
        padding: .2rem .55rem;
    }

    .pay-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: 0;
        color: #fff;
        font-weight: 600;
        padding: .8rem 1.4rem;
        border-radius: 10px;
        width: 100%;
    }

    .pay-btn:disabled {
        opacity: .6;
    }

    .status-badge {
        border-radius: 999px;
        padding: .3rem .8rem;
        font-size: .8rem;
        font-weight: 600;
    }

    .status-paid {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #fef9c3;
        color: #854d0e;
    }

    .pay-error {
        display: none;
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: .7rem 1rem;
        font-size: .9rem;
        margin-bottom: 1rem;
    }
</style>

<div class="payment-wrap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <?php if ($bookingError !== ''): ?>
                    <div class="payment-card">
                        <div class="payment-head">
                            <h1>Payment</h1>
                        </div>
                        <div class="payment-body text-center">
                            <p class="mb-3 text-danger"><?php echo htmlspecialchars($bookingError, ENT_QUOTES, 'UTF-8'); ?></p>
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/dashboard.php" class="btn btn-outline-primary">Back to my bookings</a>
                        </div>
                    </div>

                <?php elseif ($isCancelled): ?>
                    <div class="payment-card">
                        <div class="payment-head">
                            <h1>Payment</h1>
                        </div>
                        <div class="payment-body text-center">
                            <p class="mb-3">Booking <strong><?php echo htmlspecialchars($booking['booking_ref'], ENT_QUOTES, 'UTF-8'); ?></strong> has been cancelled — no payment is due.</p>
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/dashboard.php" class="btn btn-outline-primary">Back to my bookings</a>
                        </div>
                    </div>

                <?php elseif ($isPaid): ?>
                    <div class="payment-card">
                        <div class="payment-head">
                            <h1>Payment complete</h1>
                        </div>
                        <div class="payment-body">
                            <div class="text-center mb-4">
                                <span class="status-badge status-paid">PAID</span>
                            </div>
                            <div class="pay-line"><span class="label">Booking</span>
                                <strong><?php echo htmlspecialchars($booking['booking_ref'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="pay-line"><span class="label">Amount</span>
                                <strong>$<?php echo htmlspecialchars($total, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="pay-line"><span class="label">Method</span>
                                <strong><?php echo htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="pay-line"><span class="label">Transaction ID</span>
                                <strong><?php echo htmlspecialchars($payment['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="pay-line"><span class="label">Date</span>
                                <strong><?php echo htmlspecialchars($payment['created_at'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="mt-4">
                                <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/dashboard.php" class="btn btn-primary w-100">View my bookings</a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="payment-card" id="payCard">
                        <div class="payment-head">
                            <h1>Complete your payment</h1>
                        </div>
                        <div class="payment-body">

                            <div class="pay-error" id="payError"></div>

                            <div class="mb-3">
                                <span class="status-badge status-pending">PENDING PAYMENT</span>
                            </div>

                            <div class="pay-line"><span class="label">Booking</span>
                                <strong><?php echo htmlspecialchars($booking['booking_ref'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="pay-line"><span class="label">Travel date</span>
                                <strong><?php echo htmlspecialchars($booking['travel_date'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="pay-line"><span class="label">Travelers</span>
                                <strong><?php echo (int) $booking['travelers']; ?></strong></div>

                            <?php foreach ($items as $item): ?>
                                <div class="pay-line"><span class="label">
                                        <?php echo htmlspecialchars($item['destination_name'], ENT_QUOTES, 'UTF-8'); ?> &times; <?php echo (int) $item['quantity']; ?>
                                    </span>
                                    <strong>$<?php echo number_format((float) $item['unit_price'] * (int) $item['quantity'], 2, '.', ''); ?></strong></div>
                            <?php endforeach; ?>

                            <div class="pay-line"><span class="label">Total due</span>
                                <span class="pay-total">$<?php echo htmlspecialchars($total, ENT_QUOTES, 'UTF-8'); ?></span></div>

                            <h2 class="h6 mt-4 mb-2">Payment method</h2>
                            <div class="d-grid gap-2 mb-4" id="methodList">
                                <?php foreach ($methods as $i => $m): ?>
                                    <label class="method-option <?php echo $m['configured'] ? '' : 'disabled'; ?>"
                                        data-method="<?php echo htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="radio" name="payment_method"
                                            value="<?php echo htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            <?php echo ($m['configured'] && $i === 0) ? 'checked' : ''; ?>
                                            <?php echo $m['configured'] ? '' : 'disabled'; ?>>
                                        <span class="method-name"><?php echo htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if (!$m['configured']): ?>
                                            <span class="badge-off ms-auto">Not available yet</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <button class="pay-btn" id="payBtn"
                                data-ref="<?php echo htmlspecialchars($booking['booking_ref'], ENT_QUOTES, 'UTF-8'); ?>">
                                Pay $<?php echo htmlspecialchars($total, ENT_QUOTES, 'UTF-8'); ?>
                            </button>

                            <a class="d-block text-center mt-3 text-muted" style="font-size:.9rem"
                                href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/dashboard.php">Pay later — back to my bookings</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var list = document.getElementById('methodList');
        var btn = document.getElementById('payBtn');
        var err = document.getElementById('payError');
        if (!list || !btn) return;

        list.addEventListener('click', function (e) {
            var option = e.target.closest('.method-option');
            if (!option || option.classList.contains('disabled')) return;
            var input = option.querySelector('input[type="radio"]');
            if (!input || input.disabled) return;
            input.checked = true;
            list.querySelectorAll('.method-option').forEach(function (o) {
                o.classList.toggle('active', o === option);
            });
        });

        var firstActive = list.querySelector('.method-option:not(.disabled)');
        if (firstActive) firstActive.classList.add('active');

        function showError(message) {
            err.textContent = message;
            err.style.display = 'block';
            if (window.showToast) showToast(message, 'error');
        }

        btn.addEventListener('click', function () {
            var selected = list.querySelector('input[name="payment_method"]:checked');
            if (!selected) {
                showError('Please choose a payment method.');
                return;
            }

            err.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            fetch((window.BASE_URL || '') + '/api/payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN || ''
                },
                body: JSON.stringify({
                    booking_ref: btn.getAttribute('data-ref'),
                    payment_method: selected.value
                })
            })
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    if (result.success) {
                        if (window.showToast) showToast('Payment successful!', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 900);
                    } else {
                        showError(result.message || 'Payment failed. Please try again.');
                        btn.disabled = false;
                        btn.innerHTML = 'Retry payment';
                    }
                })
                .catch(function () {
                    showError('Network error. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = 'Retry payment';
                });
        });
    })();
</script>

<?php include 'includes/footer.php'; ?>
