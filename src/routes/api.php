<?php

use App\Http\Controllers\Api\ImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('api.key')->group(function () {        
        Route::get('/images/{image}', [ImageController::class, 'show'])->name('api.images.show');
    });
});
