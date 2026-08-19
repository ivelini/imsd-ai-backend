<?php

use App\Http\Controllers\Catalog\GetTireFilterValuesController;
use App\Http\Controllers\Catalog\GetTireListController;
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

// Фасетные значения фильтра каталога шин
Route::get('/reference/filter/tire', GetTireFilterValuesController::class);

// Пагинированный список шин каталога
Route::get('/catalog/tires', GetTireListController::class);
