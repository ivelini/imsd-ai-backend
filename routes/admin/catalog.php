<?php

use App\Http\Controllers\Admin\Catalog\Image\ImageController;
use App\Http\Controllers\Admin\Catalog\Import\ImportController;
use App\Http\Controllers\Admin\Catalog\Import\ImportModelController;
use App\Http\Controllers\Admin\Catalog\Import\ImportPointController;
use App\Http\Controllers\Admin\Catalog\Import\ImportStatusController;
use App\Http\Controllers\Admin\Catalog\Import\ImportTireController;
use App\Http\Controllers\Admin\Catalog\Import\ImportVehicleController;
use App\Http\Controllers\Admin\Catalog\Import\ImportWheelController;
use App\Http\Controllers\Admin\Catalog\Promotion\PromotionController;
use App\Http\Controllers\Admin\GetReferencesController;

Route::prefix('/import')->group(function () {
    Route::get('/status', ImportStatusController::class);
    Route::get('/status/{id}', [ImportController::class, 'show']);
    Route::post('/tires', [ImportTireController::class, 'store']);
    Route::post('/vehicle', [ImportVehicleController::class, 'store']);
    Route::post('/wheels', [ImportWheelController::class, 'store']);
    Route::post('/geo-points', [ImportPointController::class, 'store']);
    Route::post('/models', [ImportModelController::class, 'store']);
});

Route::get('/images', [ImageController::class, 'index']);
Route::post('/images', [ImageController::class, 'store']);
Route::delete('/images/{id}', [ImageController::class, 'destroy']);
Route::put('/images/{id}/main', [ImageController::class, 'setMain']);
Route::put('/images/reorder', [ImageController::class, 'reorder']);

Route::get('/promotions', [PromotionController::class, 'index']);
Route::post('/promotions', [PromotionController::class, 'store']);
Route::get('/promotions/{id}', [PromotionController::class, 'show']);
Route::put('/promotions/{id}', [PromotionController::class, 'update']);
Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);

Route::get('/references', GetReferencesController::class);
