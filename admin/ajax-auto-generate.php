<?php
/**
 * Auto-Generator AJAX Handler (Smart Bengali NLP & Gemini AI)
 * Alokito Gour Clone
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/auth_guard.php';

// Verify admin is authenticated
if (empty($_SESSION['admin_user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized request! Please log in.']);
    exit;
}

$db = get_db();
$settings = get_settings();

$action = trim($_POST['action'] ?? 'generate');

// =========================================================================
// Action 1: Test Gemini Connection
// =========================================================================
if ($action === 'test_gemini') {
    $apiKey = trim($_POST['api_key'] ?? ($settings['gemini_api_key'] ?? ''));
    $model = trim($_POST['model'] ?? ($settings['gemini_model'] ?? 'gemini-1.5-flash'));

    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'error' => 'No Gemini API Key provided!']);
        exit;
    }

    $result = call_gemini_api($apiKey, $model, "Say 'OK' in Bengali");
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Google Gemini API connection successful! Model: ' . $model
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Gemini API connection failed: ' . $result['error']
        ]);
    }
    exit;
}

// =========================================================================
// Action 2: Generate Title & Keywords from News Content
// =========================================================================
$contentHtml = trim($_POST['content'] ?? '');
$plainContent = trim(strip_tags($contentHtml));

if (empty($plainContent)) {
    echo json_encode(['success' => false, 'error' => 'Please enter or paste the full news content first.']);
    exit;
}

// Check which provider is configured
$activeProvider = $settings['ai_provider'] ?? 'smart';
$geminiApiKey = trim($settings['gemini_api_key'] ?? '');
$geminiModel = trim($settings['gemini_model'] ?? 'gemini-1.5-flash');

// User can also override provider in request if testing
$requestedProvider = trim($_POST['provider'] ?? '');
if (in_array($requestedProvider, ['smart', 'gemini'], true)) {
    $activeProvider = $requestedProvider;
}

$title = '';
$excerpt = '';
$keywords = '';
$usedProvider = 'smart';
$notice = '';

// Try Gemini if selected and API key is present
if ($activeProvider === 'gemini' && !empty($geminiApiKey)) {
    $prompt = <<<EOT
You are an experienced professional Bangladeshi news portal chief editor for 'Alokito Gour' (আলোকিত গৌড়).
Read and analyze the following Bengali news body, then generate:
1. "title": A compelling, accurate, standard Bengali headline suitable for a modern news portal (8 to 15 words).
2. "excerpt": A concise, clear summary of the news in Bengali (1-2 sentences, max 160 characters).
3. "keywords": 5 to 7 high-impact Bengali keywords or tags separated strictly by comma (e.g. চাঁপাইনবাবগঞ্জ, কৃষি, আম, অর্থনীতি, সরকার).

STRICT RULE:
Return ONLY valid JSON without markdown wrapping or backticks. Format:
{"title": "...", "excerpt": "...", "keywords": "..."}

News Body:
{$plainContent}
EOT;

    $geminiRes = call_gemini_api($geminiApiKey, $geminiModel, $prompt);

    if ($geminiRes['success']) {
        $rawText = trim($geminiRes['text']);
        // Remove markdown ```json ``` wraps if present
        $rawText = preg_replace('/^```(?:json)?\s*/i', '', $rawText);
        $rawText = preg_replace('/\s*```$/i', '', $rawText);
        $parsed = json_decode($rawText, true);

        if (json_last_error() === JSON_ERROR_NONE && !empty($parsed['title'])) {
            $title = trim($parsed['title'] ?? '');
            $excerpt = trim($parsed['excerpt'] ?? '');
            $keywords = trim($parsed['keywords'] ?? '');
            $usedProvider = 'gemini';
        } else {
            // Fallback to smart if JSON couldn't be parsed
            $notice = 'Smart algorithm was used as fallback because Gemini output formatting could not be parsed.';
        }
    } else {
        $notice = 'Gemini API notice (' . $geminiRes['error'] . ')! Automatically switched to built-in smart algorithm.';
    }
}

// Fallback to Smart Built-in Algorithm
if (empty($title) || empty($keywords)) {
    $smartResult = smart_bengali_generate($plainContent);
    $title = $title ?: $smartResult['title'];
    $excerpt = $excerpt ?: $smartResult['excerpt'];
    $keywords = $keywords ?: $smartResult['keywords'];
    $usedProvider = ($usedProvider === 'gemini' && empty($notice)) ? 'gemini' : 'smart';
}

echo json_encode([
    'success'       => true,
    'provider'      => $usedProvider,
    'provider_name' => ($usedProvider === 'gemini') ? 'Google Gemini AI' : 'Built-in Smart Bengali NLP',
    'title'         => $title,
    'excerpt'       => $excerpt,
    'keywords'      => $keywords,
    'notice'        => $notice
], JSON_UNESCAPED_UNICODE);
exit;

// =========================================================================
// Helper Function: Call Gemini REST API via cURL
// =========================================================================
function call_gemini_api(string $apiKey, string $model, string $prompt): array {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 600,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($curlErr)) {
        return ['success' => false, 'error' => $curlErr ?: 'Connection timed out'];
    }

    $json = json_decode($response, true);
    if ($httpCode !== 200) {
        $msg = $json['error']['message'] ?? ('HTTP Code: ' . $httpCode);
        return ['success' => false, 'error' => $msg];
    }

    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($text)) {
        return ['success' => false, 'error' => 'Gemini returned an empty response'];
    }

    return ['success' => true, 'text' => $text];
}

// =========================================================================
// Helper Function: Smart Bengali NLP Headline & Keywords Generator
// =========================================================================
function smart_bengali_generate(string $content): array {
    // 1. Generate Excerpt (First 1-2 clean sentences or 160 chars)
    $cleanText = preg_replace('/\s+/u', ' ', $content);
    $cleanText = trim($cleanText);

    // Split sentences by Bengali danda (।) or newline
    $sentences = preg_split('/[।\n\r!?]+/u', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
    $firstSentence = trim($sentences[0] ?? '');
    
    // Title generation from first sentence
    $words = preg_split('/\s+/u', $firstSentence, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) > 13) {
        $title = implode(' ', array_slice($words, 0, 11)) . '...';
    } else {
        $title = $firstSentence;
    }
    // Clean trailing punctuation using UTF-8 safe regex
    $title = preg_replace('/^[\s।,!?\-—]+|[\s।,!?\-—]+$/u', '', $title);

    // Excerpt
    if (count($sentences) > 1) {
        $excerptCandidate = trim($sentences[0]) . '। ' . trim($sentences[1]) . '।';
    } else {
        $excerptCandidate = $firstSentence . '।';
    }
    if (mb_strlen($excerptCandidate, 'UTF-8') > 175) {
        $excerpt = mb_substr($excerptCandidate, 0, 172, 'UTF-8') . '...';
    } else {
        $excerpt = $excerptCandidate;
    }

    // 2. Keyword Extraction with Bengali Stop-Words Elimination
    $stopWords = [
        'এবং', 'ও', 'বা', 'কিন্তু', 'তবে', 'যদি', 'করে', 'করা', 'হয়েছে', 'হয়েছে।', 'হয়ে', 'হবে', 'হয়', 'হতে',
        'থেকে', 'চেয়ে', 'জন্য', 'নিয়ে', 'দিয়ে', 'দ্বারা', 'কর্তৃক', 'সাথে', 'সহ', 'পরে', 'পর', 'আগে',
        'এই', 'সেই', 'ওই', 'একটি', 'এক', 'দুটি', 'দুই', 'তিন', 'কিছু', 'অনেক', 'সকল', 'সব', 'যেকোনো', 'কোনো',
        'যে', 'সে', 'তিনি', 'তার', 'তাঁর', 'তাদের', 'তাঁদের', 'আমরা', 'আমাদের', 'আপনি', 'আপনার', 'তুমি', 'তোমার',
        'আছে', 'ছিল', 'থাকে', 'থাকবে', 'গেছে', 'গেল', 'বলে', 'বলেন', 'জানান', 'জানানো', 'বলা', 'দেওয়া', 'দেয়া',
        'না', 'নয়', 'নেই', 'নাকি', 'কী', 'কি', 'কেন', 'কোথায়', 'কবে', 'কীভাবে', 'কত',
        'মধ্যে', 'ভেতরে', 'উপরে', 'নিচে', 'পাশে', 'কাছে', 'দিকে', 'প্রতি', 'ব্যাপারে', 'বিষয়ে', 'সময়',
        'আজ', 'গতকাল', 'আগামীকাল', 'এখন', 'তখন', 'তখনই', 'এখনই', 'মাত্র', 'শুধু', 'আর', 'আরও', 'খুব', 'বেশি',
        'কম', 'বেশ', 'মোট', 'প্রায়', 'ইত্যাদি', 'প্রমুখ', 'অন্যান্য', 'পর্যন্ত', 'বিনা', 'ছাড়া', 'ব্যতীত',
        'হিসেবে', 'হিসাবে', 'রূপে', 'কারণে', 'ফলে', 'ফলে।', 'অধীন', 'অনুযায়ী', 'মতে', 'সূত্র', 'সূত্রে',
        'চলতি', 'গত', 'বর্তমান', 'হচ্ছে', 'পাওয়া', 'যাওয়া', 'আসা', 'উঠে', 'পড়ে', 'বসে', 'দেখা'
    ];
    $stopMap = array_fill_keys($stopWords, true);

    // Important entity words (districts, regional words, common news terms) to give priority
    $priorityEntities = [
        'চাঁপাইনবাবগঞ্জ' => 5,
        'শিবগঞ্জ'        => 5,
        'নাচোল'          => 5,
        'গোমস্তাপুর'     => 5,
        'ভোলাহাট'        => 5,
        'রাজশাহী'        => 4,
        'ঢাকা'           => 3,
        'বাংলাদেশ'       => 3,
        'আম'             => 4,
        'কৃষি'           => 4,
        'অর্থনীতি'       => 4,
        'বাণিজ্য'        => 4,
        'শিক্ষা'         => 4,
        'স্বাস্থ্য'      => 4,
        'রাজনীতি'        => 4,
        'আইনশৃঙ্খলা'     => 4,
        'পুলিশ'          => 4,
        'আদালত'          => 4,
        'সরকার'          => 4,
        'প্রশাসন'        => 4,
        'উন্নয়ন'        => 4,
        'দুর্ঘটনা'       => 4,
        'নির্বাচন'       => 4,
        'বাজেট'          => 4,
        'সংস্কৃতি'       => 4,
    ];

    // Clean text and extract all Bengali words
    $normalized = preg_replace('/[^\p{Bengali}\s]/u', ' ', $cleanText);
    $rawTokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

    $wordScores = [];
    foreach ($rawTokens as $word) {
        $w = trim($word);
        if (mb_strlen($w, 'UTF-8') < 3) {
            continue;
        }
        if (isset($stopMap[$w])) {
            continue;
        }

        $baseScore = 1;
        if (isset($priorityEntities[$w])) {
            $baseScore += $priorityEntities[$w];
        }

        $wordScores[$w] = ($wordScores[$w] ?? 0) + $baseScore;
    }

    arsort($wordScores);
    $topWords = array_slice(array_keys($wordScores), 0, 6);

    // Ensure at least default context if none found
    if (empty($topWords)) {
        $topWords = ['চাঁপাইনবাবগঞ্জ', 'সংবাদ', 'জাতীয়'];
    }

    $keywords = implode(', ', $topWords);

    return [
        'title'   => $title ?: 'আজকের বিশেষ সংবাদ শিরোনাম',
        'excerpt' => $excerpt ?: mb_substr($cleanText, 0, 160, 'UTF-8') . '...',
        'keywords'=> $keywords
    ];
}
