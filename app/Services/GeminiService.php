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

**Output JSON Format:**
{
    "insight": "Your insight here."
}
EOT;

        return $this->callGemini($prompt)['insight'] ?? "Keep up the good work! Consistency is key.";
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
    }

    protected function fallbackFeedback()
    {
        return [
            "feedback_message" => "Great job tracking today! Keep it up.",
            "suggested_foods" => ["Healthy balanced meal"],
            "suggested_exercises" => ["Light walking"],
            "macro_analysis" => [
                "protein_status" => "Good",
                "carbs_status" => "Good",
                "fat_status" => "Good"
            ],
            "insight" => "Keep pushing towards your goals!"
        ];
    }
}
