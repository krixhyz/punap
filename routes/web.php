<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;


// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


//order routes
use App\Http\Controllers\OrderController;

Route::middleware('auth')->group(function () {
    Route::post('/order/{product}', [OrderController::class, 'store'])->name('order.store');
});


Route::get('/', [ProductController::class, 'index'])->name('index');

// Protect buy/rent/swap routes:
Route::middleware(['auth'])->group(function () {
    Route::get('/products/{id}/buy', [ProductController::class, 'buy'])->name('products.buy');
    Route::get('/products/{id}/rent', [ProductController::class, 'rent'])->name('products.rent');
    Route::get('/products/{id}/swap', [ProductController::class, 'swap'])->name('products.swap');
});

//cart routes
use App\Http\Controllers\CartController;

Route::middleware(['auth'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::delete('/cart/{id}', [CartController::class, 'destroy'])->name('cart.destroy');
});


require __DIR__.'/auth.php';
