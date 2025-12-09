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
