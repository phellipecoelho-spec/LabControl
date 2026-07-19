<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'version' => '1.0.0',
        ]);
    });

    Route::post('/auth/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:auth');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware('throttle:auth');
    Route::post('/auth/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('auth:sanctum', 'throttle:auth');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->name('roles.permissions.sync');

    Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('logs/modules/list', [ActivityLogController::class, 'modules'])->name('logs.modules');
    Route::get('logs/{activityLog}', [ActivityLogController::class, 'show'])->name('logs.show');
});
