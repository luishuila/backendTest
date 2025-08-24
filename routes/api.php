<?php


use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok'], 200));

Route::prefix('v1')->group(function () {


    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login',    [AuthController::class, 'login']);


    Route::middleware(['auth:api', 'throttle:api'])->group(function () {
        Route::get('auth/me',       [AuthController::class, 'me']);
        Route::post('auth/logout',  [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);

        Route::apiResource('tasks', TaskController::class);
    });
});

