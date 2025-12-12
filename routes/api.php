<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthAssessmentController;
use App\Http\Controllers\FoodDatabaseController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\AiFeedbackController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ExerciseLogController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
    
    // Profile Update
    Route::post('profile', [AuthController::class, 'updateProfile'])->middleware('auth:api');

    // Password Reset
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Public Assessment Routes (Onboarding)
Route::post('assessment/calculate', [HealthAssessmentController::class, 'calculateGoals']);

Route::middleware(['auth:api'])->group(function () {
    // Health Assessment (Protected)
    Route::get('assessment', [HealthAssessmentController::class, 'show']);
    Route::post('assessment/weight', [HealthAssessmentController::class, 'updateWeight']);
    Route::get('assessment/weight/history', [HealthAssessmentController::class, 'getWeightHistory']);

    // Food Database
    Route::get('foods/list', [FoodDatabaseController::class, 'list']);
    Route::post('foods/batch', [FoodDatabaseController::class, 'batchDetails']);
    Route::get('foods/{id}', [FoodDatabaseController::class, 'show'])->where('id', '[0-9]+');
    Route::get('foods', [FoodDatabaseController::class, 'index']);
    Route::post('foods', [FoodDatabaseController::class, 'store']);

    // Daily Logs
    Route::get('daily-logs', [DailyLogController::class, 'getDailyLog']);
    Route::post('daily-logs/meal', [DailyLogController::class, 'addMeal']);
    Route::put('daily-logs/meal', [DailyLogController::class, 'updateMeal']);
    Route::delete('daily-logs/meal', [DailyLogController::class, 'deleteMeal']);

    // AI Feedback
    Route::post('feedback/daily', [AiFeedbackController::class, 'generateDailyFeedback']);

    // Progress
    Route::get('progress/daily', [ProgressController::class, 'daily']);
    Route::get('progress/weekly', [ProgressController::class, 'weekly']);

    // Exercise Logs
    Route::get('exercises', [ExerciseLogController::class, 'index']);
    Route::post('exercises', [ExerciseLogController::class, 'store']);
    Route::delete('exercises/{id}', [ExerciseLogController::class, 'destroy']);

    // Chatbot
    Route::post('chat/send', [\App\Http\Controllers\ChatController::class, 'sendMessage']);
    Route::get('chat/history', [\App\Http\Controllers\ChatController::class, 'getHistory']);
});
