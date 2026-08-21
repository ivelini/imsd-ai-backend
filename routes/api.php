<?php

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
