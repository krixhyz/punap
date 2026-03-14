<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SwapRequestController;
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
use App\Http\Controllers\PaymentController;

Route::post('/order/{product}', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/{order}/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
Route::post('/order/{order}/confirm', [PaymentController::class, 'createOrderPayment'])->name('order.confirm');



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
    Route::post('/cart/place-order', [PaymentController::class, 'createCartPayment'])->name('orders.placeFromCart');

});

Route::get('/payment/esewa/success', [PaymentController::class, 'esewaSuccess'])->name('payments.esewa.success');
Route::get('/payment/esewa/failure', [PaymentController::class, 'esewaFailure'])->name('payments.esewa.failure');


// Rental routes
use App\Http\Controllers\RentalController;

Route::middleware(['auth'])->group(function () {
    // renter side
    Route::get('/rent/{product}', [RentalController::class, 'create'])->name('rental.create');
    Route::post('/rental/request/{product}', [RentalController::class, 'store'])->name('rental.store');
    Route::get('/rental/checkout/{rentalRequest}', [RentalController::class, 'checkout'])->name('rental.checkout');
    Route::get('/rental/payment/{rentalRequest}', [RentalController::class, 'payment'])->name('rental.payment');
    Route::post('/rental/{rentalRequest}/pay', [PaymentController::class, 'createRentalPayment'])->name('rental.pay');

    // owner side (reviewing rental requests)
    Route::get('/rental/request/{rentalRequest}/review', [RentalController::class, 'review'])->name('rental.review');
    Route::patch('/rental/request/{rentalRequest}/approve', [RentalController::class, 'approveRequest'])->name('rental.approve');
    Route::patch('/rental/request/{rentalRequest}/reject', [RentalController::class, 'reject'])->name('rental.reject');


    Route::patch('/rental/{rentedRental}/return', [RentalController::class, 'returnRental'])->name('rental.return'); // NEW

});

//my purchases route
Route::middleware('auth')->group(function () {
    Route::get('/my-purchases', [ProductController::class, 'myPurchases'])->name('products.myPurchases');
    Route::post('/order/{order}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
});

// Cancellation routes for rental requests and swap requests
Route::middleware('auth')->group(function () {
    Route::delete('/rental/request/{rentalRequest}/cancel', [RentalController::class, 'cancelRequest'])->name('rental.cancel');
    Route::post('/swap/{swapRequest}/cancel', [SwapRequestController::class, 'cancel'])->name('swap.request.cancel');
});



use App\Http\Controllers\NotificationController;
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
});

// Wishlist routes
use App\Http\Controllers\WishlistController;
Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});




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

    Route::get('/swap/checkout/{swapRequest}', [SwapRequestController::class, 'checkout'])
        ->name('swap.checkout');
    Route::post('/swap/{swapRequest}/pay', [PaymentController::class, 'createSwapPayment'])
        ->name('swap.pay');

    // Counter offer flow
    Route::post('/swap/{swapRequest}/counter', [SwapRequestController::class, 'counterOffer'])
        ->name('swap.request.counter');
    Route::post('/swap/{swapRequest}/counter/accept', [SwapRequestController::class, 'acceptCounter'])
        ->name('swap.request.counter.accept');
    Route::post('/swap/{swapRequest}/counter/reject', [SwapRequestController::class, 'rejectCounter'])
        ->name('swap.request.counter.reject');
});


use App\Http\Controllers\AdminController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'userStore'])->middleware('super_admin')->name('users.store');
    Route::get('/users/{id}', [AdminController::class, 'userShow'])->name('users.show');
    Route::put('/users/{user}', [AdminController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'userDelete'])->name('users.delete');
    Route::patch('/users/{user}/status', [AdminController::class, 'userStatus'])->name('users.status');
    Route::post('/users/{user}/reset-password', [AdminController::class, 'userResetPassword'])->name('users.resetPassword');

    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::patch('/products/{product}/flag', [AdminController::class, 'productFlag'])->name('products.flag');
    Route::patch('/products/{product}/unflag', [AdminController::class, 'productUnflag'])->name('products.unflag');
    Route::delete('/products/{product}', [AdminController::class, 'productDelete'])->name('products.delete');

    Route::get('/content-moderation', [AdminController::class, 'contentModeration'])->name('content');
    Route::patch('/content-moderation/{product}/decision', [AdminController::class, 'contentDecision'])->name('content.decision');
    Route::post('/content-moderation/bulk-unflag', [AdminController::class, 'contentBulkUnflag'])->name('content.bulkUnflag');
    Route::post('/content-moderation/bulk-delete', [AdminController::class, 'contentBulkDelete'])->middleware('super_admin')->name('content.bulkDelete');

    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/analytics', [AdminController::class, 'analytics'])->middleware('super_admin')->name('analytics');
    Route::get('/system-config', [AdminController::class, 'systemConfig'])->middleware('super_admin')->name('system.config');
    Route::post('/system-config', [AdminController::class, 'systemConfigUpdate'])->middleware('super_admin')->name('system.config.update');

    // Disputes
    Route::get('/disputes', [AdminController::class, 'disputes'])->name('disputes');
    Route::get('/disputes/{dispute}', [AdminController::class, 'disputeShow'])->name('disputes.show');
    Route::patch('/disputes/{dispute}/escalate', [AdminController::class, 'disputeEscalate'])->name('disputes.escalate');
    Route::patch('/disputes/{dispute}/resolve', [AdminController::class, 'disputeResolve'])->name('disputes.resolve');

    // Reviews (read-only)
    Route::get('/reviews', [AdminController::class, 'reviews'])->name('reviews');
});

// Reviews
use App\Http\Controllers\ReviewController;
Route::middleware('auth')->group(function () {
    Route::get('/review/create', [ReviewController::class, 'create'])->name('review.create');
    Route::post('/review', [ReviewController::class, 'store'])->name('review.store');
});
Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
Route::get('/user/{user}/reviews', [ReviewController::class, 'userReviews'])->name('user.reviews');

// Disputes
use App\Http\Controllers\DisputeController;
Route::middleware('auth')->group(function () {
    Route::get('/dispute/create', [DisputeController::class, 'create'])->name('dispute.create');
    Route::post('/dispute', [DisputeController::class, 'store'])->name('dispute.store');
    Route::get('/my-disputes', [DisputeController::class, 'myDisputes'])->name('dispute.my');
});




require __DIR__.'/auth.php';

//Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
