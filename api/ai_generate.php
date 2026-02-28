<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Helper to get API Key
function getGeminiKey($pdo) {
    $stmt = $pdo->query("SELECT setting_value FROM core_settings WHERE setting_key = 'google_gemini_api_key'");
    return $stmt->fetchColumn();
}

$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';

if (empty($prompt)) {
    echo json_encode(['success' => false, 'message' => 'Prompt tidak boleh kosong']);
    exit;
}

$apiKey = getGeminiKey($pdo);
if (!$apiKey) {
    echo json_encode(['success' => false, 'message' => 'API Key Google Gemini belum disetting di Admin']);
    exit;
}

// Construct the system prompt
$systemPrompt = "You are an AI assistant for a classroom TV display. 
Your task is to generate EDUCATIONAL content based on the user's prompt.
The output MUST be raw HTML format suitable for a large 16:9 screen.
Use Tailwind CSS classes for styling.
- Use large fonts (text-2xl, text-4xl, etc).
- Use distinct colors for headings and key points.
- If the user asks for a formula, format it clearly.
- If the user asks for a visualization (like ecosystem, hierarchy, or flow), ALWAYS use Mermaid JS syntax inside a <div class='mermaid'> tag. 
  Example: <div class='mermaid'>graph LR; A-->B;</div>
  Do NOT use code blocks for mermaid, put it directly in the div.
- Do NOT wrap the output in markdown code blocks (like ```html ... ```). Just return the HTML content.
- Ensure the background is transparent or neutral so it blends with the TV theme (or set a nice card background).
- Content should be concise and readable from a distance.
- Language: Indonesian (unless prompt is in English).";

$fullPrompt = $systemPrompt . "\n\nUser Prompt: " . $prompt;

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $fullPrompt]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => 'Gagal menghubungi Google AI: ' . $response]);
    exit;
}

$result = json_decode($response, true);
$generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Sanitize Markdown blocks if Gemini ignores instruction
$generatedText = preg_replace('/^```html\s*/i', '', $generatedText);
$generatedText = preg_replace('/\s*```$/', '', $generatedText);

if (empty($generatedText)) {
    echo json_encode(['success' => false, 'message' => 'AI tidak menghasilkan konten.']);
    exit;
}

echo json_encode(['success' => true, 'content' => $generatedText]);
?>