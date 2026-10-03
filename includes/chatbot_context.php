<?php

/**
 * TourBan AI assistant context.
 *
 * Builds the system prompt for the Groq chatbot from real, public project
 * data so the model answers about TourBan accurately instead of inventing
 * details.
 *
 * Privacy rules enforced here:
 *  - Only public catalog data (destinations) is read from the database.
 *  - No user rows, password hashes, OTPs, payment secrets, sessions or
 *    API credentials are ever loaded or sent to the AI provider.
 */

if (!function_exists('tourban_owner_name')) {
    /** Project owner / developer name shown to visitors. */
    function tourban_owner_name(): string
    {
        return 'MD SAKIB KHAN';
    }
}

if (!function_exists('tourban_owner_url')) {
    /** Public portfolio link for the owner. */
    function tourban_owner_url(): string
    {
        return 'https://mdsakibkhan.me/';
    }
}

if (!function_exists('tourban_support_email')) {
    /** Support mailbox published in the site footer. */
    function tourban_support_email(): string
    {
        $configured = trim((string) env('MAIL_FROM_ADDRESS', ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return $configured;
        }

        return 'msk.official2016@gmail.com';
    }
}

if (!function_exists('tourban_support_phone')) {
    /** Public contact phone number shown in the site footer. */
    function tourban_support_phone(): string
    {
        return '+8801301374299';
    }
}

if (!function_exists('tourban_destination_context')) {
    /**
     * Read public destination catalog rows for chatbot grounding.
     * Returns a short bullet list, or '' when the catalog is unavailable.
     */
    function tourban_destination_context(int $limit = 25): string
    {
        if (!class_exists('Database')) {
            return '';
        }

        try {
            $db = (new Database())->getConnection();
            if (!$db) {
                return '';
            }

            $sql = 'SELECT name, country, price_from, duration_days, category
                    FROM destinations
                    WHERE is_active = 1
                    ORDER BY id
                    LIMIT ' . max(1, min(50, $limit));

            $stmt = $db->query($sql);
            $rows = $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) {
            error_log('[TourBan] Chatbot destination context unavailable');
            return '';
        }

        if (!$rows) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $country = trim((string) ($row['country'] ?? ''));
            $price = isset($row['price_from']) ? (float) $row['price_from'] : null;
            $days = isset($row['duration_days']) ? (int) $row['duration_days'] : 0;
            $category = trim((string) ($row['category'] ?? ''));

            $parts = [];
            if ($country !== '') {
                $parts[] = $country;
            }
            if ($days > 0) {
                $parts[] = $days . ' day' . ($days === 1 ? '' : 's');
            }
            if ($category !== '') {
                $parts[] = $category;
            }

            $line = '- ' . $name
                . ($parts ? ' (' . implode(', ', $parts) . ')' : '')
                . ($price !== null && $price > 0 ? ' - from $' . number_format($price, 0) : '');

            $lines[] = $line;
        }

        return $lines ? implode("\n", $lines) : '';
    }
}

if (!function_exists('chatbot_default_model')) {
    /** Recommended active Groq model for the TourBan assistant. */
    function chatbot_default_model(): string
    {
        return 'openai/gpt-oss-20b';
    }
}

if (!function_exists('chatbot_decommissioned_models')) {
    /**
     * Models Groq has retired. Configuring one of these makes every request
     * fail with 404 model_not_found, so they are treated as "not configured".
     */
    function chatbot_decommissioned_models(): array
    {
        return [
            'llama-3.1-8b-instant',
            'llama-3.2-3b-preview',
        ];
    }
}

if (!function_exists('chatbot_model')) {
    /**
     * Resolve the active model from GROQ_MODEL with a safe fallback.
     * Never hardcodes a credential; only the public model id.
     */
    function chatbot_model(): string
    {
        $configured = trim((string) env('GROQ_MODEL', ''));

        if ($configured === ''
            || $configured === 'your_groq_model_here'
            || in_array($configured, chatbot_decommissioned_models(), true)
        ) {
            if ($configured !== '') {
                error_log('[TourBan] GROQ_MODEL "' . $configured
                    . '" is retired or invalid; using default model instead.');
            }

            return chatbot_default_model();
        }

        return $configured;
    }
}

if (!function_exists('tourban_system_prompt')) {
    /**
     * Compose the system prompt: verified project facts + live catalog data.
     */
    function tourban_system_prompt(): string
    {
        $owner = tourban_owner_name();
        $ownerUrl = tourban_owner_url();
        $email = tourban_support_email();
        $phone = tourban_support_phone();

        $prompt = "You are the TourBan Travel Assistant, the official AI concierge of TourBan,\n"
            . "a modern tourism and travel booking platform.\n\n"
            . "VERIFIED PROJECT INFORMATION (treat as ground truth):\n"
            . "- Website name: TourBan\n"
            . "- Owner and developer: {$owner}\n"
            . "- Developer portfolio: {$ownerUrl}\n"
            . "- Support email: {$email}\n"
            . "- Contact phone: {$phone}\n"
            . "- Address: 343/1, Nakhalpara, Dhaka, Bangladesh\n\n"
            . "OWNER QUESTIONS:\n"
            . "If the user asks who owns TourBan, who developed TourBan, who made or built this\n"
            . "website, who the owner or developer is, or any similar question, answer that the\n"
            . "owner and developer is {$owner}. Mention the owner only when it is relevant to\n"
            . "the question; never bring the owner up in unrelated answers.\n\n"
            . "TOURBAN FEATURES AND HOW THE SITE WORKS:\n"
            . "- Public pages: Home, About, Services, Destinations, Contact.\n"
            . "- Accounts: visitors can register with a valid email and sign in immediately\n"
            . "  (registration does not require an email code). Password recovery uses a\n"
            . "  one-time code (OTP) emailed to the registered address.\n"
            . "- Signed-in users get a Dashboard with booking history, a Profile page to update\n"
            . "  name, email, address, phone and date of birth, plus password change.\n"
            . "- Destinations page lists the live catalog; each destination can be opened to see\n"
            . "  details, duration, group size, rating and the 'from' price.\n"
            . "- Booking flow: choose a destination, pick the travel date and number of\n"
            . "  travelers, review the live calculated total, then confirm. Every confirmed\n"
            . "  booking gets a reference code that starts with TB-.\n"
            . "- Bookings can be cancelled from the Dashboard while they are not yet completed.\n"
            . "- Payment: TourBan has a payment step in the booking flow and supports a sandbox\n"
            . "  (simulated) checkout for testing. Card, wallet and live payment gateways are not\n"
            . "  enabled, so never claim that a real card payment was processed.\n"
            . "- Booking status can be confirmed, and admins can update status from the admin panel.\n"
            . "- Security: CSRF protection, rate limiting, hashed passwords, prepared SQL, and\n"
            . "  output escaping are used across the app.\n\n";

        $catalog = tourban_destination_context();
        if ($catalog !== '') {
            $prompt .= "LIVE DESTINATION CATALOG (current public data from the TourBan database):\n"
                . $catalog . "\n"
                . "Use these exact names, countries, durations and prices when a traveler asks what\n"
                . "is available. Do not invent destinations or prices that are not listed above.\n\n";
        }

        $prompt .= "RULES:\n"
            . "- Answer TourBan-specific questions only from the verified information and the\n"
            . "  catalog above. Never fabricate TourBan facts, prices, policies or features.\n"
            . "- If the answer is not in the information above, say plainly that you do not have\n"
            . "  that information and suggest contacting the team via the Contact page.\n"
            . "- You cannot see user accounts, bookings, payments or profile data. For those, tell\n"
            . "  the user to check their TourBan dashboard.\n"
            . "- Stay on travel, tourism and TourBan topics; politely decline anything unrelated\n"
            . "  or harmful.\n"
            . "- Style: warm, concise and practical. Answer in the same language as the user. Use\n"
            . "  short paragraphs and simple lists, and offer 2-3 concrete options when useful.";

        return $prompt;
    }
}