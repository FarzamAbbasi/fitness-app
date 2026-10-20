<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PodCheckinController;
use App\Http\Controllers\Api\PodController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WorkoutLogController;
use App\Http\Controllers\Api\WorkoutPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['status' => 'ok']));

// Public auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes (Bearer token via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Onboarding / profile
    Route::post('/profile', [ProfileController::class, 'store']);
    Route::get('/profile', [ProfileController::class, 'show']);

    // AI-generated workout plans
    Route::get('/plan', [WorkoutPlanController::class, 'current']);
    Route::post('/plan/regenerate', [WorkoutPlanController::class, 'regenerate']);
    Route::get('/plan/history', [WorkoutPlanController::class, 'history']);

    // Workout logging
    Route::post('/workout-logs', [WorkoutLogController::class, 'store']);
    Route::get('/workout-logs', [WorkoutLogController::class, 'index']);

    // Pods (community/accountability groups)
    Route::get('/pods', [PodController::class, 'index']);
    Route::get('/pods/mine', [PodController::class, 'mine']);
    Route::post('/pods/auto-join', [PodController::class, 'autoJoin']);
    Route::post('/pods/{pod}/join', [PodController::class, 'join']);
    Route::post('/pods/{pod}/leave', [PodController::class, 'leave']);
    Route::get('/pods/{pod}/checkins', [PodCheckinController::class, 'index']);
    Route::post('/pods/{pod}/checkins', [PodCheckinController::class, 'store']);
});
