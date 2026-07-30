<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\MeController;
use App\Http\Controllers\Admin\Catalog\BrandController;
use App\Http\Controllers\Admin\Catalog\CatalogProductController;
use App\Http\Controllers\Admin\Catalog\CountryController;
use App\Http\Controllers\Admin\Catalog\ImageController;
use App\Http\Controllers\Admin\Catalog\ImportModelController;
use App\Http\Controllers\Admin\Catalog\MarkupRuleController;
use App\Http\Controllers\Admin\Catalog\ProductModelController;
use App\Http\Controllers\Admin\Catalog\PromotionController;
use App\Http\Controllers\Admin\Catalog\SupplierController;
use App\Http\Controllers\Admin\Catalog\Tire\ImportTireController;
use App\Http\Controllers\Admin\Catalog\Tire\TireProductController;
use App\Http\Controllers\Admin\Catalog\WarehouseController;
use App\Http\Controllers\Admin\Catalog\WarehouseStockController;
use App\Http\Controllers\Admin\Catalog\Wheel\ImportWheelController;
use App\Http\Controllers\Admin\Catalog\Wheel\WheelProductController;
use App\Http\Controllers\Admin\Delivery\DeliveryScheduleController;
use App\Http\Controllers\Admin\Geo\CityController;
use App\Http\Controllers\Admin\Geo\CityPriceRuleController;
use App\Http\Controllers\Admin\Geo\DeliveryPointController;
use App\Http\Controllers\Admin\Geo\ImportPointController;
use App\Http\Controllers\Admin\GetReferencesController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/admin
| Middleware: auth:sanctum (кроме login)
|
*/

Route::post('/login', LoginController::class);
Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
Route::get('/me', MeController::class)->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/catalog/products', [CatalogProductController::class, 'index']);

    Route::get('/catalog/brands', [BrandController::class, 'index']);
    Route::post('/catalog/brands', [BrandController::class, 'store']);
    Route::get('/catalog/brands/{id}', [BrandController::class, 'show']);
    Route::put('/catalog/brands/{id}', [BrandController::class, 'update']);
    Route::delete('/catalog/brands/{id}', [BrandController::class, 'destroy']);

    Route::get('/catalog/tires', [TireProductController::class, 'index']);
    Route::post('/catalog/tires/import', [ImportTireController::class, 'store']);
    Route::post('/catalog/tires', [TireProductController::class, 'store']);
    Route::get('/catalog/tires/{id}', [TireProductController::class, 'show']);
    Route::put('/catalog/tires/{id}', [TireProductController::class, 'update']);
    Route::delete('/catalog/tires/{id}', [TireProductController::class, 'destroy']);
    Route::get('/catalog/tires/{tireId}/warehouse-stock', WarehouseStockController::class);

    Route::get('/catalog/images', [ImageController::class, 'index']);
    Route::post('/catalog/images', [ImageController::class, 'store']);
    Route::delete('/catalog/images/{id}', [ImageController::class, 'destroy']);
    Route::put('/catalog/images/{id}/main', [ImageController::class, 'setMain']);
    Route::put('/catalog/images/reorder', [ImageController::class, 'reorder']);

    Route::get('/catalog/markup-rules', [MarkupRuleController::class, 'index']);
    Route::post('/catalog/markup-rules', [MarkupRuleController::class, 'store']);
    Route::get('/catalog/markup-rules/{id}', [MarkupRuleController::class, 'show']);
    Route::put('/catalog/markup-rules/{id}', [MarkupRuleController::class, 'update']);
    Route::delete('/catalog/markup-rules/{id}', [MarkupRuleController::class, 'destroy']);

    Route::get('/catalog/delivery-schedules', [DeliveryScheduleController::class, 'index']);
    Route::post('/catalog/delivery-schedules', [DeliveryScheduleController::class, 'store']);
    Route::get('/catalog/delivery-schedules/{id}', [DeliveryScheduleController::class, 'show']);
    Route::put('/catalog/delivery-schedules/{id}', [DeliveryScheduleController::class, 'update']);
    Route::delete('/catalog/delivery-schedules/{id}', [DeliveryScheduleController::class, 'destroy']);

    Route::get('/catalog/promotions', [PromotionController::class, 'index']);
    Route::post('/catalog/promotions', [PromotionController::class, 'store']);
    Route::get('/catalog/promotions/{id}', [PromotionController::class, 'show']);
    Route::put('/catalog/promotions/{id}', [PromotionController::class, 'update']);
    Route::delete('/catalog/promotions/{id}', [PromotionController::class, 'destroy']);

    Route::get('/catalog/wheels', [WheelProductController::class, 'index']);
    Route::post('/catalog/wheels/import', [ImportWheelController::class, 'store']);
    Route::post('/catalog/wheels', [WheelProductController::class, 'store']);
    Route::get('/catalog/wheels/{id}', [WheelProductController::class, 'show']);
    Route::put('/catalog/wheels/{id}', [WheelProductController::class, 'update']);
    Route::delete('/catalog/wheels/{id}', [WheelProductController::class, 'destroy']);
    Route::get('/catalog/wheels/{wheelId}/warehouse-stock', WarehouseStockController::class);

    Route::get('/geo/cities', [CityController::class, 'index']);
    Route::post('/geo/points/import', [ImportPointController::class, 'store']);

    Route::get('/geo/city-price-rules', [CityPriceRuleController::class, 'index']);
    Route::post('/geo/city-price-rules', [CityPriceRuleController::class, 'store']);
    Route::get('/geo/city-price-rules/{id}', [CityPriceRuleController::class, 'show']);
    Route::put('/geo/city-price-rules/{id}', [CityPriceRuleController::class, 'update']);
    Route::delete('/geo/city-price-rules/{id}', [CityPriceRuleController::class, 'destroy']);

    Route::get('/geo/delivery-points', [DeliveryPointController::class, 'index']);
    Route::post('/geo/delivery-points', [DeliveryPointController::class, 'store']);
    Route::get('/geo/delivery-points/{id}', [DeliveryPointController::class, 'show']);
    Route::put('/geo/delivery-points/{id}', [DeliveryPointController::class, 'update']);
    Route::delete('/geo/delivery-points/{id}', [DeliveryPointController::class, 'destroy']);

    Route::get('/catalog/suppliers', [SupplierController::class, 'index']);
    Route::post('/catalog/suppliers', [SupplierController::class, 'store']);
    Route::get('/catalog/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/catalog/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/catalog/suppliers/{id}', [SupplierController::class, 'destroy']);

    Route::get('/catalog/warehouses', [WarehouseController::class, 'index']);
    Route::post('/catalog/warehouses', [WarehouseController::class, 'store']);
    Route::get('/catalog/warehouses/{id}', [WarehouseController::class, 'show']);
    Route::put('/catalog/warehouses/{id}', [WarehouseController::class, 'update']);
    Route::delete('/catalog/warehouses/{id}', [WarehouseController::class, 'destroy']);

    Route::get('/catalog/models', [ProductModelController::class, 'index']);
    Route::post('/catalog/models/import', [ImportModelController::class, 'store']);
    Route::post('/catalog/models', [ProductModelController::class, 'store']);
    Route::get('/catalog/models/{id}', [ProductModelController::class, 'show']);
    Route::put('/catalog/models/{id}', [ProductModelController::class, 'update']);
    Route::delete('/catalog/models/{id}', [ProductModelController::class, 'destroy']);

    Route::get('/catalog/countries', [CountryController::class, 'index']);

    Route::get('/imports/{id}', [ImportController::class, 'show']);

    Route::get('/references', GetReferencesController::class);

    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
});
