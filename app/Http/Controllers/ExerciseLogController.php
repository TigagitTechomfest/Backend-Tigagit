<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExerciseLog;
use Illuminate\Support\Facades\Auth;

class ExerciseLogController extends Controller
{
    protected $geminiService;
    
    public function __construct(\App\Services\GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'exercise_date' => 'required|date',
            'exercise_type' => 'required|string',
            'duration' => 'required|integer',
            'calories_burned' => 'nullable|integer',
        ]);

        $caloriesBurned = $request->calories_burned;

        if (!$caloriesBurned) {
            $assessment = \App\Models\HealthAssessment::where('user_id', Auth::id())->first();
            $userWeight = $assessment ? $assessment->weight : 70; // Default 70kg if no assessment
            
            $caloriesBurned = $this->geminiService->estimateCalories(
                $request->exercise_type,
                $request->duration,
                $userWeight
            );
        }

        $log = ExerciseLog::create([
            'user_id' => Auth::id(),
            'exercise_date' => $request->exercise_date,
            'exercise_type' => $request->exercise_type,
            'duration' => $request->duration,
            'calories_burned' => $caloriesBurned,
        ]);

        return response()->json(['success' => true, 'message' => 'Exercise logged', 'data' => $log]);
    }
}
