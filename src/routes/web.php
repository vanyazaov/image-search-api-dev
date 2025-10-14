<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ImageAdminController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('images', ImageAdminController::class);
    
    // Subscription management
    Route::resource('subscriptions', \App\Http\Controllers\Admin\SubscriptionController::class)
         ->only(['index', 'edit', 'update', 'destroy']);
    
    Route::post('subscriptions/{user}/generate-key', 
                [\App\Http\Controllers\Admin\SubscriptionController::class, 'generateApiKey'])
         ->name('subscriptions.generate-key');
    
    Route::post('subscriptions/{user}/reset-usage', 
                [\App\Http\Controllers\Admin\SubscriptionController::class, 'resetUsage'])
         ->name('subscriptions.reset-usage');
});

require __DIR__.'/auth.php';
