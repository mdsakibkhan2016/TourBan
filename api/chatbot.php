<?php

/**
 * Chatbot API Endpoint
 * Proxies chat requests to the Groq Cloud API (OpenAI-compatible).
 * The API key stays on the server and is never exposed to the browser.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/security.php';

// Same-origin only
$origin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin .= '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
if (!isset($_SERVER['HTTP_ORIGIN']) || rtrim($_SERVER['HTTP_ORIGIN'], '/') === rtrim($origin, '/')) {
    // Origin matches or absent (same-origin navigational request) — allow.
} else {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Cross-origin request blocked']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_csrf();

// Per-session throttle: 30 messages / 5 minutes (protects the AI quota)
secure_session_start();
$chatKey = 'chat:' . session_id();
if (rate_limit_exceeded($chatKey, 30, 300)) {
    json_error(429, 'Too many messages right now. Please wait a moment and try again.');
}
rate_limit_record($chatKey, 30, 300);

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input) || !isset($input['message']) || !is_string($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    $message = trim($input['message']);

    if ($message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }

    if (mb_strlen($message) > 2000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is too long (max 2000 characters)']);
        exit;
    }

    // Optional conversation history (sanitized, capped).
    $history = [];
    if (isset($input['history'])) {
        if (!is_array($input['history'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'History must be an array']);
            exit;
        }

        foreach ($input['history'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $role = $item['role'] ?? '';
            $content = $item['content'] ?? '';
            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content)) {
                continue;
            }
            $content = trim($content);
            if ($content === '') {
                continue;
            }
            if (mb_strlen($content) > 2000) {
                $content = mb_substr($content, 0, 2000);
            }
            $history[] = ['role' => $role, 'content' => $content];
        }

        // Keep only the most recent turns to bound token usage.
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
    }

    $apiKey = env('GROQ_API_KEY');
    if (!$apiKey || $apiKey === 'your_groq_api_key_here') {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'AI assistant is not configured.'
        ]);
        exit;
    }

    $model = env('GROQ_MODEL', 'llama-3.1-8b-instant');
    $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

    $systemPrompt = 'You are TourBan Travel Assistant, the official AI concierge of TourBan, '
        . 'a modern tourism and travel booking platform.\n\n'
        . 'Your role:\n'
        . '- Advise travelers on destinations, itineraries, best travel seasons, budgets, flights, '
        . 'hotels, local transport, visas, safety, and cultural tips.\n'
        . '- TourBan featured destinations: Rome (Italy, 7 days, from $599), Santorini (Greece, 5 days, '
        . 'from $799), Bali (Indonesia, 6 days, from $499), Paris (France, from $899), Tokyo (Japan, '
        . 'from $1099), Maldives (from $1299).\n'
        . '- When a user wants to book, tell them to pick their destination on the Destinations page and '
        . 'complete the booking form (they must sign in first). Bookings are confirmed instantly with a '
        . 'reference code starting with TB-.\n'
        . '- Recommend browsing Destinations for the full catalog and Contact us for custom or group trips.\n\n'
        . 'Style:\n'
        . '- Be warm, concise, and practical; answer in the same language as the user.\n'
        . '- Prefer short paragraphs and simple lists; suggest 2-3 concrete options when possible.\n'
        . '- Never invent prices or availability beyond the figures above; say "check the Destinations '
        . 'page for current pricing" otherwise.\n'
        . '- You cannot access the user\'s account, payments, or bookings. For booking status questions, '
        . 'direct them to their dashboard.\n'
        . '- Stay on travel and tourism topics; politely decline unrelated or harmful requests.';

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];
    foreach ($history as $h) {
        $messages[] = $h;
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 1024,
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('[TourBan] Groq API curl error: ' . $curlError);

        if ($curlErrno === CURLE_OPERATION_TIMEOUTED || $curlErrno === CURLE_COULDNT_CONNECT) {
            http_response_code(504);
            echo json_encode(['success' => false, 'message' => 'The AI service timed out. Please try again.']);
        } else {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => 'Unable to reach the AI service. Please try again.']);
        }
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode === 401 || $httpCode === 403) {
        // Invalid/revoked key — never echo the key or raw auth body to the client.
        error_log('[TourBan] Groq API auth failed (HTTP ' . $httpCode . ')');
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'AI assistant authentication failed. Please contact the site admin.']);
        exit;
    }

    if ($httpCode === 429) {
        error_log('[TourBan] Groq API rate limited (HTTP 429)');
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'The AI assistant is busy right now. Please try again in a moment.']);
        exit;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[TourBan] Groq API HTTP ' . $httpCode);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'AI service returned an error. Please try again.']);
        exit;
    }

    $reply = $data['choices'][0]['message']['content']
        ?? $data['choices'][0]['text']
        ?? null;

    if (!$reply) {
        error_log('[TourBan] Groq API unexpected response shape');
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Unexpected AI response. Please try again.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'reply'   => $reply,
    ]);
} catch (Exception $e) {
    error_log('[TourBan] Chatbot error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
