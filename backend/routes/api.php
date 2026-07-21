<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\EquipmentPhotoController;
use App\Http\Controllers\Api\V1\ManufacturerController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\InventoryCategoryController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\InventoryMovementController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\SupplierController;
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

    // Auth routes (public + throttled)
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

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Profile
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
        Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

        // Users & Roles
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
        Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions.sync');

        // Activity Logs
        Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('logs/modules/list', [ActivityLogController::class, 'modules'])->name('logs.modules');
        Route::get('logs/{activityLog}', [ActivityLogController::class, 'show'])->name('logs.show');

        // Equipment Module
        Route::apiResource('equipments', EquipmentController::class);
        Route::prefix('equipments/{equipment}')->group(function () {
            Route::get('photos', [EquipmentPhotoController::class, 'index']);
            Route::post('photos', [EquipmentPhotoController::class, 'store']);
            Route::delete('photos/{photo}', [EquipmentPhotoController::class, 'destroy']);
            Route::post('photos/reorder', [EquipmentPhotoController::class, 'reorder']);
        });

        // Reference tables
        Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('manufacturers', ManufacturerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

        // Inventory Module
        Route::apiResource('inventory-items', InventoryItemController::class);
        Route::get('inventory-items/{item}/movements', [InventoryMovementController::class, 'byItem'])
            ->name('inventory-items.movements');

        // Inventory reference tables
        Route::apiResource('inventory-categories', InventoryCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Movements (immutable — no update/destroy)
        Route::apiResource('inventory-movements', InventoryMovementController::class)
            ->only(['index', 'store', 'show']);

        // Loans Module
        Route::apiResource('loans', LoanController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::prefix('loans/{loan}')->group(function () {
            Route::post('activate', [LoanController::class, 'activate'])->name('loans.activate');
            Route::post('return', [LoanController::class, 'returnItem'])->name('loans.return');
            Route::post('cancel', [LoanController::class, 'cancel'])->name('loans.cancel');
        });
    });
});
