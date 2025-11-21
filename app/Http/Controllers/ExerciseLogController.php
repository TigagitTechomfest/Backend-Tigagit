<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExerciseLog;
use Illuminate\Support\Facades\Auth;

class ExerciseLogController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'exercise_date' => 'required|date',
            'exercise_type' => 'required|string',
            'duration' => 'required|integer',
            'calories_burned' => 'required|integer',
        ]);

        $log = ExerciseLog::create([
            'user_id' => Auth::id(),
            'exercise_date' => $request->exercise_date,
            'exercise_type' => $request->exercise_type,
            'duration' => $request->duration,
            'calories_burned' => $request->calories_burned,
        ]);

        return response()->json(['success' => true, 'message' => 'Exercise logged', 'data' => $log]);
    }
}
