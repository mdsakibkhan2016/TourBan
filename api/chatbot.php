<?php

/**
 * Chatbot API Endpoint
 * Proxies chat requests to the xAI Grok API.
 * The API key stays on the server and is never exposed to the browser.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/env.php';

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

$apiKey = env('XAI_API_KEY');
if (!$apiKey || $apiKey === 'your_grok_api_key_here') {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'AI assistant is not configured. Set XAI_API_KEY on the server.'
    ]);
    exit;
}

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

    $apiBase = rtrim((string) env('XAI_API_BASE', 'https://api.x.ai/v1'), '/');
    $model = env('XAI_MODEL', 'grok-2-latest');

    $systemPrompt = 'You are TourBan, a friendly travel assistant for the TourBan tourism website. '
        . 'Help users with destinations (Rome, Santorini, Bali, Paris, Tokyo, etc.), tours, hotels, '
        . 'travel guides, transportation, adventure tours, and event planning. '
        . 'Be concise and helpful. Respond in the same language as the user.';

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message],
        ],
        'temperature' => 0.7,
        'max_tokens' => 1024,
    ];

    $ch = curl_init($apiBase . '/chat/completions');
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
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('[TourBan] Grok API curl error: ' . $curlError);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Unable to reach the AI service. Please try again.']);
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[TourBan] Grok API HTTP ' . $httpCode . ': ' . $response);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'AI service returned an error. Please try again.']);
        exit;
    }

    $reply = $data['choices'][0]['message']['content']
        ?? $data['choices'][0]['text']
        ?? null;

    if (!$reply) {
        error_log('[TourBan] Grok API unexpected response: ' . $response);
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
