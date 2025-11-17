<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodLog;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodLogController extends Controller
{
    /**
     * Store a new food log (IMPLEMENTASI LENGKAP)
     */
    public function store(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:foods,id',
            'quantity_grams' => 'required|numeric|min:0.01',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
            'log_date' => 'required|date',
        ]);

        // Check if food exists
        $food = Food::findOrFail($request->food_id);

        // Create food log
        $foodLog = FoodLog::create([
            'user_id' => $request->user()->id,
            'food_id' => $request->food_id,
            'quantity_grams' => $request->quantity_grams,
            'meal_type' => $request->meal_type,
            'log_date' => $request->log_date,
        ]);

        // Load food relationship for response
        $foodLog->load('food');

        // Calculate nutritional values based on quantity
        $ratio = $request->quantity_grams / $food->serving_size_grams;
        $calculatedNutrition = [
            'calories' => round($food->calories * $ratio, 2),
            'protein' => round($food->protein * $ratio, 2),
            'carbs' => round($food->carbs * $ratio, 2),
            'fats' => round($food->fats * $ratio, 2),
            'fiber' => round($food->fiber * $ratio, 2),
        ];

        return response()->json([
            'message' => 'Food log created successfully',
            'food_log' => $foodLog,
            'nutrition' => $calculatedNutrition,
        ], 201);
    }

    /**
     * Get food logs by date
     */
    public function getByDate(Request $request, $date)
    {
        $request->validate([
            'date' => 'date',
        ], [
            'date' => 'The date must be a valid date format (Y-m-d)',
        ]);

        $foodLogs = FoodLog::where('user_id', $request->user()->id)
            ->whereDate('log_date', $date)
            ->with('food')
            ->orderBy('meal_type')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'date' => $date,
            'food_logs' => $foodLogs,
        ]);
    }

    /**
     * Get all food logs (paginated)
     */
    public function index(Request $request)
    {
        $foodLogs = FoodLog::where('user_id', $request->user()->id)
            ->with('food')
            ->orderBy('log_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($foodLogs);
    }

    /**
     * Delete food log
     */
    public function destroy(Request $request, $id)
    {
        $foodLog = FoodLog::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $foodLog->delete();

        return response()->json([
            'message' => 'Food log deleted successfully',
        ]);
    }
}
