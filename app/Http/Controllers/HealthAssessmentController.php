<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthAssessment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HealthAssessmentController extends Controller
{


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

    public function getWeightHistory()
    {
        $history = \App\Models\HealthHistory::where('user_id', Auth::id())
            ->orderBy('history_date', 'desc')
            ->get();

        $assessment = HealthAssessment::where('user_id', Auth::id())->first();
        $initialWeight = $assessment ? $assessment->initial_weight : null;
        $currentWeight = $assessment ? $assessment->weight : null;

        return response()->json([
            'success' => true,
            'message' => 'Weight history retrieved',
            'data' => [
                'initial_weight' => $initialWeight,
                'current_weight' => $currentWeight,
                'history' => $history
            ]
        ]);
    }

    public function calculateGoals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age' => 'required|integer|min:15|max:65',
            'gender' => 'required|in:male,female',
            'height' => 'required|numeric|min:50|max:300', 
            'weight' => 'required|numeric|min:20|max:500',
            'activity_level' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'data' => $validator->errors()], 400);
        }

        // 1. BMI Calculation
        $heightM = $request->height / 100;
        $bmi = $request->weight / ($heightM * $heightM);
        $bmi = round($bmi, 1); // Round to 1 decimal for consistency

        // Determine BMI Label
        if ($bmi < 18.5) {
            $bmiLabel = "Underweight";
        } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
            $bmiLabel = "Ideal";
        } else {
            $bmiLabel = "Overweight"; // Covers Overweight and Obese
        }

        // 2. BMR & TDEE (Mifflin-St Jeor)
        if ($request->gender == 'male') {
            $bmr = (10 * $request->weight) + (6.25 * $request->height) - (5 * $request->age) + 5;
        } else {
            $bmr = (10 * $request->weight) + (6.25 * $request->height) - (5 * $request->age) - 161;
        }

        $activityMultipliers = [
            'Sedentary' => 1.2,
            'Light' => 1.375,
            'Moderate' => 1.55,
            'Very Active' => 1.725,
        ];
        $multiplier = $activityMultipliers[$request->activity_level] ?? 1.2;
        $tdee = round($bmr * $multiplier);

        // 3. Logic Goal
        $recommendation = [];
        $availableGoals = [];

        if ($bmi < 18.5) {
            // Case A: Underweight
            $recommendation = [
                'primaryGoal' => 'BULKING',
                'message' => 'Berat badanmu di bawah ideal. Disarankan fokus menambah massa otot/berat badan.'
            ];
            $allowed = ['BULKING', 'MAINTAIN'];
        } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
            // Case B: Ideal
            $recommendation = [
                'primaryGoal' => 'USER_CHOICE',
                'message' => 'Berat badanmu ideal. Tentukan tujuanmu selanjutnya.'
            ];
            $allowed = ['BULKING', 'CUTTING', 'MAINTAIN'];
        } else {
            // Case C: Overweight/Obese
            $recommendation = [
                'primaryGoal' => 'CUTTING',
                'message' => 'Berat badanmu di atas ideal. Disarankan defisit kalori untuk mencapai berat ideal.'
            ];
            $allowed = ['CUTTING', 'MAINTAIN'];
        }

        // Generate Available Goals Data
        foreach ($allowed as $type) {
            $targetCalories = $tdee;
            $label = '';
            $description = '';

            if ($type === 'BULKING') {
                $targetCalories = $tdee + 500;
                $label = 'Build Muscle';
                $description = 'Fokus menambah massa otot.';
            } elseif ($type === 'CUTTING') {
                $targetCalories = $tdee - 500;
                $label = 'Lose Fat';
                $description = 'Fokus mengurangi lemak/definisi otot.';
            } elseif ($type === 'MAINTAIN') {
                $targetCalories = $tdee;
                $label = 'Maintain Weight';
                $description = 'Jaga berat badan & vitalitas.';
            }

            $availableGoals[] = [
                'type' => $type,
                'label' => $label,
                'targetCalories' => $targetCalories,
                'description' => $description
            ];
        }

        return response()->json([
            'meta' => [
                'bmi' => $bmi,
                'bmiLabel' => $bmiLabel,
                'tdee' => $tdee
            ],
            'recommendation' => $recommendation,
            'availableGoals' => $availableGoals
        ]);
    }
}
