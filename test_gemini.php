<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = 'AIzaSyBdfQu0DeSFjQVndiFWGjvjcD-aL5eqqCQ';
$baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

$prompt = <<<EOT
You are an expert nutritionist and fitness coach AI for the "Tigagit" app.
Analyze the following user data and provide personalized feedback in JSON format.

**User Profile:**
- Name: Test User
- Goal: Weight Loss
- Activity Level: Moderate
- Dietary Preference: Halal

**Daily Summary:**
- Calories: 1800 / 2000 (Net: 1800)
- Protein: 80g / 100g
- Carbs: 200g / 250g
- Fat: 60g / 70g

**Task:**
1. Analyze the intake vs target.
2. Provide a motivational and actionable feedback message (max 2 sentences).
3. Suggest 2 specific foods to eat (if under target) or avoid (if over target).
4. Suggest 1 specific exercise based on the net calorie status.

**Output JSON Format:**
{
    "feedback_message": "Your message here.",
    "suggested_foods": ["Food 1", "Food 2"],
    "suggested_exercises": ["Exercise 1"],
    "macro_analysis": {
        "protein_status": "Low/Good/High",
        "carbs_status": "Low/Good/High",
        "fat_status": "Low/Good/High"
    }
}
EOT;

echo "Testing Gemini API...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Model: gemini-2.5-flash\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '?key=' . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 8192,
        'responseMimeType' => 'application/json',
    ]
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    echo "Full Raw API Response:\n";
    echo $response . "\n\n";

    $data = json_decode($response, true);
    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    if ($rawText) {
        echo "Extracted Text:\n";
        echo $rawText . "\n\n";
        
        $feedback = json_decode($rawText, true);
        echo "Parsed Feedback:\n";
        print_r($feedback);
    } else {
        echo "Failed to extract text from response.\n";
    }
} else {
    echo "Error Response:\n";
    echo $response . "\n";
}
