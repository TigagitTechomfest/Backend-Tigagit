<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyLog;
use App\Models\FoodDatabase;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DailyLogController extends Controller
{
    public function getDailyLog(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $log = DailyLog::where('user_id', Auth::id())->where('log_date', $date)->first();

        if (!$log) {
            return response()->json(['success' => true, 'message' => 'No log found', 'data' => null]);
        }

        return response()->json(['success' => true, 'message' => 'Log retrieved', 'data' => $log]);
    }

    public function addMeal(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'meal_type' => 'required|string', // breakfast, lunch, dinner, snack
            'food_id' => 'required|exists:food_database,id',
            'quantity' => 'required|numeric', // in grams
        ]);

        $food = FoodDatabase::find($request->food_id);
        $ratio = $request->quantity / 100;

        $mealEntry = [
            'meal_type' => $request->meal_type,
            'food_id' => $food->id,
            'food_name' => $food->food_name,
            'quantity' => $request->quantity,
            'calories' => $food->calories_per_100g * $ratio,
            'protein' => $food->protein_per_100g * $ratio,
            'carbs' => $food->carbs_per_100g * $ratio,
            'fat' => $food->fat_per_100g * $ratio,
        ];

        $log = DailyLog::firstOrCreate(
            ['user_id' => Auth::id(), 'log_date' => $request->date],
            ['meal_entries' => [], 'total_daily_intake' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0]]
        );

        $entries = $log->meal_entries ?? [];
        $entries[] = $mealEntry;

        // Recalculate totals
        $totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
        foreach ($entries as $entry) {
            $totals['calories'] += $entry['calories'];
            $totals['protein'] += $entry['protein'];
            $totals['carbs'] += $entry['carbs'];
            $totals['fat'] += $entry['fat'];
        }

        $log->meal_entries = $entries;
        $log->total_daily_intake = $totals;
        $log->save();

        return response()->json(['success' => true, 'message' => 'Meal added', 'data' => $log]);
    }
}
