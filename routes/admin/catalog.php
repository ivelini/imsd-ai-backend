<?php

use App\Http\Controllers\Admin\Catalog\CatalogProductController;
use App\Http\Controllers\Admin\Catalog\Image\ImageController;
use App\Http\Controllers\Admin\Catalog\Import\ImportController;
use App\Http\Controllers\Admin\Catalog\Import\ImportModelController;
use App\Http\Controllers\Admin\Catalog\Import\ImportPointController;
use App\Http\Controllers\Admin\Catalog\Import\ImportStatusController;
use App\Http\Controllers\Admin\Catalog\Import\ImportTireController;
use App\Http\Controllers\Admin\Catalog\Import\ImportVehicleController;
use App\Http\Controllers\Admin\Catalog\Import\ImportWheelController;
use App\Http\Controllers\Admin\Catalog\Model\ProductModelController;
use App\Http\Controllers\Admin\Catalog\Promotion\PromotionController;
use App\Http\Controllers\Admin\Catalog\Tire\GetTireDimensionsController;
use App\Http\Controllers\Admin\Catalog\Tire\TireProductController;
use App\Http\Controllers\Admin\Catalog\Tire\TireWarehouseStockController;
use App\Http\Controllers\Admin\Catalog\Wheel\GetWheelDimensionsController;
use App\Http\Controllers\Admin\Catalog\Wheel\WheelProductController;
use App\Http\Controllers\Admin\Catalog\Wheel\WheelWarehouseStockController;
use App\Http\Controllers\Admin\GetReferencesController;

Route::get('/products', [CatalogProductController::class, 'index']);

Route::prefix('/import')->group(function () {
    Route::get('/status', ImportStatusController::class);
    Route::get('/status/{id}', [ImportController::class, 'show']);
    Route::post('/tires', [ImportTireController::class, 'store']);
    Route::post('/vehicle', [ImportVehicleController::class, 'store']);
    Route::post('/wheels', [ImportWheelController::class, 'store']);
    Route::post('/geo-points', [ImportPointController::class, 'store']);
    Route::post('/models', [ImportModelController::class, 'store']);
});

Route::prefix('/tires')->group(function () {
    Route::get('/dimensions', GetTireDimensionsController::class);
    Route::get('', [TireProductController::class, 'index']);
    Route::post('', [TireProductController::class, 'store']);
    Route::get('/{id}', [TireProductController::class, 'show']);
    Route::put('/{id}', [TireProductController::class, 'update']);
    Route::delete('/{id}', [TireProductController::class, 'destroy']);
    Route::get('/{tire}/warehouse-stock', TireWarehouseStockController::class);
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

Route::get('/wheels/dimensions', GetWheelDimensionsController::class);
Route::get('/wheels', [WheelProductController::class, 'index']);
Route::post('/wheels', [WheelProductController::class, 'store']);
Route::get('/wheels/{id}', [WheelProductController::class, 'show']);
Route::put('/wheels/{id}', [WheelProductController::class, 'update']);
Route::delete('/wheels/{id}', [WheelProductController::class, 'destroy']);
Route::get('/wheels/{wheel}/warehouse-stock', WheelWarehouseStockController::class);

Route::get('/models', [ProductModelController::class, 'index']);
Route::post('/models', [ProductModelController::class, 'store']);
Route::get('/models/{id}', [ProductModelController::class, 'show']);
Route::put('/models/{id}', [ProductModelController::class, 'update']);
Route::delete('/models/{id}', [ProductModelController::class, 'destroy']);

Route::get('/references', GetReferencesController::class);
