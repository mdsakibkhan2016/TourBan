<?php

if (!function_exists('is_conversational_message')) {
    function is_conversational_message(string $message): bool
    {
        $text = trim($message);
        if ($text === '') {
            return true;
        }

        $lower = ' ' . mb_strtolower($text, 'UTF-8') . ' ';

        $patterns = [
            '/\b(hello|hi|hey|hola|hallo|bonjour|ciao|salam|assalamu|alaikum|namaste)\b/i',
            '/^(hi|hello|hey)[\s!\.,]*$/i',
            '/\b(thank|thanks|thnx|tnx|dhonnobad|dhanbad|shukriya|shukria|dhanyabad|arigato)\b/i',
            '/^(ok|okay|k|kk|thik|thik ache|tik ache|accha|acha|theek|hmm|hm)\b/i',
            '/\b(yes|no|yeah|yep|nope|nai|na|hya|ha|haan|han)\b$/i',
            '/\b(bujhlam|bujhsi|understand|got it|gotcha)\b/i',
            '/\b(confused|confuse|bujhte parchi na|bujhte parche na|bujhtesi na)\b/i',
            '/\b(help|help me|help chai|ekta help chai|ekta jinish jante chai|ekta jinis jante chai|kichu jante chai|kisu jante chai|jante chai|can you help|could you help|will you help)\b/i',
            '/\b(sorry|excuse me|oops)\b/i',
            '/\b(good morning|good evening|good night|good afternoon|bye|bye bye|tata|allah hafiz)\b/i',
            '/^(how are you|how r u|kemon acho|kemon asen|kemn acho|apni kemon|tumi kemon)[\?!\.]*$/i',
            '/\b(who are you|what are you|what can you do)\b/i',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $lower)) {
                return true;
            }
        }

        $words = preg_split('/\s+/u', $text);
        if (count($words) <= 3) {
            $short = implode(' ', $words);
            if (preg_match('/^(hi|hello|hey|thanks|thank you|ok|okay|thik ache|bujhlam|help)$/iu', $short)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('get_conversational_reply')) {
    function get_conversational_reply(string $message): string
    {
        $text = trim($message);
        $lower = mb_strtolower($text, 'UTF-8');

        if (preg_match('/\b(hello|hi|hey)\b/i', $lower)) {
            return 'Hello! How can I help you with your travel plans?';
        }
        if (preg_match('/\b(salam|assalamu)\b/i', $lower)) {
            return 'ওয়ালাইকুম সালাম! কীভাবে সাহায্য করতে পারি?';
        }
        if (preg_match('/\b(namaste)\b/i', $lower)) {
            return 'Namaste! What can I help you with today?';
        }
        if (preg_match('/\b(good morning)\b/i', $lower)) {
            return 'Good morning! How can I help you today?';
        }
        if (preg_match('/\b(good evening)\b/i', $lower)) {
            return 'Good evening! What would you like to know about travel?';
        }
        if (preg_match('/\b(good night)\b/i', $lower)) {
            return 'Good night! Have a great trip ahead!';
        }

        if (preg_match('/\b(thank|dhonnobad|shukriya)\b/i', $lower)) {
            return 'You\'re welcome! Is there anything else I can help you with?';
        }

        if (preg_match('/how (are|r) you/i', $lower) || preg_match('/kemon (acho|asen)/i', $lower) || preg_match('/apni kemon|tumi kemon/i', $lower)) {
            return 'I\'m doing great, thank you! How can I help you with your travel plans today?';
        }

        if (preg_match('/who are you|what are you/i', $lower)) {
            return 'I\'m TourBan AI Travel Assistant. I can help you find hotels, restaurants, places to visit, weather information, or book TourBan tour packages!';
        }
        if (preg_match('/what can you do/i', $lower)) {
            return 'I can help you with hotel searches, restaurant recommendations, tourist attractions, weather updates, travel information, and TourBan tour bookings. What would you like to do?';
        }

        if (preg_match('/confused|bujhte parchi na|bujhte parche na/i', $lower)) {
            return 'No problem. Tell me what you\'re trying to do, and I\'ll help you step by step.';
        }
        if (preg_match('/help|help chai|ekta help chai/i', $lower)) {
            return 'অবশ্যই। কী ধরনের travel বা tour information নিয়ে সাহায্য চান?';
        }
        if (preg_match('/ekta jinish jante chai|kichu jante chai|jante chai/i', $lower)) {
            return 'অবশ্যই। কী জানতে চান?';
        }

        if (preg_match('/^(yes|yeah|yep|haan|ha|hya)$/i', trim($text))) {
            return 'ঠিক আছে। বলুন, কী করতে পারি?';
        }
        if (preg_match('/^(no|nope|na|nai)$/i', trim($text))) {
            return 'ঠিক আছে। যদি কিছু লাগবে, বলবেন।';
        }

        if (preg_match('/\b(okay|ok|thik ache|tik ache|bujhlam|got it|gotcha)\b/i', $lower)) {
            return 'ঠিক আছে। কী নিয়ে এগোতে চান?';
        }

        if (preg_match('/\b(bye|tata|allah hafiz)\b/i', $lower)) {
            return 'Goodbye! Safe travels!';
        }

        return 'অবশ্যই। কী ধরনের সাহায্য চান?';
    }
}
