<?php

/**
 * Web-powered retrieval for the TourBan AI assistant.
 *
 * The assistant must never answer factual questions from the language model's
 * pretrained knowledge. This module performs the retrieval step: it turns the
 * user's message into a normalized search query, calls free / key-less external
 * data sources, and returns only what those sources actually returned.
 *
 * Sources used (all free, none require an API key):
 *  - Open-Meteo      geocoding + current weather   (api.open-meteo.com)
 *  - Nominatim        OpenStreetMap place geocoding (nominatim.openstreetmap.org)
 *  - Overpass API     OpenStreetMap POIs: hotels, restaurants, attractions
 *  - Wikipedia REST   summaries + search           (en/bn/ar/ja/hi/es/fr/de)
 *  - DuckDuckGo IA    instant answers              (api.duckduckgo.com)
 *
 * Security notes:
 *  - Every request is made server-side. No key, header or URL used here is ever
 *    sent to the browser.
 *  - Outbound URLs are built from a fixed allow-list of hosts plus values that
 *    are URL-encoded, so the user message cannot be used for SSRF.
 *
 * If retrieval yields nothing, the caller must NOT fall back to model knowledge.
 */

if (!function_exists('web_search_http_timeout')) {
    /** Total seconds allowed for one outbound retrieval call. */
    function web_search_http_timeout(): int
    {
        $v = (int) env('WEB_SEARCH_TIMEOUT', '10');
        return $v < 3 ? 3 : ($v > 25 ? 25 : $v);
    }
}

if (!function_exists('web_search_cache_dir')) {
    /**
     * Cache lives outside the web root so deployments that wipe htdocs keep it
     * and it can never be requested over HTTP.
     */
    function web_search_cache_dir(): string
    {
        $base = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'tourban_websearch';
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }
        return is_dir($base) ? $base : '';
    }
}

if (!function_exists('web_search_cache_path')) {
    function web_search_cache_path(string $key): string
    {
        $dir = web_search_cache_dir();
        if ($dir === '') {
            return '';
        }
        return $dir . DIRECTORY_SEPARATOR . sha1($key) . '.json';
    }
}

if (!function_exists('web_search_cache_read')) {
    /** @return array|null */
    function web_search_cache_read(string $key, int $ttl): ?array
    {
        $path = web_search_cache_path($key);
        if ($path === '' || !is_readable($path)) {
            return null;
        }
        $age = time() - (int) filemtime($path);
        if ($age > $ttl || $age < 0) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}

if (!function_exists('web_search_cache_write')) {
    function web_search_cache_write(string $key, array $payload): void
    {
        $path = web_search_cache_path($key);
        if ($path === '') {
            return;
        }
        @file_put_contents($path, json_encode($payload), LOCK_EX);
    }
}

if (!function_exists('web_search_is_enabled_host')) {
    /**
     * Allow-list of external hosts. Only these may ever be contacted, so a
     * crafted user message cannot turn the assistant into an SSRF proxy.
     */
    function web_search_is_enabled_host(string $host): bool
    {
        $allowed = [
            'api.open-meteo.com',
            'geocoding-api.open-meteo.com',
            'nominatim.openstreetmap.org',
            'overpass-api.de',
            'overpass.kumi.systems',
            'api.duckduckgo.com',
        ];
        if (in_array(strtolower($host), $allowed, true)) {
            return true;
        }
        // Wikipedia language subdomains, e.g. en.wikipedia.org
        if (preg_match('/^[a-z]{2,3}(-[a-z]{2,4})?\.wikipedia\.org$/i', $host)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('web_search_get')) {
    /**
     * Server-side GET with timeout, size cap and a descriptive User-Agent as
     * required by the OpenStreetMap/Nominatim usage policy.
     *
     * @return array{ok:bool,status:int,body:string,error:string}
     */
    function web_search_get(string $url, int $timeout = 0, string $accept = 'application/json'): array
    {
        $fail = ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'invalid url'];

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            return $fail;
        }
        if ($parts['scheme'] !== 'https') {
            return $fail;
        }
        if (!web_search_is_enabled_host($parts['host'])) {
            error_log('[TourBan] web_search blocked non-allow-listed host: ' . $parts['host']);
            return $fail;
        }

        $timeout = $timeout > 0 ? $timeout : web_search_http_timeout();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(6, $timeout),
            CURLOPT_USERAGENT      => 'TourBanTravelAssistant/1.0 (+https://github.com/mdsakibkhan2016/TourBan)',
            CURLOPT_HTTPHEADER     => ['Accept: ' . $accept],
            CURLOPT_ENCODING       => '',
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            error_log('[TourBan] web_search curl error (' . $errno . '): ' . $error);
            return ['ok' => false, 'status' => $status, 'body' => '', 'error' => $error];
        }

        // Guard against unexpectedly large payloads.
        if (strlen($body) > 400000) {
            $body = substr($body, 0, 400000);
        }

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => (string) $body,
            'error'  => '',
        ];
    }
}

if (!function_exists('web_search_get_json')) {
    /** @return array|null Decoded JSON, or null on any failure. */
    function web_search_get_json(string $url, int $timeout = 0): ?array
    {
        $r = web_search_get($url, $timeout);
        if (!$r['ok'] || $r['body'] === '') {
            return null;
        }
        $data = json_decode($r['body'], true);
        return is_array($data) ? $data : null;
    }
}

/* ------------------------------------------------------------------ *
 * Language / script detection
 * ------------------------------------------------------------------ */

if (!function_exists('web_search_detect_language')) {
    /**
     * Detect the language of the user's message so retrieval can target the
     * right Wikipedia edition and the reply can be given in the same language.
     *
     * @return array{script:string,reply:string,wiki:string}
     */
    function web_search_detect_language(string $message): array
    {
        // Non-Latin scripts are unambiguous.
        if (preg_match('/\p{Script=Bengali}/u', $message)) {
            return ['script' => 'bengali', 'reply' => 'Bangla', 'wiki' => 'bn'];
        }
        if (preg_match('/\p{Script=Arabic}/u', $message)) {
            return ['script' => 'arabic', 'reply' => 'Arabic', 'wiki' => 'ar'];
        }
        if (preg_match('/[\p{Script=Hiragana}\p{Script=Katakana}]/u', $message)) {
            return ['script' => 'japanese', 'reply' => 'Japanese', 'wiki' => 'ja'];
        }
        if (preg_match('/\p{Script=Devanagari}/u', $message)) {
            return ['script' => 'devanagari', 'reply' => 'Hindi', 'wiki' => 'hi'];
        }
        if (preg_match('/\p{Script=Cyrillic}/u', $message)) {
            return ['script' => 'cyrillic', 'reply' => 'Cyrillic', 'wiki' => 'ru'];
        }
        if (preg_match('/\p{Script=Han}/u', $message)) {
            return ['script' => 'han', 'reply' => 'Chinese', 'wiki' => 'zh'];
        }

        // Latin script: distinguish Banglish (romanized Bangla) from English
        // and other Latin languages using romanized Bangla function words.
        $banglish = [
            'amar', 'apnar', 'tumi', 'ami', 'khoje', 'khujo', 'dao', 'dau', 'koro',
            'kori', 'bolo', 'bhalo', 'valo', 'ache', 'ase', 'ekta', 'jonno', 'niye',
            'te', 'ta', 'r ', 'dar', 'jabe', 'ni', 'deni', 'shaj', 'rong',
        ];
        $lower = ' ' . mb_strtolower($message, 'UTF-8') . ' ';
        $hits = 0;
        foreach ($banglish as $w) {
            if (preg_match('/(?<![\p{L}])' . preg_quote(trim($w), '/') . '(?![\p{L}])/u', $lower)) {
                $hits++;
            }
        }
        if ($hits >= 1) {
            return ['script' => 'banglish', 'reply' => 'Banglish (romanized Bangla)', 'wiki' => 'bn'];
        }

        return ['script' => 'latin', 'reply' => 'the same language the user used', 'wiki' => 'en'];
    }
}

/* ------------------------------------------------------------------ *
 * Intent detection
 * ------------------------------------------------------------------ */

if (!function_exists('web_search_keyword_hits')) {
    /** @return array<string,int> keyword => occurrences */
    function web_search_keyword_hits(string $haystackLower, array $keywords): array
    {
        $hits = [];
        foreach ($keywords as $kw) {
            $kw = trim($kw);
            if ($kw === '') {
                continue;
            }

            // Scripts that are written without word separators (Japanese,
            // Bengali, Devanagari, Arabic) cannot use lookaround boundaries,
            // because an adjacent letter would break the assertion. For those,
            // match the keyword as a substring.
            if (preg_match('/[\x80-\xFF]/', $kw)) {
                $n = mb_substr_count($haystackLower, mb_strtolower($kw));
            } else {
                // Allow a simple plural so "hôtels", "hoteles" and "hotels"
                // all match the singular keyword.
                $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($kw, '/') . '(?:s|es)?(?![\p{L}\p{N}])/iu';
                $n = preg_match_all($pattern, $haystackLower);
            }

            if ($n > 0) {
                $hits[$kw] = $n;
            }
        }
        return $hits;
    }
}

if (!function_exists('web_search_intent_keywords')) {
    /**
     * Multilingual keyword sets. Bangla, romanized Bangla, Hindi, Arabic,
     * Japanese, Spanish, French and German are all covered.
     */
    function web_search_intent_keywords(): array
    {
        return [
            'hotel' => [
                'hotel', 'hotels', 'hotal', 'hotels', 'hostel', 'hostels', 'resort',
                'resorts', 'motel', 'accommodation', 'inn', 'lodge', 'guesthouse',
                'pension', 'stay', 'room', 'rooms',
                'হোটেল', 'থাকার', 'রিসোর্ট',
                'होटल', 'ठहरना',
                'فندق', 'فنادق', 'نزل', 'إقامة',
                'ホテル', '旅館', '宿泊',
                'hôtel', 'hoteles', 'hotel', 'unterkunft',
            ],
            'restaurant' => [
                'restaurant', 'restaurants', 'restaurent', 'cafe', 'cafeteria',
                'coffee', 'food', 'eat', 'dining', 'diner', 'eatery', 'street food',
                'রেস্টুরেন্ট', 'খাবার', 'ক্যাফে',
                'भोजन', 'रेस्तरां', 'कॉफी',
                'مطعم', 'مطاعم', 'مقهى',
                'レストラン', '食堂', 'コーヒー',
                'restaurante', 'restaurantes', 'café', 'restaurant',
            ],
            'weather' => [
                'weather', 'forecast', 'temperature', 'rain', 'raining', 'snow',
                'climate', 'humidity', 'wind', 'sunny', 'hot today', 'cold today',
                'আবহাওয়া', 'বৃষ্টি', 'তাপমাত্রা', 'রোদ',
                'मौसम', 'तापमान', 'बारिश',
                'طقس', 'درجة الحرارة', 'مطر',
                '天気', '気温', '雨', '予報',
                'wetter', 'météo', 'tempo', 'temperatura', 'tiempo',
            ],
            'attraction' => [
                'tourist', 'attraction', 'attractions', 'sightseeing', 'landmark',
                'landmarks', 'museum', 'beach', 'beaches', 'places to visit',
                'thing to do', 'things to do', 'visit', 'temple', 'park',
                'দর্শনীয়', 'পর্যটন', 'স্থান', 'আকর্ষণ',
                'पर्यटन', 'आकर्षण', 'स्मारक',
                'معالم', 'سياحة', 'أماكن',
                '観光', '名所', '美術館', '海滩',
                'atracción', 'atracciones', 'sehenswert', 'attractions',
            ],
            'tourban_product' => [
                'tourban package', 'tourban price', 'tourban booking', 'tourban tour',
                'your package', 'your price', 'your booking', 'book a tour',
                'tourban destination', 'available destinations', 'what destinations',
                'টুরবান', 'প্যাকেজ', 'বুকিং',
            ],
        ];
    }
}

if (!function_exists('web_search_has_backreference')) {
    /**
     * True when the message points back at something already discussed
     * ("that place", "this city"), so it cannot be searched as written.
     */
    function web_search_has_backreference(string $message): bool
    {
        return (bool) preg_match(
            '/\b(?:that|this|those|these)\s+(?:place|city|country|location|spot|area|town|hotel|restaurant)\b/iu',
            $message
        ) || (bool) preg_match(
            '/(?:এই|সেই|ওই)\s+(?:জায়গাটি|জায়গা|শহরটি|শহর|দেশটি|দেশ|হোটেলটি|হোটেল)/u',
            $message
        );
    }
}

if (!function_exists('web_search_resolve_followup')) {
    /**
     * Rewrite an explicit back-reference so the follow-up can be searched.
     *
     * "Where is that place?" becomes "Where is Paris?" once the place from the
     * previous question is known. Only unambiguous noun phrases are replaced,
     * so ordinary wording is never mangled.
     */
    function web_search_resolve_followup(string $message, string $place): string
    {
        if ($place === '') {
            return $message;
        }

        $patterns = [
            '/\b(?:that|this)\s+(?:place|city|country|location|spot|area|town)\b/iu',
            '/\b(?:that|this)\s+(?:হালটেল|জায়গা|শহর|দেশ)\b/u',
            '/\b(?:এই|সেই)\s+(?:জায়গাটি|জায়গা|শহরটি|শহর|দেশটি)\b/u',
        ];

        foreach ($patterns as $p) {
            $message = preg_replace($p, $place, $message) ?? $message;
        }

        return trim(preg_replace('/\s+/u', ' ', $message) ?? $message);
    }
}

if (!function_exists('web_search_previous_place')) {
    /**
     * Find the place mentioned in the most recent earlier question, so a
     * follow-up that refers back ("where is that place?") can still be
     * resolved against real retrieved data.
     *
     * @param array $history Prior turns as [['role' => ..., 'content' => ...]]
     */
    function web_search_previous_place(array $history): string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            $turn = $history[$i];
            if (!is_array($turn) || ($turn['role'] ?? '') !== 'user') {
                continue;
            }
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '' || mb_strlen($content) > 300) {
                continue;
            }
            $place = web_search_extract_location($content);
            if ($place !== '') {
                return $place;
            }
        }
        return '';
    }
}

if (!function_exists('web_search_is_tourban_product_question')) {
    /**
     * Questions about TourBan's own products, prices, packages and bookings are
     * answered from the verified internal catalog rather than the web.
     *
     * This is deliberately phrase-based: a bare word such as "tour" must not
     * capture "tour in Bali", which is an ordinary web question.
     */
    function web_search_is_tourban_product_question(string $message): bool
    {
        $lower = ' ' . mb_strtolower($message, 'UTF-8') . ' ';

        // Any mention of the brand itself.
        if (preg_match('/(?<![\p{L}\p{N}])(tourban|tour\s?ban|টুরবান|توربان)(?![\p{L}\p{N}])/iu', $lower)) {
            return true;
        }

        // "your packages", "your prices", "your destinations", "your booking"
        if (preg_match('/\byour\s+(?:packages?|prices?|bookings?|tours?|trips?|destinations?|services?|offers?|hotels?|resorts?)\b/u', $lower)) {
            return true;
        }

        // "do you offer / can you book / how much is a trip"
        if (preg_match('/\b(?:do you|can you|could you|will you)\s+(?:offer|provide|have|arrange|book|sell)\b/u', $lower)
            && preg_match('/\b(?:packages?|tours?|trips?|bookings?|resorts?|hotels?|destinations?|itinerary|itineraries)\b/u', $lower)) {
            return true;
        }

        // "how much does a trip/package/tour cost", "can I book with you"
        if (preg_match('/\bhow\s+(?:much|many|long)\b/u', $lower)
            && preg_match('/\b(?:packages?|tours?|trips?|bookings?|cost|price|charge)\b/u', $lower)) {
            return true;
        }

        if (preg_match('/\b(?:book|reserve|booking)\b.*\b(?:with\s+you|from\s+you|through\s+tourban)\b/u', $lower)) {
            return true;
        }

        // Multilingual equivalents.
        if (preg_match('/(?:আপনার|আপনারা)\s*(?:প্যাকেজ|প্যাকেজসমূহ|বুকিং|ডেস্টিনেশন)/u', $message)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('web_search_is_smalltalk')) {
    /**
     * Jokes, creative writing and other non-web chat. These are refused
     * deterministically so the model is never asked to improvise.
     */
    function web_search_is_smalltalk(string $message): bool
    {
        $lower = ' ' . mb_strtolower($message, 'UTF-8') . ' ';
        $markers = [
            'joke', 'jokes', 'funny', 'make me laugh', 'tell me a joke', 'a joke',
            'riddle', 'pun', 'roast', 'meme',
            'জোক', 'হাসি', 'হাসার', 'ঠটকা', 'রসিকা',
            'मज़ाक', 'मजाक', 'चुटकुला', 'हँसाओ',
            'نكتة', 'اضحكني', 'طرفة',
            '冗談', ' jest', '笑话', 'witz', 'chiste', 'blague',
        ];
        foreach ($markers as $m) {
            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($m, '/') . '(?![\p{L}\p{N}])/iu', $lower)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('web_search_extract_location')) {
    /**
     * Best-effort extraction of the place the user is asking about.
     * Handles "in Bali", "near Eiffel Tower", Bangla "বালিতে", Banglish
     * "Bali te", and "at <Place>".
     */
    function web_search_extract_location(string $message): string
    {
        $text = trim($message);

        // Words that are capitalised only because they start a sentence. These
        // must never be mistaken for a place name.
        $stop = [
            'what', 'which', 'where', 'when', 'who', 'whom', 'whose', 'why', 'how',
            'is', 'are', 'was', 'were', 'do', 'does', 'did', 'can', 'could', 'would',
            'should', 'shall', 'will', 'may', 'might', 'must', 'tell', 'find', 'show',
            'please', 'give', 'explain', 'describe', 'list', 'and', 'but', 'if', 'then',
            'the', 'a', 'an', 'i', 'my', 'we', 'our', 'you', 'your', 'it', 'this',
            'that', 'there', 'here', 'hi', 'hello', 'hey', 'any', 'some', 'in', 'on',
            'at', 'of', 'for', 'to', 'from', 'me', 'us', 'also', 'now', 'today',
            'best', 'good', 'cheap', 'cheapest', 'near', 'close', 'hotel', 'hotels',
            'restaurant', 'restaurants', 'place', 'places', 'city', 'country',
            // TourBan is our own brand, never a destination.
            'tourban', 'tour', 'tours', 'trip', 'trips', 'package', 'packages',
            // Time expressions are not destinations.
            'spring', 'summer', 'autumn', 'fall', 'winter', 'monsoon', 'season',
            'today', 'tomorrow', 'tonight', 'weekend', 'week', 'month', 'year',
            'morning', 'afternoon', 'evening', 'night', 'day', 'days', 'time',
            'january', 'february', 'march', 'april', 'may', 'june', 'july',
            'august', 'september', 'october', 'november', 'december',
        ];

        // English / Latin: "in <Place>", "near <Place>", "at <Place>", "around <Place>"
        if (preg_match('/\b(?:in|near|at|around|close to)\s+([\p{L}\p{N}\'’\-\. ]{2,40})/iu', $text, $m)) {
            $place = web_search_clean_place($m[1]);
            // A bare year or number is never a destination.
            $isNumber = (bool) preg_match('/^[\p{N}\s]+$/u', $place);
            if ($place !== '' && !$isNumber && !in_array(mb_strtolower($place), $stop, true)) {
                return $place;
            }
        }

        // Bangla locative suffix. Capture only the word immediately before the
        // suffix so "আমার জন্য বালিতে ..." yields "বালি", not the whole prefix.
        if (preg_match('/([\p{L}\p{M}]{2,20})(?:তে|টির|য়ে)/u', $text, $m)) {
            $place = trim($m[1]);
            if ($place !== '') {
                return web_search_clean_place($place);
            }
        }

        // Hindi locative postposition "में" and Arabic preposition "في"
        if (preg_match('/([\p{L}\p{M}]{2,20})\s*(?:में|मे\b)/u', $text, $m)) {
            $place = trim($m[1]);
            if ($place !== '') {
                return web_search_clean_place($place);
            }
        }
        if (preg_match('/في\s+([\p{L}\p{M}]{2,20})/u', $text, $m)) {
            $place = trim($m[1]);
            if ($place !== '') {
                return web_search_clean_place($place);
            }
        }

        // Arabic has no capitalisation to key off and often omits the
        // preposition ("ما هو طقس دبي؟"), so the final noun before the
        // question mark is the best available candidate.
        if (preg_match('/([\p{Script=Arabic}]{3,20})\s*[؟?]/u', $text, $m)) {
            $place = trim($m[1]);
            if ($place !== '') {
                return web_search_clean_place($place);
            }
        }

        // Banglish: "Bali te", "Dhaka te", "Paris mein"
        if (preg_match('/\b([A-Za-z][A-Za-z\-\']{2,20})\s+(?:te|mein|ay)/u', $text, $m)) {
            $place = trim($m[1]);
            if ($place !== '' && !in_array(mb_strtolower($place), $stop, true)) {
                return web_search_clean_place($place);
            }
        }

        // Japanese: "<Place>に" / "<Place>の"
        if (preg_match('/([\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}]{2,20})[にの]/u', $text, $m)) {
            $place = trim($m[1]);
            if ($place !== '') {
                return web_search_clean_place($place);
            }
        }

        // Fallback: a capitalised token that is not the first word and is not a
        // sentence starter or common request word.
        if (preg_match_all('/(?<![\p{L}\p{N}])[\p{Lu}][\p{L}\p{N}\-\']{1,20}/u', $text, $m)) {
            foreach ($m[0] as $tok) {
                if (!in_array(mb_strtolower($tok), $stop, true)) {
                    return web_search_clean_place($tok);
                }
            }
        }

        return '';
    }
}

if (!function_exists('web_search_wikipedia_query')) {
    /**
     * Encyclopedia lookups need a topic, not a sentence. Strips the question
     * scaffolding so "What is the capital of France?" becomes
     * "capital of France" and retrieves the right article.
     */
    function web_search_wikipedia_query(string $message): string
    {
        $q = trim($message);
        $q = preg_replace('/[\?\!\.\x{0964}]+$/u', '', $q) ?? $q;

        $patterns = [
            '/^\s*(?:please\s+)?(?:can|could|would|will)\s+you\s+(?:please\s+)?/iu',
            '/^\s*(?:tell|show|give)\s+me\s+(?:about\s+|the\s+|a\s+|an\s+)?/iu',
            '/^\s*(?:explain|describe|list|suggest|recommend)\s+(?:me\s+)?(?:about\s+|some\s+|the\s+|a\s+|an\s+)?/iu',
            '/^\s*(?:i\s+want|i\s+need|help\s+me)\s+(?:to\s+)?(?:find\s+|get\s+)?/iu',
            '/^\s*what(?:' . "'" . 's| is| are| was| were)?\s+/iu',
            '/^\s*who(?:' . "'" . 's| is| are| was| were)?\s+/iu',
            '/^\s*where(?:' . "'" . 's| is| are| was| were| do| does)?\s+/iu',
            '/^\s*when(?:' . "'" . 's| is| are| was| were| did)?\s+/iu',
            '/^\s*(?:why|how)\s+/iu',
            '/^\s*how\s+(?:is|are|do|does|did|much|many|can)\s+/iu',
            '/^\s*(?:the|a|an)\s+/iu',
        ];

        foreach ($patterns as $p) {
            $q = preg_replace($p, '', $q) ?? $q;
        }

        $q = trim($q, " \t\n\r\0\x0B.,?!'\"\x{2019}-");

        // Guard against a query reduced to nothing.
        if (mb_strlen($q) < 2) {
            return trim($message);
        }

        return $q;
    }
}

if (!function_exists('web_search_clean_place')) {
    /** Trim interrogative/trailing noise from a candidate place name. */
    function web_search_clean_place(string $place): string
    {
        $place = trim(preg_replace('/\s+/u', ' ', $place) ?? $place);
        // Drop trailing question/filler words.
        $place = preg_replace(
            '/\s+(?:please|now|thanks|thank|you|good|best|cheap|cheapest|near|for|me|a|an|the|is|are|any|suggest|suggestion|recommend|hotel|hotels|restaurant|restaurants)\s*$/iu',
            '',
            $place
        ) ?? $place;
        $place = trim($place, " \t\n\r\0\x0B.,?!'\"-");

        if (mb_strlen($place) > 40) {
            $place = trim(mb_substr($place, 0, 40));
        }
        return $place;
    }
}

if (!function_exists('web_search_detect_intent')) {
    /**
     * @return array{
     *   intent:string, location:string, language:array, needs_destination:bool,
     *   keywords:array
     * }
     */
    function web_search_detect_intent(string $message): array
    {
        $lower = ' ' . mb_strtolower($message, 'UTF-8') . ' ';
        $language = web_search_detect_language($message);
        $location = web_search_extract_location($message);

        $sets = web_search_intent_keywords();
        $scores = [];
        foreach ($sets as $name => $words) {
            $scores[$name] = array_sum(web_search_keyword_hits($lower, $words));
        }

        arsort($scores);
        $top = array_key_first($scores);
        $intent = ($scores[$top] ?? 0) > 0 ? $top : 'general';

        // TourBan product questions are answered from the verified catalog,
        // not the web. Checked before keyword scoring so that questions such as
        // "do you offer hotel booking" are not mistaken for a web hotel search.
        if (web_search_is_tourban_product_question($message)) {
            $intent = 'tourban_product';
        } else {
            $productHits = array_sum(web_search_keyword_hits($lower, $sets['tourban_product']));
            if ($productHits > 0 && in_array($intent, ['general'], true)) {
                $intent = 'tourban_product';
            }
        }

        // Hotel/restaurant/attraction queries need a place before we can search.
        $needsDestination = in_array($intent, ['hotel', 'restaurant', 'attraction'], true)
            && $location === '';

        return [
            'intent'            => $intent,
            'location'          => $location,
            'language'          => $language,
            'needs_destination' => $needsDestination,
            'keywords'          => $scores,
        ];
    }
}

/* ------------------------------------------------------------------ *
 * Retrieval sources
 * ------------------------------------------------------------------ */

if (!function_exists('web_search_build_query')) {
    /**
     * Build the outbound search query from the user's message and detected
     * intent, so "cheap hotels in Bali" becomes "cheap hotels Bali".
     */
    function web_search_build_query(string $message, string $intent, string $location): string
    {
        $q = trim($message);
        $q = preg_replace('/[\?\!\.。]+$/u', '', $q) ?? $q;

        if ($location !== '') {
            // Append the place when the raw text does not already end with it.
            if (mb_stripos($q, $location) === false) {
                $q .= ' ' . $location;
            }
        }

        return trim($q);
    }
}

if (!function_exists('web_search_weather')) {
    /**
     * Current weather from Open-Meteo (key-less). Geocodes the place, then reads
     * the live observation. Returns real measured values only.
     */
    function web_search_weather(string $location, string $wikiLang = 'en'): array
    {
        if ($location === '') {
            return [];
        }

        $cacheKey = 'weather|' . mb_strtolower($location);
        $cached = web_search_cache_read($cacheKey, 900);
        if ($cached !== null) {
            return $cached;
        }

        // Geocode with Nominatim rather than Open-Meteo's own geocoder: it
        // resolves non-Latin place names (বালি -> Bali, कोलकाता -> Kolkata) and
        // returns coordinates we can pass straight to the forecast endpoint.
        $place = web_search_geocode($location, $wikiLang);
        if ($place === null) {
            return [];
        }

        $lat = (string) $place['lat'];
        $lon = (string) $place['lon'];
        $name = (string) ($place['name'] !== '' ? $place['name'] : $location);
        $country = '';
        $parts = explode(',', (string) $place['display']);
        if (count($parts) > 1) {
            $country = trim($parts[count($parts) - 1]);
        }

        $forecastUrl = 'https://api.open-meteo.com/v1/forecast?latitude=' . $lat . '&longitude=' . $lon
            . '&current=temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,wind_speed_10m'
            . '&timezone=auto';

        $wx = web_search_get_json($forecastUrl);
        if (!$wx || empty($wx['current'])) {
            return [];
        }

        $cur = $wx['current'];
        $codes = [
            0 => 'clear sky', 1 => 'mainly clear', 2 => 'partly cloudy', 3 => 'overcast',
            45 => 'fog', 48 => 'depositing rime fog', 51 => 'light drizzle',
            53 => 'moderate drizzle', 55 => 'dense drizzle', 61 => 'slight rain',
            63 => 'moderate rain', 65 => 'heavy rain', 71 => 'slight snow fall',
            73 => 'moderate snow fall', 75 => 'heavy snow fall', 80 => 'slight rain showers',
            81 => 'moderate rain showers', 82 => 'violent rain showers',
            95 => 'thunderstorm', 96 => 'thunderstorm with slight hail',
        ];
        $code = (int) ($cur['weather_code'] ?? -1);
        $desc = $codes[$code] ?? ('weather code ' . $code);

        // Open-Meteo reports local time as an ISO string; show it readably.
        $observed = trim((string) ($cur['time'] ?? ''));
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/', $observed, $tm)) {
            $month = (int) $tm[2];
            $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $observed = sprintf('%d %s %s at %s:%s local time',
                (int) $tm[3], $months[$month] ?? (string) $month, $tm[1], $tm[4], $tm[5]);
        } else {
            $observed = 'now';
        }

        $results = [[
            'source'  => 'Open-Meteo (current observation)',
            'title'   => 'Current weather in ' . $name . ($country !== '' ? ', ' . $country : ''),
            'snippet' => 'Observed ' . $observed . ': ' . $desc
                . '. Temperature ' . ($cur['temperature_2m'] ?? '?') . ' degC'
                . ' (feels like ' . ($cur['apparent_temperature'] ?? '?') . ' degC)'
                . '. Humidity ' . ($cur['relative_humidity_2m'] ?? '?') . '%'
                . '. Precipitation ' . ($cur['precipitation'] ?? '?') . ' mm'
                . '. Wind ' . ($cur['wind_speed_10m'] ?? '?') . ' km/h.',
            'url'     => 'https://open-meteo.com/',
        ]];

        web_search_cache_write($cacheKey, $results);
        return $results;
    }
}

if (!function_exists('web_search_geocode')) {
    /**
     * Resolve a place name to coordinates via Nominatim.
     *
     * Nominatim accepts non-Latin input directly, so Bengali, Devanagari,
     * Arabic and Japanese place names resolve without any local
     * transliteration table. The Latin name it reports is reused downstream.
     *
     * @return array{lat:float,lon:float,name:string,display:string}|null
     */
    function web_search_geocode(string $location, string $wikiLang = 'en'): ?array
    {
        if ($location === '') {
            return null;
        }

        $cacheKey = 'geo|' . mb_strtolower($location);
        $cached = web_search_cache_read($cacheKey, 86400);
        if ($cached !== null) {
            $place = $cached['place'] ?? null;
            return is_array($place) ? $place : null;
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=0&q='
            . rawurlencode($location);
        $data = web_search_get_json($url);

        $place = null;
        if ($data && !empty($data[0]['lat']) && !empty($data[0]['lon'])) {
            $row = $data[0];
            $place = [
                'lat'     => (float) $row['lat'],
                'lon'     => (float) $row['lon'],
                'name'    => (string) ($row['name'] ?? $location),
                'display' => (string) ($row['display_name'] ?? $location),
            ];
        }

        // Misses are cached too, so an unresolvable query is not retried
        // on every request.
        web_search_cache_write($cacheKey, ['place' => $place]);

        return $place;
    }
}

if (!function_exists('web_search_overpass')) {
    /**
     * Real named places from OpenStreetMap via Overpass: hotels, restaurants and
     * tourist attractions near a point. Only names/tags that OSM actually holds
     * are returned, so nothing here is invented.
     */
    function web_search_overpass(string $intent, string $location, int $limit = 8, string $wikiLang = 'en'): array
    {
        $place = web_search_geocode($location, $wikiLang !== '' ? $wikiLang : 'en');
        if ($place === null) {
            return [];
        }

        $selector = [
            'hotel'       => 'nwr["tourism"~"^(hotel|hostel|guest_house|motel|resort)$"]',
            'restaurant'  => 'nwr["amenity"~"^(restaurant|cafe|fast_food|bar)$"]',
            'attraction'  => 'nwr["tourism"~"^(attraction|museum|viewpoint|gallery|artwork)$"]',
        ][$intent] ?? 'nwr["tourism"="attraction"]';

        $lat = $place['lat'];
        $lon = $place['lon'];
        $query = '[out:json][timeout:25];(' . $selector
            . '(around:6000,' . $lat . ',' . $lon . '););out center ' . $limit . ';';

        // Overpass mirrors are independently rate limited and occasionally
        // stall, so try each in turn before reporting "no results".
        $data = null;
        foreach (['overpass-api.de', 'overpass.kumi.systems'] as $mirror) {
            $url = 'https://' . $mirror . '/api/interpreter?data=' . rawurlencode($query);
            $attempt = web_search_get_json($url, $mirror === 'overpass-api.de' ? 22 : 15);
            if ($attempt && !empty($attempt['elements'])) {
                $data = $attempt;
                break;
            }
            usleep(250000);
        }
        if (!$data || empty($data['elements'])) {
            return [];
        }

        $label = [
            'hotel'      => 'Hotels and places to stay',
            'restaurant' => 'Restaurants and places to eat',
            'attraction' => 'Attractions and places to visit',
        ][$intent] ?? 'Places nearby';

        $results = [];
        foreach ($data['elements'] as $el) {
            $tags = $el['tags'] ?? [];
            $name = trim((string) ($tags['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $bits = [];
            if (!empty($tags['addr:street'])) {
                $street = (string) $tags['addr:street'];
                if (!empty($tags['addr:housenumber'])) {
                    $street .= ' ' . $tags['addr:housenumber'];
                }
                $bits[] = $street;
            }
            if (!empty($tags['addr:city'])) {
                $bits[] = (string) $tags['addr:city'];
            }
            if (!empty($tags['website'])) {
                $bits[] = 'website listed';
            } elseif (!empty($tags['contact:website'])) {
                $bits[] = 'website listed';
            }

            // Only a URL that OSM actually stores may be surfaced.
            $site = (string) ($tags['website'] ?? $tags['contact:website'] ?? '');
            if ($site !== '' && !preg_match('#^https?://#i', $site)) {
                $site = 'https://' . $site;
            }

            // Fall back to the OSM object page, using the element's real type
            // so a way or relation is never linked as a node.
            $osmId = (int) ($el['id'] ?? 0);
            $osmType = (string) ($el['type'] ?? 'node');
            if (!in_array($osmType, ['node', 'way', 'relation'], true)) {
                $osmType = 'node';
            }
            $osmUrl = $osmId > 0
                ? 'https://www.openstreetmap.org/' . $osmType . '/' . $osmId
                : '';

            $results[] = [
                'source'  => 'OpenStreetMap / Overpass API',
                'title'   => $name . ($bits ? ' — ' . implode(', ', $bits) : ''),
                'snippet' => $label . ' near ' . $place['display']
                    . '. Listed in OpenStreetMap. Data source: OpenStreetMap contributors (ODbL).'
                    . ($site !== '' ? ' Official site listed in the map data.' : ''),
                'url'     => $site !== ''
                    ? $site
                    : $osmUrl,
            ];
        }

        $results = array_values(array_filter(
            $results,
            static fn(array $r): bool => ($r['url'] ?? '') !== ''
        ));

        if ($results) {
            web_search_cache_write('osm|' . $intent . '|' . mb_strtolower($location), $results);
        }
        return $results;
    }
}

if (!function_exists('web_search_wikipedia')) {
    /**
     * Wikipedia search + summary in the user's language. Gives sourced facts and
     * a real article URL for general questions such as "capital of France".
     */
    function web_search_wikipedia(string $query, string $wikiLang): array
    {
        if (trim($query) === '') {
            return [];
        }

        // Search the topic, not the sentence, otherwise
        // "What is the capital of France?" matches unrelated articles.
        $query = web_search_wikipedia_query($query);

        $lang = preg_match('/^[a-z]{2,3}(-[a-z]{2,4})?$/i', $wikiLang) ? $wikiLang : 'en';
        $cacheKey = 'wiki|' . $lang . '|' . mb_strtolower($query);
        $cached = web_search_cache_read($cacheKey, 21600);
        if ($cached !== null) {
            return $cached;
        }

        $api = 'https://' . $lang . '.wikipedia.org/w/api.php?action=query&format=json&origin=*'
            . '&list=search&srlimit=3&srsearch=' . rawurlencode($query);

        $data = web_search_get_json($api);
        $hits = $data['query']['search'] ?? [];
        if (!$hits) {
            // Fall back to English when the localized edition has no match.
            if ($lang !== 'en') {
                return web_search_wikipedia($query, 'en');
            }
            return [];
        }

        $title = (string) ($hits[0]['title'] ?? '');
        if ($title === '') {
            return [];
        }

        $summaryUrl = 'https://' . $lang . '.wikipedia.org/api/rest_v1/page/summary/'
            . str_replace(' ', '_', rawurlencode($title));

        $summary = web_search_get_json($summaryUrl);
        $extract = trim((string) ($summary['extract'] ?? ''));
        if ($extract === '') {
            $extract = trim((string) ($hits[0]['snippet'] ?? ''));
            $extract = preg_replace('/<[^>]+>/', '', $extract) ?? $extract;
        }
        if ($extract === '') {
            return [];
        }

        // Keep the extract compact for the prompt.
        if (mb_strlen($extract) > 900) {
            $extract = trim(mb_substr($extract, 0, 900)) . '...';
        }

        $pageUrl = (string) ($summary['content_urls']['desktop']['page']
            ?? 'https://' . $lang . '.wikipedia.org/wiki/' . str_replace(' ', '_', rawurlencode($title)));

        $results = [[
            'source'  => 'Wikipedia (' . $lang . ')',
            'title'   => $title,
            'snippet' => $extract,
            'url'     => $pageUrl,
        ]];

        web_search_cache_write($cacheKey, $results);
        return $results;
    }
}

if (!function_exists('web_search_duckduckgo')) {
    /**
     * DuckDuckGo Instant Answer (key-less): abstract text plus related topics
     * with real source URLs.
     */
    function web_search_duckduckgo(string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        $cacheKey = 'ddg|' . mb_strtolower($query);
        $cached = web_search_cache_read($cacheKey, 21600);
        if ($cached !== null) {
            return $cached;
        }

        $url = 'https://api.duckduckgo.com/?format=json&no_html=1&no_redirect=1&skip_disambig=1&q='
            . rawurlencode($query);

        $data = web_search_get_json($url);
        if (!$data) {
            return [];
        }

        $results = [];

        // DuckDuckGo returns markup in Heading/Text fields; strip it so the
        // evidence handed to the model is plain text.
        $plain = static function (?string $s): string {
            $s = trim((string) $s);
            if ($s === '') {
                return '';
            }
            $s = preg_replace('#<br\s*/?>#i', ' ', $s) ?? $s;
            $s = strip_tags($s) ?? $s;
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        };

        $abstract = $plain($data['AbstractText'] ?? '');
        // Only a URL DuckDuckGo actually returned may be surfaced.
        $abstractUrl = trim((string) ($data['AbstractURL'] ?? ''));
        if ($abstract !== '' && $abstractUrl !== '') {
            if (mb_strlen($abstract) > 900) {
                $abstract = trim(mb_substr($abstract, 0, 900)) . '...';
            }
            $heading = $plain($data['Heading'] ?? '');
            $results[] = [
                'source'  => 'DuckDuckGo Instant Answer',
                'title'   => $heading !== '' ? $heading : $query,
                'snippet' => $abstract,
                'url'     => $abstractUrl,
            ];
        }

        foreach (($data['RelatedTopics'] ?? []) as $topic) {
            if (!is_array($topic)) {
                continue;
            }
            $text = $plain($topic['Text'] ?? '');
            $firstUrl = trim((string) ($topic['FirstURL'] ?? ''));
            // Require a real provider URL; never construct one.
            if ($text === '' || $firstUrl === '') {
                continue;
            }
            if (mb_strlen($text) > 400) {
                $text = trim(mb_substr($text, 0, 400)) . '...';
            }
            $title = $plain($topic['Result'] ?? '');
            $results[] = [
                'source'  => 'DuckDuckGo Instant Answer',
                'title'   => $title !== '' ? $title : $query,
                'snippet' => $text,
                'url'     => $firstUrl,
            ];
            if (count($results) >= 5) {
                break;
            }
        }

        if ($results) {
            web_search_cache_write($cacheKey, $results);
        }
        return $results;
    }
}

if (!function_exists('web_search_run')) {
    /**
     * Run the retrieval step.
     *
     * @return array{
     *   status:string,          // 'ok' | 'empty' | 'error'
     *   results:array,          // list of ['source','title','snippet','url']
     *   source_status:array     // per-source outcome, for honest fallbacks
     * }
     */
    function web_search_run(string $message, string $intent, string $location, array $language): array
    {
        $query = web_search_build_query($message, $intent, $location);
        $results = [];
        $sourceStatus = [];

        // 1. Weather intent is served by a live observation.
        if ($intent === 'weather') {
            if ($location === '') {
                $sourceStatus['weather'] = 'needs_location';
            } else {
                $w = web_search_weather($location, $language['wiki'] ?? 'en');
                $sourceStatus['weather'] = $w ? 'ok' : 'no_result';
                $results = array_merge($results, $w);
            }
            // Weather can still benefit from a place description.
            if ($location !== '') {
                foreach (web_search_wikipedia($location, $language['wiki']) as $r) {
                    $results[] = $r;
                    $sourceStatus['wikipedia'] = 'ok';
                }
            }
        }

        // 2. Place-based intents are served by real OpenStreetMap POIs.
        if (in_array($intent, ['hotel', 'restaurant', 'attraction'], true) && $location !== '') {
            $osm = web_search_overpass($intent, $location, 8, $language['wiki'] ?? 'en');
            $sourceStatus['openstreetmap'] = $osm ? 'ok' : 'no_result';
            $results = array_merge($results, $osm);
        }

        // 3. Wikipedia for encyclopaedic facts.
        if (!in_array($intent, ['weather'], true)) {
            $wiki = web_search_wikipedia($query, $language['wiki']);
            $sourceStatus['wikipedia'] = $wiki ? 'ok' : 'no_result';
            $results = array_merge($results, $wiki);
        }

        // 4. DuckDuckGo Instant Answer as a supplementary key-less source.
        $ddg = web_search_duckduckgo($query);
        $sourceStatus['duckduckgo'] = $ddg ? 'ok' : 'no_result';
        $results = array_merge($results, $ddg);

        // De-duplicate by URL, then cap the payload.
        $seen = [];
        $unique = [];
        foreach ($results as $r) {
            $key = $r['url'] ?? ($r['title'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $r;
        }
        $unique = array_slice($unique, 0, 8);

        if ($unique) {
            return ['status' => 'ok', 'results' => $unique, 'source_status' => $sourceStatus];
        }

        // Distinguish "service broken" from "genuinely nothing found".
        $allFailed = array_values($sourceStatus);
        $reached = array_filter($allFailed, fn($s) => $s === 'ok' || $s === 'no_result');
        $status = empty($reached) ? 'error' : 'empty';

        return ['status' => $status, 'results' => [], 'source_status' => $sourceStatus];
    }
}