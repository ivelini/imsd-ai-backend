<?php

use App\Http\Controllers\Booking\ConfirmBookingController;
use App\Http\Controllers\Booking\GetBookingCatalogController;
use App\Http\Controllers\Booking\GetDaySlotsController;
use App\Http\Controllers\Booking\GetSlotDaysController;
use App\Http\Controllers\Booking\GetUnitPricesController;
use App\Http\Controllers\Booking\IssueBookingCodeController;
use App\Http\Controllers\Catalog\GetCityReferenceController;
use App\Http\Controllers\Catalog\GetTireFilterValuesController;
use App\Http\Controllers\Catalog\GetTireListController;
use App\Http\Controllers\Catalog\GetWheelFilterValuesController;
use App\Http\Controllers\Catalog\GetWheelListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api
|
*/

// Заглушка для редиректа auth:sanctum у неавторизованных
Route::get('/login', function (Request $request) {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

// Справочник городов для дропдаунов
Route::get('/reference/city', GetCityReferenceController::class);

// Фасетные значения фильтра каталога шин
Route::get('/reference/filter/tire', GetTireFilterValuesController::class);

// Пагинированный список шин каталога
Route::get('/catalog/tires', GetTireListController::class);

// Фасетные значения фильтра каталога дисков
Route::get('/reference/filter/wheel', GetWheelFilterValuesController::class);

// Пагинированный список дисков каталога
Route::get('/catalog/wheels', GetWheelListController::class);

// Запись на шиномонтаж: сетка, каталог услуг, SMS-код и подтверждение
Route::get('/booking/slots', GetSlotDaysController::class);
Route::get('/booking/slots/{date}', GetDaySlotsController::class)->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
Route::get('/booking/catalog', GetBookingCatalogController::class);
Route::get('/booking/price', GetUnitPricesController::class);
Route::post('/booking/code', IssueBookingCodeController::class);
Route::post('/booking/confirm', ConfirmBookingController::class);
