<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyLog;
use App\Models\AiFeedback;
use App\Models\HealthAssessment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Services\GeminiService;
use App\Models\ExerciseLog;

class AiFeedbackController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generateDailyFeedback(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $log = DailyLog::where('user_id', Auth::id())->where('log_date', $date)->first();
        $assessment = HealthAssessment::where('user_id', Auth::id())->first();

        if (!$log || !$assessment) {
            return response()->json(['success' => false, 'message' => 'Data insufficient', 'data' => null], 400);
        }

        // Calculate Net Calories (Intake - Exercise)
        $exercises = ExerciseLog::where('user_id', Auth::id())
            ->whereDate('exercise_date', $date)
            ->get();
        $caloriesBurned = $exercises->sum('calories_burned');
        
        $intake = $log->total_daily_intake;
        $netCalories = ($intake['calories'] ?? 0) - $caloriesBurned;

        $target = [
            'calories' => $assessment->daily_calorie_target,
            'protein' => $assessment->daily_protein_target,
            'carbs' => $assessment->daily_carbs_target,
            'fat' => $assessment->daily_fat_target,
        ];

        // Prepare Context for AI
        $context = [
            'user' => Auth::user(),
            'assessment' => $assessment,
            'intake' => $intake,
            'target' => $target,
            'net_calories' => $netCalories,
        ];

        // Generate AI Feedback
        $aiResponse = $this->geminiService->generateFeedback($context);

        $feedback = AiFeedback::create([
            'user_id' => Auth::id(),
            'log_id' => $log->id,
            'feedback_type' => 'daily',
            'feedback_message' => $aiResponse['feedback_message'],
            'suggested_foods' => $aiResponse['suggested_foods'],
            'foods_to_avoid' => $aiResponse['foods_to_avoid'] ?? [],
            'suggested_exercises' => $aiResponse['suggested_exercises'],
            'macro_analysis' => $aiResponse['macro_analysis'],
        ]);

        $log->ai_feedback_generated = true;
        $log->save();

        return response()->json(['success' => true, 'message' => 'AI Feedback generated', 'data' => $feedback]);
    }
}
