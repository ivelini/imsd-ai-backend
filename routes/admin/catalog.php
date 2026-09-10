<?php

use App\Http\Controllers\Admin\Catalog\Promotion\PromotionController;
use App\Http\Controllers\Admin\GetReferencesController;

Route::get('/promotions', [PromotionController::class, 'index']);
Route::post('/promotions', [PromotionController::class, 'store']);
Route::get('/promotions/{id}', [PromotionController::class, 'show']);
Route::put('/promotions/{id}', [PromotionController::class, 'update']);
Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);

Route::get('/references', GetReferencesController::class);
