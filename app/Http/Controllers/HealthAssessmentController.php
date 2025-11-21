<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthAssessment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HealthAssessmentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age' => 'required|integer',
            'gender' => 'required|in:male,female',
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
            'activity_level' => 'required|string',
            'health_goal' => 'required|string',
            'dietary_preference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'data' => $validator->errors()], 400);
        }

        // Calculate BMI
        $heightM = $request->height / 100;
        $bmi = $request->weight / ($heightM * $heightM);

        // Calculate BMR (Harris-Benedict)
        if ($request->gender == 'male') {
            $bmr = 88.362 + (13.397 * $request->weight) + (4.799 * $request->height) - (5.677 * $request->age);
        } else {
            $bmr = 447.593 + (9.247 * $request->weight) + (3.098 * $request->height) - (4.330 * $request->age);
        }

        // Activity Multiplier
        $activityMultipliers = [
            'Sedentary' => 1.2,
            'Light' => 1.375,
            'Moderate' => 1.55,
            'Very Active' => 1.725,
        ];
        $multiplier = $activityMultipliers[$request->activity_level] ?? 1.2;
        $tdee = $bmr * $multiplier;

        // Goal Adjustment
        $goalAdjustments = [
            'Weight Loss' => -500,
            'Maintain' => 0,
            'Weight Gain' => 500,
            'Build Muscle' => 250,
        ];
        $adjustment = $goalAdjustments[$request->health_goal] ?? 0;
        $dailyCalories = $tdee + $adjustment;

        // Macros (Standard split: Protein 25%, Carbs 50%, Fat 25% - simplified)
        $protein = ($dailyCalories * 0.25) / 4;
        $carbs = ($dailyCalories * 0.50) / 4;
        $fat = ($dailyCalories * 0.25) / 9;

        $assessment = HealthAssessment::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'age' => $request->age,
                'gender' => $request->gender,
                'height' => $request->height,
                'weight' => $request->weight,
                'bmi' => round($bmi, 2),
                'activity_level' => $request->activity_level,
                'health_goal' => $request->health_goal,
                'dietary_preference' => $request->dietary_preference,
                'daily_calorie_target' => round($dailyCalories),
                'daily_protein_target' => round($protein),
                'daily_carbs_target' => round($carbs),
                'daily_fat_target' => round($fat),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Assessment saved', 'data' => $assessment]);
    }

    public function show()
    {
        $assessment = HealthAssessment::where('user_id', Auth::id())->first();
        return response()->json(['success' => true, 'message' => 'Assessment retrieved', 'data' => $assessment]);
    }

    public function updateWeight(Request $request)
    {
        $request->validate([
            'weight' => 'required|numeric',
        ]);

        $assessment = HealthAssessment::where('user_id', Auth::id())->firstOrFail();
        $assessment->weight = $request->weight;
        
        // Recalculate BMI
        $heightM = $assessment->height / 100;
        $assessment->bmi = round($request->weight / ($heightM * $heightM), 2);
        $assessment->save();

        // Log to history
        \App\Models\HealthHistory::create([
            'user_id' => Auth::id(),
            'history_date' => now()->toDateString(),
            'weight' => $assessment->weight,
            'bmi' => $assessment->bmi,
            'health_status' => 'Updated',
        ]);

        return response()->json(['success' => true, 'message' => 'Weight updated', 'data' => $assessment]);
    }
}
