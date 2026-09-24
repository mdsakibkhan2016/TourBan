<?php

/**
 * Mail helper — sends plain-text email via PHP mail().
 * Never logs message bodies (OTP codes) outside APP_DEBUG.
 */

if (!function_exists('send_app_mail')) {
    function send_app_mail(string $to, string $subject, string $body): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $from = env('MAIL_FROM', 'no-reply@' . (parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost'));
        $fromName = env('MAIL_FROM_NAME', 'TourBan');

        $headers = 'From: ' . sprintf('%s <%s>', $fromName, $from) . "\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

        if (!$sent) {
            error_log('[TourBan] Mail delivery failed for recipient domain');
            if (defined('APP_DEBUG') && APP_DEBUG) {
                // Dev only: surface delivery failures without leaking in production logs
                error_log('[TourBan] Mail debug to=' . $to . ' subject=' . $subject);
            }
        }

        return $sent;
    }
}
