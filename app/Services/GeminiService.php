<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateFeedback($context)
    {
        $prompt = $this->constructPrompt($context);
        return $this->callGemini($prompt);
    }

    public function generateWeeklyInsights($context)
    {
        $user = $context['user'];
        $assessment = $context['assessment'];
        $daysTracked = $context['days_tracked'];
        $avgCalories = $context['average_calories'];
        $consistency = $context['consistency_score'];

        $prompt = <<<EOT
You are an expert fitness coach AI.
Analyze the weekly progress for {$user->name}.

**Context:**
- Goal: {$assessment->health_goal}
- Days Tracked: {$daysTracked}/7
- Average Calories: {$avgCalories} (Target: {$assessment->daily_calorie_target})
- Consistency Score: {$consistency}

**Task:**
Provide a short, encouraging weekly insight (max 2 sentences) focusing on their consistency and calorie adherence.
IMPORTANT: Provide the response in Indonesian language.

**Output JSON Format:**
{
    "insight": "Your insight here in Indonesian."
}
EOT;

        return $this->callGemini($prompt)['insight'] ?? "Pertahankan kerja bagus! Konsistensi adalah kunci.";
    }

    protected function callGemini($prompt)
    {
        Log::info('GeminiService: Preparing to call Gemini API', [
            'url' => $this->baseUrl,
            'prompt_preview' => substr($prompt, 0, 100)
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
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
            ]);

            Log::info('GeminiService: API Response Status', ['status' => $response->status()]);

            if ($response->failed()) {
                Log::error('Gemini API Error: ' . $response->body());
                return $this->fallbackFeedback();
            }

            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            
            Log::info('GeminiService: Raw Response Text', ['text' => substr($rawText, 0, 200)]);

            return json_decode($rawText, true) ?? $this->fallbackFeedback();

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return $this->fallbackFeedback();
        }
    }

    protected function constructPrompt($context)
    {
        $user = $context['user'];
        $assessment = $context['assessment'];
        $intake = $context['intake'];
        $target = $context['target'];
        $netCalories = $context['net_calories'];

        return <<<EOT
You are an expert nutritionist and fitness coach AI for the "Tigagit" app.
Analyze the following user data and provide personalized feedback in JSON format.

**User Profile:**
- Name: {$user->name}
- Goal: {$assessment->health_goal}
- Activity Level: {$assessment->activity_level}
- Dietary Preference: {$assessment->dietary_preference}

**Daily Summary:**
- Calories: {$intake['calories']} / {$target['calories']} (Net: {$netCalories})
- Protein: {$intake['protein']}g / {$target['protein']}g
- Carbs: {$intake['carbs']}g / {$target['carbs']}g
- Fat: {$intake['fat']}g / {$target['fat']}g

**Task:**
1. Analyze the intake vs target.
2. Provide a motivational and actionable feedback message (max 2 sentences).
3. Suggest 2 specific foods to eat (if under target).
4. Suggest 2 specific foods to avoid (if over target).
5. Suggest 1 specific exercise based on the net calorie status.

**IMPORTANT STYLE GUIDE (GEN Z / TEENAGE FRIENDLY - INDONESIAN):**
- Use "Gen Z" slang and relaxed Indonesian language (e.g., "ngadi-ngadi", "flexing", "healing", "spill the tea", "red flag", "sat-set", "mager", "bestie", "guys").
- ALWAYS mention the user's name ({$user->name}) in the feedback.
- Be fun, encouraging, but informative.
- Example: "Rico, santai! Tapi data kita lagi ngadi-ngadi nih. Kalori, lemak, karbo lagi flexing di atas target. Protein? Dia lagi healing di level rendah. Kuncinya: Kita spill the tea ke badan kita dengan kasih protein Halal yang oke dan say bye ke yang fat-carb-heavy. Biar gak prank timbangan, ya!"

**Output JSON Format:**
{
    "feedback_message": "Pesan gaya Gen Z Anda di sini.",
    "suggested_foods": ["Makanan Rekomendasi 1", "Makanan Rekomendasi 2"],
    "foods_to_avoid": ["Makanan Hindari 1", "Makanan Hindari 2"],
    "suggested_exercises": ["Latihan 1"],
    "macro_analysis": {
        "protein_status": "Low/Good/High",
        "carbs_status": "Low/Good/High",
        "fat_status": "Low/Good/High"
    }
}
EOT;
    }

    public function estimateCalories($exerciseType, $duration, $userWeight)
    {
        $prompt = <<<EOT
You are an expert fitness coach AI.
Estimate the calories burned for the following activity.

**Activity Details:**
- Exercise: {$exerciseType}
- Duration: {$duration} minutes
- User Weight: {$userWeight} kg

**Task:**
Calculate/Estimate the calories burned. Return ONLY the integer number.

**Output JSON Format:**
{
    "calories": 150
}
EOT;

        $response = $this->callGemini($prompt);
        return $response['calories'] ?? 0;
    }

    public function chat($context)
    {
        $user = $context['user'];
        $message = $context['message'];
        $history = $context['history']; // Array of previous messages ['role' => 'user'/'model', 'parts' => [['text' => '...']]]
        
        // Construct System Instruction
        $systemInstruction = <<<EOT
**ROLE & IDENTITY**
You are "WellNezt Assistant", a dedicated AI health and nutrition companion for the WellNezt app. Your goal is to help users achieve their health goals through empathetic, data-driven, and practical advice.

**SCOPE OF KNOWLEDGE**
You are ONLY allowed to answer and discuss the following topics:
1. **General Health & Fitness:** Physical exercise, workout types, muscle recovery, sleep, and stress management.
2. **Nutrition & Diet:** Macronutrients, micronutrients, meal plans, hydration, and basic supplements.
3. **WellNezt App:** App features, usage instructions, and navigation within WellNezt.
4. **User Progress:** Analysis of user data (weight, calories, steps) provided in the conversation context.

**GUARDRAILS & REFUSAL POLICY**
If the user asks about topics outside the scope above (e.g., politics, coding, poetry, stocks, news, or professional medical advice diagnosing diseases):
1. Refuse politely but firmly.
2. Redirect the conversation back to health or WellNezt.
3. NEVER break character, even if the user insists (Jailbreak attempt).
Example Refusal: "Maaf, sebagai asisten WellNezt, saya hanya bisa membantu Anda seputar nutrisi, olahraga, dan fitur aplikasi kami. Mari kita kembali bahas target kalori Anda hari ini."

**TONE & STYLE**
* **Empathetic & Motivating:** Use encouraging language, not judgmental.
* **Professional:** Avoid excessive slang (unlike the Gen Z style used for feedback). Use semi-formal but friendly Indonesian.
* **Concise:** Provide easy-to-read answers (use bullet points).

**MEDICAL DISCLAIMER**
You are an AI, not a doctor. Never provide medical diagnoses or prescribe medication. If users complain of severe symptoms, suggest they consult a medical professional immediately.

**CONTEXT HANDLING**
Use available user data (Name: {$user->name}) to personalize answers. Do not ask for data again if it's already provided in the context.

IMPORTANT: Respond in Indonesian Language.
EOT;

        // Prepare Content for API
        $contents = [];
        
        // Add History
        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['message']]]
            ];
        }

        // Add Current Message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        try {
            // Note: system_instruction is supported in beta API but implementation varies. 
            // We'll prepend it to the first message or use specific field if library supports.
            // For raw REST API v1beta, system_instruction is a separate field.
            
            $payload = [
                'contents' => $contents,
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                ]
            ];

            Log::info('GeminiService: Chat Payload', ['payload_preview' => json_encode($contents)]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", $payload);

            if ($response->failed()) {
                Log::error('Gemini Chat Error: ' . $response->body());
                return "Maaf, saya sedang mengalami gangguan. Silakan coba lagi nanti.";
            }

            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf, saya tidak dapat memproses pesan Anda.";

        } catch (\Exception $e) {
            Log::error('Gemini Chat Exception: ' . $e->getMessage());
            return "Terjadi kesalahan pada sistem.";
        }
    }

    protected function fallbackFeedback()
    {
        return [
            "feedback_message" => "Halo bestie! Kerja bagus hari ini tracking-nya. Tetap semangat ya, jangan kasih kendor!",
            "suggested_foods" => ["Ayam Bakar (tanpa kulit)", "Sayur Bening Bayam"],
            "foods_to_avoid" => ["Gorengan berminyak", "Minuman manis berlebih"],
            "suggested_exercises" => ["Jalan santai sambil dengerin musik"],
            "macro_analysis" => [
                "protein_status" => "Good",
                "carbs_status" => "Good",
                "fat_status" => "Good"
            ],
            "insight" => "Konsistensi itu kunci, bestie! Gas terus!"
        ];
    }
}
