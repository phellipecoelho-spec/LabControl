<?php

use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ManufacturerController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\EquipmentPhotoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('equipments', EquipmentController::class);

    Route::prefix('equipments/{equipment}')->group(function () {
        Route::get('photos', [EquipmentPhotoController::class, 'index']);
        Route::post('photos', [EquipmentPhotoController::class, 'store']);
        Route::delete('photos/{photo}', [EquipmentPhotoController::class, 'destroy']);
        Route::post('photos/reorder', [EquipmentPhotoController::class, 'reorder']);
    });

    Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('manufacturers', ManufacturerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
});