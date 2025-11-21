<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyLog;
use App\Models\HealthAssessment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Services\GeminiService;

class ProgressController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function daily(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $log = DailyLog::where('user_id', Auth::id())->where('log_date', $date)->first();
        $assessment = HealthAssessment::where('user_id', Auth::id())->first();
        
        // Fetch exercises for the day
        $exercises = \App\Models\ExerciseLog::where('user_id', Auth::id())
            ->whereDate('exercise_date', $date)
            ->get();
        
        $caloriesBurned = $exercises->sum('calories_burned');
        $intakeCalories = $log ? ($log->total_daily_intake['calories'] ?? 0) : 0;

        $data = [
            'intake' => $log ? $log->total_daily_intake : ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0],
            'burned' => $caloriesBurned,
            'net_calories' => $intakeCalories - $caloriesBurned,
            'target' => $assessment ? [
                'calories' => $assessment->daily_calorie_target,
                'protein' => $assessment->daily_protein_target,
                'carbs' => $assessment->daily_carbs_target,
                'fat' => $assessment->daily_fat_target,
            ] : null,
            'exercises' => $exercises
        ];

        return response()->json(['success' => true, 'message' => 'Daily progress', 'data' => $data]);
    }

    public function weekly(Request $request)
    {
        $endDate = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $startDate = $endDate->copy()->subDays(6);

        $logs = DailyLog::where('user_id', Auth::id())
            ->whereBetween('log_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $assessment = HealthAssessment::where('user_id', Auth::id())->first();
        $targetCalories = $assessment ? $assessment->daily_calorie_target : 2000;

        $totalCalories = 0;
        $daysTracked = $logs->count();
        $dailyData = [];

        foreach ($logs as $log) {
            $calories = $log->total_daily_intake['calories'] ?? 0;
            $totalCalories += $calories;
            $dailyData[] = [
                'date' => $log->log_date->toDateString(),
                'calories' => $calories,
                'hit_target' => abs($calories - $targetCalories) < 200 // within 200 cal buffer
            ];
        }

        $avgCalories = $daysTracked > 0 ? $totalCalories / $daysTracked : 0;
        $consistencyScore = $daysTracked . '/7';

        // AI Insights
        $aiContext = [
            'user' => Auth::user(),
            'assessment' => $assessment,
            'days_tracked' => $daysTracked,
            'average_calories' => round($avgCalories),
            'consistency_score' => $consistencyScore,
        ];
        
        $aiInsight = $this->geminiService->generateWeeklyInsights($aiContext);

        return response()->json([
            'success' => true,
            'message' => 'Weekly analytics',
            'data' => [
                'average_calories' => round($avgCalories),
                'days_tracked' => $daysTracked,
                'daily_breakdown' => $dailyData,
                'consistency_score' => $consistencyScore,
                'ai_insights' => $aiInsight
            ]
        ]);
    }
}
