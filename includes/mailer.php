<?php

/**
 * Mail helper — SMTP (PHPMailer) with PHP mail() fallback.
 *
 * SMTP is configured entirely through environment variables:
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 *   MAIL_FROM_ADDRESS (alias: MAIL_FROM), MAIL_FROM_NAME,
 *   MAIL_ENCRYPTION (tls|ssl|none), MAIL_LOGO_URL (optional)
 *
 * Credentials are only ever read from the environment / .env file and are
 * never written into source code or logs.
 *
 * Never logs message bodies (OTP codes / booking details) in production.
 */

require_once __DIR__ . '/../config/database.php';

if (!function_exists('mail_smtp_configured')) {
    /** True when an SMTP relay is configured. */
    function mail_smtp_configured(): bool
    {
        return trim((string) env('MAIL_HOST', '')) !== '';
    }
}

if (!function_exists('mail_from_address')) {
    /**
     * Resolve the From address.
     * MAIL_FROM_ADDRESS is the canonical name; MAIL_FROM is kept as a
     * backwards-compatible alias for existing .env files.
     */
    function mail_from_address(): string
    {
        $candidates = [
            (string) env('MAIL_FROM_ADDRESS', ''),
            (string) env('MAIL_FROM', ''),
            (string) env('MAIL_USERNAME', ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return 'no-reply@' . (parse_url((string) BASE_URL, PHP_URL_HOST) ?: 'localhost');
    }
}

if (!function_exists('mail_from_name')) {
    /** Branded sender name shown in the email client. */
    function mail_from_name(): string
    {
        $name = trim((string) env('MAIL_FROM_NAME', ''));

        return $name !== '' ? $name : 'TourBan';
    }
}

if (!function_exists('mail_logo_url')) {
    /**
     * Absolute, publicly reachable logo URL for email clients.
     * Email clients cannot read local filesystem paths, so the logo is always
     * referenced over HTTP(S) and falls back to the hosted asset when one is
     * configured. Returns '' when no reachable logo can be built, in which case
     * the layout renders the wordmark only.
     */
    function mail_logo_url(): string
    {
        $configured = trim((string) env('MAIL_LOGO_URL', ''));
        if ($configured !== '') {
            return $configured;
        }

        $base = rtrim((string) BASE_URL, '/');
        if ($base === '' || strpos($base, 'http') !== 0) {
            return '';
        }

        return $base . '/assets/images/logo.png';
    }
}

if (!function_exists('send_app_mail')) {
    /**
     * Send a plain-text (optionally HTML) email.
     * Uses PHPMailer over SMTP when MAIL_HOST is set, otherwise PHP mail().
     * Returns true only when the transport accepted the message.
     */
    function send_app_mail(string $to, string $subject, string $body, ?string $html = null): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $from = mail_from_address();
        $fromName = mail_from_name();

        if (mail_smtp_configured()) {
            return mail_send_smtp($to, $subject, $body, $html, $from, $fromName);
        }

        return mail_send_php($to, $subject, $body, $html, $from, $fromName);
    }
}

if (!function_exists('mail_send_smtp')) {
    function mail_send_smtp(string $to, string $subject, string $body, ?string $html, string $from, string $fromName): bool
    {
        $host = trim((string) env('MAIL_HOST'));
        $port = (int) env('MAIL_PORT', 587);
        $username = (string) env('MAIL_USERNAME', '');
        $password = (string) env('MAIL_PASSWORD', '');
        $encryption = strtolower((string) env('MAIL_ENCRYPTION', 'tls'));

        require_once __DIR__ . '/lib/phpmailer/Exception.php';
        require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/lib/phpmailer/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port > 0 ? $port : 587;
            $mail->SMTPAuth = $username !== '';
            if ($username !== '') {
                $mail->Username = $username;
                $mail->Password = $password;
            }

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === '') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure = '';
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if ($html !== null && $html !== '') {
                $mail->isHTML(true);
                $mail->AltBody = strip_tags($body);
                $mail->MsgHTML($html);
            }

            $mail->send();
            return true;
        } catch (Throwable $e) {
            // Log transport failure only — never message bodies or credentials.
            error_log('[TourBan] SMTP send failed: ' . get_class($e) . ' (host=' . $host . ')');
            return false;
        }
    }
}

if (!function_exists('mail_send_php')) {
    function mail_send_php(string $to, string $subject, string $body, ?string $html, string $from, string $fromName): bool
    {
        $headers = 'From: ' . sprintf('%s <%s>', $fromName, $from) . "\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= 'Content-Type: ' . ($html !== null && $html !== '' ? 'text/html' : 'text/plain') . '; charset=UTF-8' . "\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

        $content = $html !== null && $html !== '' ? $html : $body;

        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $content, $headers);

        if (!$sent) {
            error_log('[TourBan] Mail delivery failed for recipient domain');
        }

        return $sent;
    }
}

// ------------------------------------------------------------------
// Domain email notifications
// ------------------------------------------------------------------

if (!function_exists('send_otp_email')) {
    /**
     * Send an OTP email (password reset or email verification).
     *
     * The code is only ever placed in the message body that is handed to the
     * mail transport. It is never returned to the caller, logged, or exposed
     * through an API response.
     */
    function send_otp_email(string $to, string $name, string $code, string $purpose = 'registration'): bool
    {
        $isReset = $purpose === 'password_reset';
        $subject = $isReset
            ? 'TourBan Password Reset Verification Code'
            : 'TourBan Verification Code';
        $minutes = otp_expiry_minutes();
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $reason = $isReset
            ? 'We received a request to reset your TourBan account password.'
            : 'We received a request to verify your TourBan account email address.';

        $body = "Hello,\n\n"
            . $reason . "\n\n"
            . "Your verification code is:\n\n"
            . $code . "\n\n"
            . "This code will expire in {$minutes} minutes.\n\n"
            . "If you did not request this, you can safely ignore this email.\n\n"
            . "TourBan\n"
            . mail_from_address() . "\n"
            . tourban_support_phone_for_mail();

        $inner = '<p style="margin:0 0 14px;font-size:15px;color:#1f2937;">Hello,</p>'
            . '<p style="margin:0 0 18px;font-size:15px;color:#1f2937;">' . $reason . '</p>'
            . '<p style="margin:0 0 10px;font-size:14px;color:#374151;">Your verification code is:</p>'
            . '<div style="margin:0 0 18px;padding:18px 22px;text-align:center;'
            . 'background:#eff6ff;border:2px dashed #93c5fd;border-radius:12px;">'
            . '<span style="font-family:Consolas,\'Courier New\',monospace;font-size:32px;'
            . 'font-weight:700;letter-spacing:8px;color:#1d4ed8;">' . $safeCode . '</span></div>'
            . '<p style="margin:0 0 8px;font-size:13px;color:#6b7280;">'
            . 'This code will expire in ' . $minutes . ' minutes.</p>'
            . '<p style="margin:0 0 18px;font-size:13px;color:#92400e;background:#fffbeb;'
            . 'border-left:3px solid #f59e0b;border-radius:6px;padding:10px 12px;">'
            . '<strong>Security notice:</strong> if you did not request this '
            . ($isReset ? 'password reset' : 'verification')
            . ', you can safely ignore this email. Your current password will stay active '
            . 'and no changes will be made to your account.</p>';

        $html = mail_layout_html(
            $isReset ? 'Password Reset Verification Code' : 'Email Verification Code',
            $inner,
            $safeName
        );

        return send_app_mail($to, $subject, $body, $html);
    }
}

if (!function_exists('tourban_support_phone_for_mail')) {
    /** Public contact phone printed in the email signature. */
    function tourban_support_phone_for_mail(): string
    {
        if (function_exists('tourban_support_phone')) {
            return tourban_support_phone();
        }

        return '+8801301374299';
    }
}

if (!function_exists('otp_expiry_minutes')) {
    /** OTP lifetime in minutes (must match the DB expiry window). */
    function otp_expiry_minutes(): int
    {
        $minutes = (int) env('OTP_EXPIRY_MINUTES', 15);

        return $minutes > 0 ? $minutes : 15;
    }
}

if (!function_exists('send_booking_confirmation')) {
    /**
     * Send booking confirmation email for a booking id (non-fatal).
     */
    function send_booking_confirmation(int $bookingId): bool
    {
        $data = mail_booking_data($bookingId);
        if (!$data) {
            return false;
        }

        $b = $data['booking'];
        $subject = 'TourBan booking confirmed — ' . $b['booking_ref'];
        $destList = implode(', ', array_column($data['items'], 'destination_name'));

        $body = "Hello {$b['name']},\n\n"
            . "Your TourBan booking is confirmed.\n\n"
            . "Reference: {$b['booking_ref']}\n"
            . "Trip: {$destList}\n"
            . "Travel date: {$b['travel_date']}\n"
            . "Travelers: {$b['travelers']}\n"
            . "Total: $" . number_format((float) $b['total_amount'], 2) . "\n\n"
            . "You can view or cancel it from your dashboard.\n\n"
            . "— TourBan";

        $rows = '';
        foreach ($data['items'] as $item) {
            $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #e5e7eb;">'
                . htmlspecialchars($item['destination_name'], ENT_QUOTES, 'UTF-8')
                . '</td><td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">$'
                . number_format((float) $item['unit_price'], 2) . ' × ' . (int) $item['quantity']
                . '</td></tr>';
        }

        $html = mail_layout_html(
            'Booking confirmed',
            '<p>Hello <strong>' . htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>Your booking <strong>' . htmlspecialchars($b['booking_ref'], ENT_QUOTES, 'UTF-8') . '</strong> is confirmed 🎉</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">' . $rows . '</table>'
            . '<p><strong>Travel date:</strong> ' . htmlspecialchars($b['travel_date'], ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>Travelers:</strong> ' . (int) $b['travelers'] . '<br>'
            . '<strong>Total:</strong> $' . number_format((float) $b['total_amount'], 2) . '</p>'
            . '<p style="font-size:13px;color:#6b7280;">Manage your bookings any time from your TourBan dashboard.</p>'
        );

        return send_app_mail($b['email'], $subject, $body, $html);
    }
}

if (!function_exists('send_booking_status_notification')) {
    /**
     * Notify the customer when an admin changes a booking status.
     */
    function send_booking_status_notification(int $bookingId, string $newStatus): bool
    {
        $data = mail_booking_data($bookingId);
        if (!$data) {
            return false;
        }

        $b = $data['booking'];
        $destList = implode(', ', array_column($data['items'], 'destination_name'));
        $subject = 'TourBan booking ' . $b['booking_ref'] . ' — status: ' . ucfirst($newStatus);

        $body = "Hello {$b['name']},\n\n"
            . "The status of your booking {$b['booking_ref']} ({$destList}) is now: {$newStatus}.\n\n"
            . "View details in your TourBan dashboard.\n\n"
            . "— TourBan";

        $statusColor = [
            'confirmed' => '#16a34a',
            'completed' => '#2563eb',
            'cancelled' => '#dc2626',
            'pending' => '#d97706',
        ][$newStatus] ?? '#2563eb';

        $html = mail_layout_html(
            'Booking update',
            '<p>Hello <strong>' . htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>Your booking <strong>' . htmlspecialchars($b['booking_ref'], ENT_QUOTES, 'UTF-8') . '</strong> '
            . '(' . htmlspecialchars($destList, ENT_QUOTES, 'UTF-8') . ') is now:</p>'
            . '<p style="font-size:18px;font-weight:700;color:' . $statusColor . ';">'
            . htmlspecialchars(strtoupper($newStatus), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-size:13px;color:#6b7280;">Open your dashboard for full details.</p>'
        );

        return send_app_mail($b['email'], $subject, $body, $html);
    }
}

if (!function_exists('mail_booking_data')) {
    /** Load booking + customer + line items for notification emails. */
    function mail_booking_data(int $bookingId): ?array
    {
        $db = (new Database())->getConnection();
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare(
                'SELECT b.id, b.booking_ref, b.status, b.travel_date, b.travelers, b.total_amount,
                        u.name, u.email
                 FROM bookings b JOIN users u ON u.id = b.user_id
                 WHERE b.id = ? LIMIT 1'
            );
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                return null;
            }

            $stmt = $db->prepare(
                'SELECT destination_name, unit_price, quantity FROM booking_items WHERE booking_id = ?'
            );
            $stmt->execute([$bookingId]);
            $items = $stmt->fetchAll();

            return ['booking' => $booking, 'items' => $items];
        } catch (PDOException $e) {
            error_log('[TourBan] Mail booking lookup failed');
            return null;
        }
    }
}

if (!function_exists('mail_layout_html')) {
    /**
     * Shared branded HTML wrapper for notification emails.
     *
     * The logo is referenced through an absolute http(s) URL because email
     * clients cannot load local filesystem paths. When no reachable logo URL
     * can be built the header falls back to the TourBan wordmark so the email
     * never shows a broken image.
     */
    function mail_layout_html(string $heading, string $innerHtml, string $greetingName = ''): string
    {
        $site = htmlspecialchars((string) BASE_URL, ENT_QUOTES, 'UTF-8');
        $heading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
        $logo = mail_logo_url();
        $logoHtml = '';

        if ($logo !== '') {
            $safeLogo = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
            $logoHtml = '<img src="' . $safeLogo . '" alt="TourBan" width="132" height="40" '
                . 'style="display:block;width:132px;height:auto;border:0;outline:none;'
                . 'text-decoration:none;-ms-interpolation-mode:bicubic;" />';
        }

        $greetingHtml = $greetingName !== ''
            ? '<p style="margin:0 0 4px;font-size:13px;color:#6b7280;">Hello '
                . $greetingName . ',</p>'
            : '';

        return '<!DOCTYPE html><html><head><meta charset="utf-8" />'
            . '<meta name="viewport" content="width=device-width,initial-scale=1" />'
            . '<title>' . $heading . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f6fb;'
            . 'font-family:Segoe UI,Arial,Helvetica,sans-serif;color:#1f2937;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:24px 0;">'
            . '<tr><td align="center"><table role="presentation" width="560" cellpadding="0" cellspacing="0" '
            . 'style="background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<tr><td style="background:linear-gradient(135deg,#2563eb,#1d4ed8);padding:20px 28px;">'
            . ($logoHtml !== '' ? $logoHtml . '<span style="display:none;">TourBan</span>'
                : '<span style="color:#ffffff;font-size:20px;font-weight:700;">TourBan</span>')
            . '</td></tr>'
            . '<tr><td style="padding:26px 28px 32px;">'
            . '<h2 style="margin:0 0 14px;font-size:20px;">' . $heading . '</h2>'
            . $greetingHtml
            . $innerHtml
            . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:22px 0;">'
            . '<p style="margin:0 0 4px;font-size:13px;color:#6b7280;font-weight:600;">TourBan</p>'
            . '<p style="margin:0 0 2px;font-size:12px;color:#9ca3af;">'
            . '<a href="mailto:' . htmlspecialchars(mail_from_address(), ENT_QUOTES, 'UTF-8')
            . '" style="color:#2563eb;text-decoration:none;">'
            . htmlspecialchars(mail_from_address(), ENT_QUOTES, 'UTF-8') . '</a>'
            . ' &middot; ' . htmlspecialchars(tourban_support_phone_for_mail(), ENT_QUOTES, 'UTF-8')
            . '</p>'
            . '<p style="margin:0;font-size:12px;color:#9ca3af;">'
            . '<a href="' . $site . '" style="color:#2563eb;text-decoration:none;">' . $site . '</a></p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}
