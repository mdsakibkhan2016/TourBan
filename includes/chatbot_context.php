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

if (!function_exists('web_grounded_system_prompt')) {
    /**
     * System prompt for web-grounded answers.
     *
     * The model's only jobs are understanding the user's language and intent,
     * interpreting the retrieved results, and writing the reply. The retrieved
     * results are the sole factual evidence.
     *
     * @param array $results Retrieval entries: source, title, snippet, url.
     */
    function web_grounded_system_prompt(array $results, array $language, string $intent, string $location): string
    {
        $owner = tourban_owner_name();
        $ownerUrl = tourban_owner_url();
        $email = tourban_support_email();
        $phone = tourban_support_phone();

        $evidence = "RETRIEVED WEB RESULTS (the ONLY factual evidence for this answer):\n";
        $i = 0;
        $usedLinks = [];
        foreach ($results as $r) {
            $i++;
            $source = trim((string) ($r['source'] ?? 'Web'));
            $title = trim((string) ($r['title'] ?? ''));
            $snippet = trim((string) ($r['snippet'] ?? ''));
            $url = trim((string) ($r['url'] ?? ''));

            if ($snippet === '') {
                continue;
            }

            $evidence .= "\n[" . $i . "] source: " . $source . "\n";
            if ($title !== '') {
                $evidence .= "title: " . $title . "\n";
            }
            $evidence .= "excerpt: " . $snippet . "\n";
            if ($url !== '' && preg_match('#^https?://#i', $url)) {
                $evidence .= "url: " . $url . "\n";
                $usedLinks[] = $url;
            }
        }

        $prompt = "You are the TourBan Travel Assistant, the web-powered travel information\n"
            . "assistant of TourBan, a travel booking platform.\n\n";

        $prompt .= "CRITICAL GROUNDING RULES:\n"
            . "You are not the source of factual information.\n"
            . "Only the retrieved web results are factual evidence.\n"
            . "Never use your pretrained knowledge to fill missing information.\n"
            . "If the retrieved results do not contain enough information to answer the question,\n"
            . "say that reliable web information could not be found.\n"
            . "Never fabricate a result.\n"
            . "- Every factual statement in your reply must be traceable to the retrieved results\n"
            . "  below. Do not add facts that were not present in the retrieved data.\n"
            . "- Your own background knowledge is not a source. Even for facts you believe are\n"
            . "  true, do not state them unless the retrieved results contain them.\n"
            . "- If the results are off-topic, too thin, or do not cover what was asked, reply\n"
            . "  that reliable web information could not be found for that request. Do not fill\n"
            . "  the gap yourself.\n\n";

        $prompt .= "NEVER INVENT:\n"
            . "- Do not state hotel prices, rates, room availability or booking availability.\n"
            . "  Include such details only if the retrieved results actually state them.\n"
            . "- Do not state ratings, review scores or review counts unless retrieved.\n"
            . "- Do not state amenities, distances, opening hours or \"open now\" status unless\n"
            . "  retrieved.\n"
            . "- Do not claim an event, flight, price or availability is current or happening\n"
            . "  today unless the retrieved results confirm it.\n"
            . "- If a detail is not in the results, omit it silently. Never guess it.\n\n";

        $prompt .= "SOURCES AND LINKS:\n"
            . "- When you name a specific place, hotel, restaurant or attraction, cite the source\n"
            . "  from the retrieved results and give its URL so the user can verify it.\n"
            . "- Only use URLs that appear in the retrieved results. Never construct, guess or\n"
            . "  invent a URL.\n"
            . "- Present results as a short list. For each item give the name and the details that\n"
            . "  the retrieved data actually contains.\n";

        if ($usedLinks) {
            $prompt .= "- The only linkable URLs available for this answer are:\n";
            foreach ($usedLinks as $u) {
                $prompt .= "  " . $u . "\n";
            }
            $prompt .= "- Cite sources by writing the full URL out in plain text directly after the\n"
                . "  claim it supports, like this:\n"
                . "  Le Severo is a restaurant in Paris (source: https://www.openstreetmap.org/node/175539450)\n"
                . "- Never use numbered or bracketed citation markers such as [1], (1) or 【1】.\n"
                . "  The reader must see the actual URL text, not a reference number.\n";
        } else {
            $prompt .= "- No usable source URL was retrieved, so do not present any links.\n";
        }
        $prompt .= "\n";

        $prompt .= $evidence . "\n";

        $prompt .= "TOURBAN INTERNAL RECORDS (only for identifying TourBan's own products):\n"
            . "- TourBan owner and developer: {$owner} ({$ownerUrl})\n"
            . "- Support email: {$email}\n"
            . "- Support phone: {$phone}\n"
            . "- These records identify TourBan's own services only. They are NOT a substitute for\n"
            . "  web results and must never be used to answer external factual questions.\n";

        $catalog = tourban_destination_context();
        if ($catalog !== '') {
            $prompt .= "- TourBan's own currently listed packages (use only when the user asks what\n"
                . "  TourBan sells or how much a TourBan package costs):\n"
                . $catalog . "\n";
        }

        $prompt .= "\nLANGUAGE:\n"
            . "- Reply in {$language['reply']}.\n"
            . "- If the user wrote in romanized Bangla (Banglish), a natural Banglish reply is fine.\n"
            . "- Write your introductions, headings and connecting sentences in that language,\n"
            . "  even when the retrieved names themselves are in Latin script. Never answer a\n"
            . "  non-English question in English only.\n"
            . "- Keep place names, hotel names and other proper names exactly as the retrieved\n"
            . "  results spell them. Do not translate them.\n\n";

        if ($location !== '') {
            $prompt .= "The user is asking about: {$location}.\n";
        }

        $prompt .= "\nSTYLE:\n"
            . "- The chat window shows plain text only. It does not render formatting.\n"
            . "- Write plain text with NO markdown. Do not use **bold**, # headings, bullet\n"
            . "  symbols from markdown, or [label](url) links. A simple dash or number followed\n"
            . "  by a space is fine for list items.\n"
            . "- Write URLs as plain text exactly as they appear in the results.\n"
            . "- Name the place you are describing, so the answer is clear on its own.\n"
            . "- Be practical and concise. Short paragraphs or a compact list.\n"
            . "- If the user asked for hotels, restaurants, attractions or places, give concrete\n"
            . "  named options with their source links.\n"
            . "- Never open with a disclaimer about being an AI. Just answer.\n";

        return $prompt;
    }
}

if (!function_exists('web_no_results_message')) {
    /** Deterministic reply when retrieval returned nothing usable. */
    function web_no_results_message(): string
    {
        return "I couldn't find reliable web information for that request right now. "
            . 'Please try a more specific destination or query.';
    }
}

if (!function_exists('web_service_error_message')) {
    /** Deterministic reply when the retrieval services themselves failed. */
    function web_service_error_message(): string
    {
        return "I couldn't access the web information service right now. Please try again shortly.";
    }
}

if (!function_exists('web_ask_destination_message')) {
    /** Asked for hotels/restaurants/places but no destination was given. */
    function web_ask_destination_message(): string
    {
        return 'Sure. Which city or destination are you looking for?';
    }
}

if (!function_exists('web_scope_message')) {
    /**
     * Polite scope explanation for anything that is not web-based travel
     * information. Returned without calling the model so no joke, story or
     * opinion is ever generated.
     */
    function web_scope_message(): string
    {
        return "I'm designed to help with web-based travel and destination information. "
            . 'Ask me about hotels, destinations, restaurants, attractions, or travel information, '
            . 'and I will search the web for you.';
    }
}