<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodLog;
use App\Models\HealthProfile;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary for a specific date (IMPLEMENTASI LENGKAP)
     */
    public function getSummary(Request $request, $date)
    {
        // Validate date format
        try {
            $date = Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)',
            ], 400);
        }

        $user = $request->user();

        // Get all food logs for the date
        $foodLogs = FoodLog::where('user_id', $user->id)
            ->whereDate('log_date', $date)
            ->with('food')
            ->get();

        // Calculate total nutrition consumed
        $totalCalories = 0;
        $totalProtein = 0;
        $totalCarbs = 0;
        $totalFats = 0;
        $totalFiber = 0;

        foreach ($foodLogs as $log) {
            $food = $log->food;
            $ratio = $log->quantity_grams / $food->serving_size_grams;
            
            $totalCalories += $food->calories * $ratio;
            $totalProtein += $food->protein * $ratio;
            $totalCarbs += $food->carbs * $ratio;
            $totalFats += $food->fats * $ratio;
            $totalFiber += $food->fiber * $ratio;
        }

        // Round to 2 decimal places
        $consumed = [
            'calories' => round($totalCalories, 2),
            'protein' => round($totalProtein, 2),
            'carbs' => round($totalCarbs, 2),
            'fats' => round($totalFats, 2),
            'fiber' => round($totalFiber, 2),
        ];

        // Get health profile to calculate target calories
        $healthProfile = HealthProfile::where('user_id', $user->id)->first();
        
        $targetCalories = null;
        if ($healthProfile && $healthProfile->weight_kg && $healthProfile->height_cm && $healthProfile->date_of_birth) {
            $targetCalories = $this->calculateDailyCalories($healthProfile);
        }

        // Group food logs by meal type
        $meals = [
            'breakfast' => [],
            'lunch' => [],
            'dinner' => [],
            'snack' => [],
        ];

        foreach ($foodLogs as $log) {
            $food = $log->food;
            $ratio = $log->quantity_grams / $food->serving_size_grams;
            
            $meals[$log->meal_type][] = [
                'id' => $log->id,
                'food' => [
                    'id' => $food->id,
                    'name' => $food->name,
                ],
                'quantity_grams' => $log->quantity_grams,
                'nutrition' => [
                    'calories' => round($food->calories * $ratio, 2),
                    'protein' => round($food->protein * $ratio, 2),
                    'carbs' => round($food->carbs * $ratio, 2),
                    'fats' => round($food->fats * $ratio, 2),
                    'fiber' => round($food->fiber * $ratio, 2),
                ],
            ];
        }

        return response()->json([
            'date' => $date,
            'consumed' => $consumed,
            'target_calories' => $targetCalories,
            'remaining_calories' => $targetCalories ? round($targetCalories - $consumed['calories'], 2) : null,
            'meals' => $meals,
            'food_logs_count' => $foodLogs->count(),
        ]);
    }

    /**
     * Calculate daily calorie target using Harris-Benedict equation
     */
    private function calculateDailyCalories(HealthProfile $profile)
    {
        $age = Carbon::parse($profile->date_of_birth)->age;
        $weight = $profile->weight_kg;
        $height = $profile->height_cm;

        // Calculate BMR (Basal Metabolic Rate) using Harris-Benedict equation
        if ($profile->gender === 'male') {
            $bmr = 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
        } else {
            $bmr = 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
        }

        // Apply activity level multiplier
        $activityMultipliers = [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
        ];

        $tdee = $bmr * ($activityMultipliers[$profile->activity_level] ?? 1.2);

        // Apply goal adjustment
        $goalAdjustments = [
            'lose_weight' => -500, // Deficit of 500 calories per day
            'maintain' => 0,
            'gain_weight' => 500, // Surplus of 500 calories per day
        ];

        $targetCalories = $tdee + ($goalAdjustments[$profile->goal] ?? 0);

        return round($targetCalories, 2);
    }
}
