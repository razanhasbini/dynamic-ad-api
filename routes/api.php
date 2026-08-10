<?php

use App\Http\Controllers\Api\V1\AdController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/ads', [AdController::class, 'store']);
    Route::get('/v1/my-ads', [AdController::class, 'myAds']);
});

Route::get('/v1/ads/{ad}', [AdController::class, 'show']);
