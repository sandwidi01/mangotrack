<?php

use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\ProducteurController;
use App\Http\Middleware\ForceJson;
use Illuminate\Support\Facades\Route;

Route::middleware(ForceJson::class)->group(function () {
    Route::get('producteurs/{producteur}/stock', [ProducteurController::class, 'stock']);
    Route::apiResource('producteurs', ProducteurController::class);
    Route::apiResource('livraisons', LivraisonController::class)->only(['index', 'store', 'show']);
});
