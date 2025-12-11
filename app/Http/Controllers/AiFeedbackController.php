<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyLog;
use App\Models\AiFeedback;
use App\Models\HealthAssessment;
use App\Models\ExerciseLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\GeminiService;

class AiFeedbackController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * 🧠 SMART FEEDBACK GENERATION
     *
     * Logic:
     * 1. Cek apakah sudah ada feedback untuk date ini
     * 2. Kalau belum ada → generate baru
     * 3. Kalau sudah ada → cek apakah daily_log/exercise_log ada update setelah feedback terakhir
     * 4. Kalau ada update → re-generate
     * 5. Kalau tidak ada update → return feedback lama
     */
    public function generateDailyFeedback(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $userId = Auth::id();

        // ✅ Step 1: Get Daily Log & Assessment
        $log = DailyLog::where('user_id', $userId)
            ->where('log_date', $date)
            ->first();

        $assessment = HealthAssessment::where('user_id', $userId)->first();

        if (!$log || !$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak lengkap. Pastikan sudah ada daily log dan health assessment.',
                'data' => null
            ], 400);
        }

        // ✅ Step 2: Cek apakah sudah ada feedback untuk date ini
        $existingFeedback = AiFeedback::where('user_id', $userId)
            ->where('log_id', $log->id)
            ->where('feedback_type', 'daily')
            ->first();

        // ✅ Step 3: Kalau belum ada feedback → GENERATE BARU
        if (!$existingFeedback) {
            return $this->generateNewFeedback($log, $assessment, $date);
        }

        // ✅ Step 4: Kalau sudah ada, cek apakah ada update setelah feedback terakhir di-generate
        $needsRegeneration = $this->checkIfNeedsRegeneration($log, $existingFeedback, $date);

        if ($needsRegeneration) {
            // ✅ Step 5a: Ada update → RE-GENERATE (update existing record)
            return $this->regenerateFeedback($existingFeedback, $log, $assessment, $date);
        }

        // ✅ Step 5b: Tidak ada update → RETURN FEEDBACK LAMA
        return response()->json([
            'success' => true,
            'message' => 'Feedback loaded (no changes detected)',
            'data' => $existingFeedback,
            'cached' => true, // Flag untuk FE tahu ini dari cache
        ]);
    }

    /**
     * 🔍 Check apakah perlu regenerate feedback
     *
     * Regenerate jika:
     * - Daily log updated_at > feedback created_at
     * - Ada exercise log baru/update setelah feedback generated
     */
    protected function checkIfNeedsRegeneration($log, $feedback, $date)
    {
        // Cek apakah daily_log di-update setelah feedback terakhir
        if ($log->updated_at > $feedback->created_at) {
            \Log::info("🔄 Daily log updated after feedback. Need regeneration.", [
                'log_updated' => $log->updated_at,
                'feedback_created' => $feedback->created_at,
            ]);
            return true;
        }

        // Cek apakah ada exercise log yang updated/created setelah feedback
        $exerciseUpdated = ExerciseLog::where('user_id', Auth::id())
            ->whereDate('exercise_date', $date)
            ->where(function ($query) use ($feedback) {
                $query->where('created_at', '>', $feedback->created_at)
                    ->orWhere('updated_at', '>', $feedback->created_at);
            })
            ->exists();

        if ($exerciseUpdated) {
            \Log::info("🔄 Exercise log updated after feedback. Need regeneration.");
            return true;
        }

        // Tidak ada perubahan
        return false;
    }

    /**
     * ✨ Generate feedback baru
     */
    protected function generateNewFeedback($log, $assessment, $date)
    {
        \Log::info("🤖 Generating NEW AI feedback for: {$date}");

        $context = $this->prepareContext($log, $assessment, $date);
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

        return response()->json([
            'success' => true,
            'message' => 'AI Feedback generated successfully',
            'data' => $feedback,
            'cached' => false,
        ]);
    }

    /**
     * 🔄 Regenerate feedback (update existing record)
     */
    protected function regenerateFeedback($existingFeedback, $log, $assessment, $date)
    {
        \Log::info("🔄 REGENERATING AI feedback for: {$date}");

        $context = $this->prepareContext($log, $assessment, $date);
        $aiResponse = $this->geminiService->generateFeedback($context);

        // Update existing feedback record
        $existingFeedback->update([
            'feedback_message' => $aiResponse['feedback_message'],
            'suggested_foods' => $aiResponse['suggested_foods'],
            'foods_to_avoid' => $aiResponse['foods_to_avoid'] ?? [],
            'suggested_exercises' => $aiResponse['suggested_exercises'],
            'macro_analysis' => $aiResponse['macro_analysis'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI Feedback regenerated successfully',
            'data' => $existingFeedback->fresh(), // Reload from DB
            'cached' => false,
        ]);
    }

    /**
     * 📦 Prepare context untuk Gemini
     */
    protected function prepareContext($log, $assessment, $date)
    {
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

        return [
            'user' => Auth::user(),
            'assessment' => $assessment,
            'intake' => $intake,
            'target' => $target,
            'net_calories' => $netCalories,
        ];
    }

    /**
     * 🗑️ Force regenerate feedback (untuk testing/admin)
     * Endpoint: POST /feedback/regenerate
     */
    public function forceRegenerate(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $userId = Auth::id();

        // Delete existing feedback
        AiFeedback::where('user_id', $userId)
            ->whereHas('dailyLog', function ($query) use ($date) {
                $query->where('log_date', $date);
            })
            ->delete();

        // Generate new
        return $this->generateDailyFeedback($request);
    }
}