<?php

/**
 * Mail helper — SMTP (PHPMailer) with PHP mail() fallback.
 *
 * SMTP is configured entirely through environment variables:
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 *   MAIL_FROM, MAIL_FROM_NAME, MAIL_ENCRYPTION (tls|ssl|none)
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

        $from = (string) env('MAIL_FROM', 'no-reply@' . (parse_url((string) BASE_URL, PHP_URL_HOST) ?: 'localhost'));
        $fromName = (string) env('MAIL_FROM_NAME', 'TourBan');

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
     * Send an OTP email (registration verification or password reset).
     */
    function send_otp_email(string $to, string $name, string $code, string $purpose = 'registration'): bool
    {
        $isReset = $purpose === 'password_reset';
        $subject = $isReset ? 'Your TourBan password reset code' : 'Your TourBan verification code';

        $body = "Hello {$name},\n\n"
            . ($isReset
                ? "Use this code to reset your TourBan password: {$code}\n"
                : "Your TourBan verification code is: {$code}\n")
            . "\nIt expires in 15 minutes. If you did not request this, you can safely ignore this email.\n\n"
            . "— TourBan";

        $html = mail_layout_html(
            $isReset ? 'Reset your password' : 'Verify your email',
            '<p>Hello <strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>' . ($isReset
                ? 'Use the code below to reset your password:'
                : 'Use the code below to verify your email address:') . '</p>'
            . '<div style="font-size:26px;font-weight:700;letter-spacing:6px;color:#2563eb;'
            . 'background:#eff6ff;border:1px dashed #93c5fd;border-radius:10px;padding:14px 22px;'
            . 'text-align:center;margin:18px 0;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<p style="color:#6b7280;font-size:13px;">This code expires in 15 minutes. '
            . 'If you did not request this, ignore this email.</p>'
        );

        return send_app_mail($to, $subject, $body, $html);
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
    /** Shared branded HTML wrapper for notification emails. */
    function mail_layout_html(string $heading, string $innerHtml): string
    {
        $site = htmlspecialchars((string) BASE_URL, ENT_QUOTES, 'UTF-8');
        $heading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f6fb;'
            . 'font-family:Segoe UI,Arial,sans-serif;color:#1f2937;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:24px 0;">'
            . '<tr><td align="center"><table role="presentation" width="560" cellpadding="0" cellspacing="0" '
            . 'style="background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<tr><td style="background:linear-gradient(135deg,#2563eb,#1d4ed8);padding:20px 28px;">'
            . '<span style="color:#ffffff;font-size:20px;font-weight:700;">TourBan</span></td></tr>'
            . '<tr><td style="padding:26px 28px 32px;">'
            . '<h2 style="margin:0 0 14px;font-size:20px;">' . $heading . '</h2>'
            . $innerHtml
            . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:22px 0;">'
            . '<p style="margin:0;font-size:12px;color:#9ca3af;">TourBan · <a href="' . $site
            . '" style="color:#2563eb;text-decoration:none;">' . $site . '</a></p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}
