<?php

use App\Http\Controllers\Admin\Catalog\Brand\BrandController;
use App\Http\Controllers\Admin\Catalog\CatalogProductController;
use App\Http\Controllers\Admin\Catalog\Country\CountryController;
use App\Http\Controllers\Admin\Catalog\Image\ImageController;
use App\Http\Controllers\Admin\Catalog\MarkupRule\MarkupRuleController;
use App\Http\Controllers\Admin\Catalog\Model\ImportModelController;
use App\Http\Controllers\Admin\Catalog\Model\ProductModelController;
use App\Http\Controllers\Admin\Catalog\Promotion\PromotionController;
use App\Http\Controllers\Admin\Catalog\Supplier\SupplierController;
use App\Http\Controllers\Admin\Catalog\Tire\GetTireDimensionsController;
use App\Http\Controllers\Admin\Catalog\Tire\ImportTireController;
use App\Http\Controllers\Admin\Catalog\Tire\TireProductController;
use App\Http\Controllers\Admin\Catalog\Tire\TireWarehouseStockController;
use App\Http\Controllers\Admin\Catalog\Vehicle\ImportVehicleController;
use App\Http\Controllers\Admin\Catalog\Warehouse\WarehouseController;
use App\Http\Controllers\Admin\Catalog\Wheel\GetWheelDimensionsController;
use App\Http\Controllers\Admin\Catalog\Wheel\ImportWheelController;
use App\Http\Controllers\Admin\Catalog\Wheel\WheelProductController;
use App\Http\Controllers\Admin\Catalog\Wheel\WheelWarehouseStockController;
use App\Http\Controllers\Admin\Delivery\DeliveryScheduleController;
use App\Http\Controllers\Admin\Geo\CityController;
use App\Http\Controllers\Admin\Geo\CityPriceRuleController;
use App\Http\Controllers\Admin\Geo\DeliveryPointController;
use App\Http\Controllers\Admin\Geo\ImportPointController;
use App\Http\Controllers\Admin\GetReferencesController;
use App\Http\Controllers\Admin\ImportController;

Route::get('/products', [CatalogProductController::class, 'index']);

Route::prefix('/import')->group(function () {
    Route::get('/import/{id}', [ImportController::class, 'show']);
    Route::post('/tires', [ImportTireController::class, 'store']);
    Route::post('/vehicle', [ImportVehicleController::class, 'store']);
    Route::post('/wheels', [ImportWheelController::class, 'store']);
    Route::post('/geo-points', [ImportPointController::class, 'store']);
    Route::post('/models', [ImportModelController::class, 'store']);
});

Route::apiResource('/brands', BrandController::class);

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

Route::get('/markup-rules', [MarkupRuleController::class, 'index']);
Route::post('/markup-rules', [MarkupRuleController::class, 'store']);
Route::get('/markup-rules/{id}', [MarkupRuleController::class, 'show']);
Route::put('/markup-rules/{id}', [MarkupRuleController::class, 'update']);
Route::delete('/markup-rules/{id}', [MarkupRuleController::class, 'destroy']);

Route::get('/delivery-schedules', [DeliveryScheduleController::class, 'index']);
Route::post('/delivery-schedules', [DeliveryScheduleController::class, 'store']);
Route::get('/delivery-schedules/{id}', [DeliveryScheduleController::class, 'show']);
Route::put('/delivery-schedules/{id}', [DeliveryScheduleController::class, 'update']);
Route::delete('/delivery-schedules/{id}', [DeliveryScheduleController::class, 'destroy']);

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

Route::get('/geo/cities', [CityController::class, 'index']);
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

Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::post('/warehouses', [WarehouseController::class, 'store']);
Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);
Route::put('/warehouses/{id}', [WarehouseController::class, 'update']);
Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);

Route::get('/models', [ProductModelController::class, 'index']);
Route::post('/models', [ProductModelController::class, 'store']);
Route::get('/models/{id}', [ProductModelController::class, 'show']);
Route::put('/models/{id}', [ProductModelController::class, 'update']);
Route::delete('/models/{id}', [ProductModelController::class, 'destroy']);

Route::get('/countries', [CountryController::class, 'index']);

Route::get('/references', GetReferencesController::class);
