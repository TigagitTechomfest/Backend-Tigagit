<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthProfileController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\FoodLogController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\DashboardController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Health Profile routes
    Route::apiResource('health-profiles', HealthProfileController::class)->only(['store', 'update', 'show']);

    // Food routes
    Route::get('/foods', [FoodController::class, 'search']);

    // Food Log routes
    Route::apiResource('logs', FoodLogController::class)->only(['store', 'index', 'destroy']);
    Route::get('/logs/{date}', [FoodLogController::class, 'getByDate']);

    // Recipe routes
    Route::apiResource('recipes', RecipeController::class);
    Route::post('/recipes/{recipe}/ingredients', [RecipeController::class, 'addIngredient']);
    Route::delete('/recipes/{recipe}/ingredients/{ingredient}', [RecipeController::class, 'removeIngredient']);

    // Dashboard routes
    Route::get('/dashboard/{date}', [DashboardController::class, 'getSummary']);
});


