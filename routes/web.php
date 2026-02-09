<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;


// Route::get('/', function () {
//     return view('welcome');
// });



use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LogoutController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

//Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');


use App\Events\MessageSent;

Route::get('/test-broadcast', function () {
    broadcast(new MessageSent('Hello World'));
    return 'Broadcasted';
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

    
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


//order routes
use App\Http\Controllers\OrderController;

Route::post('/order/{product}', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/{order}/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
Route::post('/order/{order}/confirm', [OrderController::class, 'confirm'])->name('order.confirm'); // NEW



Route::get('/', [ProductController::class, 'index'])->name('products.index');


// Listing routes (must be before {id} route to avoid conflict)
Route::middleware(['auth'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create'); // MOVED HERE
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');

    Route::get('/my-listings', [ProductController::class, 'myListings'])->name('products.myListings');
    Route::patch('/products/{id}/status', [ProductController::class, 'updateStatus'])->name('products.updateStatus');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
});

Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show'); // KEEP THIS AFTER SPECIFIC ROUTES


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
    Route::post('/cart/add/{productId}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{id}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/place-order', [CartController::class, 'placeFromCart'])->name('orders.placeFromCart');

});


// Rental routes
use App\Http\Controllers\RentalController;

Route::middleware(['auth'])->group(function () {
    // renter side
    Route::get('/rent/{product}', [RentalController::class, 'create'])->name('rental.create');
    Route::post('/rental/request/{product}', [RentalController::class, 'store'])->name('rental.store');
    Route::get('/rental/checkout/{request}', [RentalController::class, 'checkout'])->name('rental.checkout');

    // owner side (reviewing rental requests)
    Route::get('/rental/request/{request}/review', [RentalController::class, 'review'])->name('rental.review');
    Route::patch('/rental/request/{request}/approve', [RentalController::class, 'approveRequest'])->name('rental.approve');
    Route::patch('/rental/request/{request}/reject', [RentalController::class, 'reject'])->name('rental.reject');


    Route::patch('/rental/{rentedRental}/return', [RentalController::class, 'returnRental'])->name('rental.return'); // NEW

});

//my purchases route
Route::middleware('auth')->group(function () {
    Route::get('/my-purchases', [ProductController::class, 'myPurchases'])->name('products.myPurchases');
});



use App\Http\Controllers\NotificationController;
Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.markRead')->middleware('auth');




use App\Http\Controllers\SwapRequestController;

Route::middleware(['auth'])->group(function () {
    // Show request form
    Route::post('/swap/request/{product}', [SwapRequestController::class, 'showRequestForm'])
        ->name('swap.request.form');

    // Submit swap request
    Route::post('/swap/request', [SwapRequestController::class, 'store'])
        ->name('swap.request.store');

    // Show incoming swap requests (for owners)
    Route::get('/swap/requests', [SwapRequestController::class, 'incoming'])
        ->name('swap.request.incoming');
       
       
    Route::get('/swap/request/{swapRequest}', [SwapRequestController::class, 'show'])
    ->name('swap.request.show');


    // Accept swap request
    Route::post('/swap/{swapRequest}/accept', [SwapRequestController::class, 'accept'])
        ->name('swap.request.accept');

    // Reject swap request
    Route::post('/swap/{swapRequest}/reject', [SwapRequestController::class, 'reject'])
        ->name('swap.request.reject');
});


use App\Http\Controllers\AdminController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{id}', [AdminController::class, 'userShow'])->name('users.show');
    Route::put('/users/{user}', [AdminController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'userDelete'])->name('users.delete');

    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::patch('/products/{product}/flag', [AdminController::class, 'productFlag'])->name('products.flag');     // PATCH
    Route::patch('/products/{product}/unflag', [AdminController::class, 'productUnflag'])->name('products.unflag'); // optional
    Route::delete('/products/{product}', [AdminController::class, 'productDelete'])->name('products.delete');
});




require __DIR__.'/auth.php';

//Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
