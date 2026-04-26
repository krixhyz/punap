<?php

use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\User\SwapRequestController;
use App\Http\Controllers\User\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\ProductController;
use App\Http\Controllers\LocationController;


// Route::get('/', function () {
//     return view('welcome');
// });

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
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\PaymentController;

 Route::middleware(['auth', 'verified', 'user_only'])->group(function () {
     Route::post('/checkout/calculate', [PaymentController::class, 'calculateCheckout'])->middleware('throttle:checkout-payment')->name('checkout.calculate');
     Route::post('/checkout/pay', [PaymentController::class, 'checkoutPay'])->middleware('throttle:checkout-payment')->name('checkout.pay');
     Route::post('/payment/verify', [PaymentController::class, 'verifyPayment'])->middleware('throttle:checkout-payment')->name('payment.verify');
     Route::post('/orders/{order}', [PaymentController::class, 'orderDetails'])->name('orders.details.json');
     Route::get('/transactions/my-history', [PaymentController::class, 'myTransactionHistory'])->name('transactions.my-history');

     Route::post('/order/{product}', [OrderController::class, 'store'])->name('order.store');
     Route::get('/order/product/{product}/checkout', [OrderController::class, 'checkoutProduct'])->name('order.checkout.product');
     Route::post('/order/product/{product}/confirm', [PaymentController::class, 'createDirectOrderPayment'])->middleware('throttle:checkout-payment')->name('order.confirm.product');
     Route::get('/order/{order}/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
     Route::post('/order/{order}/confirm', [PaymentController::class, 'createOrderPayment'])->middleware('throttle:checkout-payment')->name('order.confirm');
     Route::post('/order/{order}/cancel-checkout', [OrderController::class, 'cancelFromCheckout'])->name('order.cancelCheckout');
 });



Route::get('/', function () {
    $featuredProducts = \App\Models\Product::query()
        ->with('category')
        ->where('status', 'available')
        ->where(function ($query) {
            if (auth()->check()) {
                $query->where('approval_status', 'APPROVED');
                return;
            }

            $query->whereIn('approval_status', ['APPROVED', 'PENDING']);
        })
        ->when(auth()->check(), fn ($query) => $query->where('user_id', '!=', auth()->id()))
        ->latest()
        ->take(6)
        ->get();

    $parentCategories = \App\Models\Category::query()
        ->whereNull('parent_id')
        ->with(['children:id,parent_id'])
        ->orderBy('name')
        ->get();

    $allCategoryIds = $parentCategories
        ->flatMap(fn ($category) => collect([$category->id])->merge($category->children->pluck('id')))
        ->unique()
        ->values();

    $visibleApprovalStatuses = auth()->check() ? ['APPROVED'] : ['APPROVED', 'PENDING'];

    $productCountByCategoryId = \App\Models\Product::query()
        ->selectRaw('category_id, COUNT(*) as total')
        ->where('status', 'available')
        ->whereIn('approval_status', $visibleApprovalStatuses)
        ->whereIn('category_id', $allCategoryIds)
        ->groupBy('category_id')
        ->pluck('total', 'category_id');

    $topCategories = $parentCategories
        ->map(function ($category) use ($productCountByCategoryId) {
            $relatedIds = collect([$category->id])->merge($category->children->pluck('id'));

            $category->products_count = $relatedIds->sum(
                fn ($id) => (int) ($productCountByCategoryId[(int) $id] ?? 0)
            );

            return $category;
        })
        ->sortByDesc('products_count')
        ->take(6)
        ->values();

    return view('landing', compact('featuredProducts', 'topCategories'));
})->name('landing');

Route::view('/terms-and-conditions', 'legal.terms')->name('terms');
Route::get('/marketplace', [ProductController::class, 'index'])->name('products.index');


// Listing routes (must be before {id} route to avoid conflict)
Route::middleware(['auth'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create'); // MOVED HERE
    Route::post('/products', [ProductController::class, 'store'])->middleware('throttle:product-create')->name('products.store');

    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');

    Route::get('/my-listings', [ProductController::class, 'myListings'])->name('products.myListings');
    Route::patch('/products/{id}/status', [ProductController::class, 'updateStatus'])->name('products.updateStatus');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
});

Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show'); // KEEP THIS AFTER SPECIFIC ROUTES


// Protect buy/rent/swap routes:
Route::middleware(['auth', 'verified', 'user_only'])->group(function () {
    Route::get('/products/{id}/buy', [ProductController::class, 'buy'])->name('products.buy');
    Route::get('/products/{id}/rent', [ProductController::class, 'rent'])->name('products.rent');
    Route::get('/products/{id}/swap', [ProductController::class, 'swap'])->name('products.swap');
});

//cart routes
use App\Http\Controllers\User\CartController;

Route::middleware(['auth', 'verified', 'user_only'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/count', [CartController::class, 'getCount'])->name('cart.count');
    Route::post('/cart/add/{productId}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{id}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/place-order', [PaymentController::class, 'createCartPayment'])->middleware('throttle:checkout-payment')->name('orders.placeFromCart');

});

Route::get('/payment/esewa/success', [PaymentController::class, 'esewaSuccess'])->name('payments.esewa.success');
Route::get('/payment/esewa/failure', [PaymentController::class, 'esewaFailure'])->name('payments.esewa.failure');
Route::get('/payment/khalti/return', [PaymentController::class, 'khaltiReturn'])->name('payments.khalti.return');


// Rental routes
use App\Http\Controllers\User\RentalController;
use Illuminate\Auth\Access\AuthorizationException;

Route::middleware(['auth'])->group(function () {
    // renter side
    Route::middleware(['verified', 'user_only'])->group(function () {
        Route::get('/rent/{product}', [RentalController::class, 'create'])->name('rental.create');
        Route::post('/rental/request/{product}', [RentalController::class, 'store'])->middleware('throttle:message-forms')->name('rental.store');
        Route::get('/rental/checkout/{rentalRequest}', [RentalController::class, 'checkout'])->name('rental.checkout');
        Route::get('/rental/payment/{rentalRequest}', [RentalController::class, 'payment'])->name('rental.payment');
        Route::post('/rental/{rentalRequest}/pay', [PaymentController::class, 'createRentalPayment'])->middleware('throttle:checkout-payment')->name('rental.pay');
        Route::get('/rental/{rental}', [RentalController::class, 'show'])->name('rental.show');
        Route::patch('/rental/{rentedRental}/request-return', [RentalController::class, 'requestReturn'])->name('rental.requestReturn');
        Route::patch('/rental/{rentedRental}/return', [RentalController::class, 'returnRental'])->name('rental.return'); // NEW
    });

    // owner side (reviewing rental requests)
    Route::get('/rental/request/{rentalRequest}/review', [RentalController::class, 'review'])->name('rental.review');
    Route::patch('/rental/request/{rentalRequest}/approve', [RentalController::class, 'approveRequest'])->name('rental.approve');
    Route::patch('/rental/request/{rentalRequest}/reject', [RentalController::class, 'reject'])->name('rental.reject');

});

Route::get('/rental/request/{rentalRequest}', function (\App\Models\RentalRequest $rentalRequest) {
    if (!auth()->check()) {
        return redirect()->route('login')
            ->with('info', 'Please log in to view rental requests.');
    }

    try {
        \Illuminate\Support\Facades\Gate::authorize('ownerManage', $rentalRequest);

        return redirect()->route('rental.review', $rentalRequest->id);
    } catch (AuthorizationException $e) {
        return redirect()->route('rental.myRentals')
            ->with('info', 'Open rental requests from My Rentals > Incoming Requests.');
    }
})->name('rental.request.show');

Route::middleware(['auth', 'user_only'])->group(function () {
    Route::get('/my-purchases', [ProductController::class, 'myPurchases'])->name('products.myPurchases');
    Route::get('/my-rentals', [RentalController::class, 'myRentals'])->name('rental.myRentals');
    Route::post('/order/{order}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/payouts', [WalletController::class, 'requestPayout'])->name('wallet.payout.request');
});

// Seller orders routes - view incoming orders with buyer details
Route::middleware(['auth', 'user_only'])->group(function () {
    Route::get('/my-orders', [OrderController::class, 'sellerIncoming'])->name('orders.incoming');
    Route::get('/my-orders/{order}', [OrderController::class, 'sellerOrderDetail'])->name('orders.detail');
});

// Cancellation routes for rental requests and swap requests
Route::middleware(['auth', 'verified', 'user_only'])->group(function () {
    Route::delete('/rental/request/{rentalRequest}/cancel', [RentalController::class, 'cancelRequest'])->name('rental.cancel');
    Route::post('/swap/{swapRequest}/cancel', [SwapRequestController::class, 'cancel'])->name('swap.request.cancel');
});



use App\Http\Controllers\User\NotificationController;
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
});

// Wishlist routes
use App\Http\Controllers\User\WishlistController;
Route::middleware(['auth', 'user_only'])->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});




Route::middleware(['auth'])->group(function () {
    // Show request form
    Route::middleware(['verified', 'user_only'])->group(function () {
        Route::get('/swap/request/product/{product}', [SwapRequestController::class, 'showRequestForm'])
        ->name('swap.request.form');

        // Backward-compatibility for stale clients still submitting POST to form URL
        Route::post('/swap/request/{product}', function ($product) {
            return redirect()->route('swap.request.form', $product);
        })->name('swap.request.form.legacy');

    // Submit swap request
    Route::post('/swap/request', [SwapRequestController::class, 'store'])
        ->middleware('throttle:message-forms')
        ->name('swap.request.store');

    // Show incoming swap requests (for owners)
    Route::get('/swap/requests', [SwapRequestController::class, 'incoming'])
        ->name('swap.request.incoming');
        
    // View my completed/active swaps (for both parties)
    Route::get('/my-swaps', [SwapRequestController::class, 'mySwaps'])
        ->name('swap.mySwaps');
       
       
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
        ->middleware('throttle:checkout-payment')
        ->name('swap.pay');

    // Counter offer flow
    Route::post('/swap/{swapRequest}/counter', [SwapRequestController::class, 'counterOffer'])
        ->middleware('throttle:message-forms')
        ->name('swap.request.counter');
    });
    Route::post('/swap/{swapRequest}/counter/accept', [SwapRequestController::class, 'acceptCounter'])
        ->name('swap.request.counter.accept');
    Route::post('/swap/{swapRequest}/counter/reject', [SwapRequestController::class, 'rejectCounter'])
        ->name('swap.request.counter.reject');

    // Dual confirmation flow (after payment)
    Route::get('/swap/{swapRequest}/confirmation', [SwapRequestController::class, 'confirmation'])
        ->name('swap.confirmation');
    Route::get('/swap/{swapRequest}/confirm/received', function (\App\Models\SwapRequest $swapRequest) {
        \Illuminate\Support\Facades\Gate::authorize('view', $swapRequest);

        return redirect()->route('swap.mySwaps', [
            'tab' => 'pending',
            'swap_request_id' => $swapRequest->id,
        ])->with('info', 'Use the Confirm Received action inside My Swaps > Non-completed.');
    })->name('swap.confirm.received.get');
    Route::post('/swap/{swapRequest}/confirm/received', [SwapRequestController::class, 'confirmReceived'])
        ->name('swap.confirm.received');
});


use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\WalletPayoutController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'userStore'])->name('users.store');
    Route::get('/users/{id}', [AdminController::class, 'userShow'])->name('users.show');
    Route::put('/users/{user}', [AdminController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'userDelete'])->name('users.delete');
    Route::patch('/users/{user}/status', [AdminController::class, 'userStatus'])->name('users.status');
    Route::post('/users/{user}/reset-password', [AdminController::class, 'userResetPassword'])->name('users.resetPassword');
    Route::post('/users/{user}/verify', [AdminController::class, 'userVerify'])->name('users.verify');
    Route::post('/users/{user}/revoke-verification', [AdminController::class, 'userRevokeVerification'])->name('users.revokeVerification');
    Route::post('/locations/cache-clear', [LocationController::class, 'clearCache'])->name('locations.cache.clear');

    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::patch('/products/{product}/flag', [AdminController::class, 'productFlag'])->name('products.flag');
    Route::patch('/products/{product}/unflag', [AdminController::class, 'productUnflag'])->name('products.unflag');
    Route::post('/products/{product}/approve', [AdminController::class, 'productApprove'])->name('products.approve');
    Route::post('/products/{product}/reject', [AdminController::class, 'productReject'])->name('products.reject');
    Route::delete('/products/{product}', [AdminController::class, 'productDelete'])->name('products.delete');
    Route::delete('/products/{product}/force-delete', [AdminController::class, 'productForceDelete'])->middleware('super_admin')->name('products.forceDelete');
    Route::get('/products/{product}', [AdminController::class, 'productShow'])->name('products.show');


    Route::get('/content-moderation', [AdminController::class, 'contentModeration'])->name('content');
    Route::patch('/content-moderation/{product}/decision', [AdminController::class, 'contentDecision'])->name('content.decision');
    Route::post('/content-moderation/bulk-unflag', [AdminController::class, 'contentBulkUnflag'])->name('content.bulkUnflag');
    Route::post('/content-moderation/bulk-delete', [AdminController::class, 'contentBulkDelete'])->middleware('super_admin')->name('content.bulkDelete');

    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::get('/wallet/payouts', [WalletPayoutController::class, 'index'])->name('wallet.payouts');
    Route::patch('/wallet/payouts/{payoutRequest}/approve', [WalletPayoutController::class, 'approve'])->name('wallet.payouts.approve');
    Route::patch('/wallet/payouts/{payoutRequest}/reject', [WalletPayoutController::class, 'reject'])->name('wallet.payouts.reject');
    Route::patch('/wallet/payouts/{payoutRequest}/paid', [WalletPayoutController::class, 'markPaid'])->name('wallet.payouts.paid');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/analytics', [AdminController::class, 'analytics'])->middleware('super_admin')->name('analytics');
    Route::get('/system-config', [AdminController::class, 'systemConfig'])->middleware('super_admin')->name('system.config');
    Route::post('/system-config', [AdminController::class, 'systemConfigUpdate'])->middleware('super_admin')->name('system.config.update');
    Route::patch('/deposits/{rentalDeposit}/process', [AdminController::class, 'processDeposit'])->name('deposits.process');

    // Disputes
    Route::get('/disputes', [AdminController::class, 'disputes'])->name('disputes');
    Route::get('/disputes/{dispute}', [AdminController::class, 'disputeShow'])->name('disputes.show');
    Route::patch('/disputes/{dispute}/escalate', [AdminController::class, 'disputeEscalate'])->name('disputes.escalate');
    Route::patch('/disputes/{dispute}/resolve', [AdminController::class, 'disputeResolve'])->name('disputes.resolve');

    // Reviews (read-only)
    Route::get('/reviews', [AdminController::class, 'reviews'])->name('reviews');
});

// Reviews
use App\Http\Controllers\User\ReviewController;
Route::middleware('auth')->group(function () {
    Route::get('/review/create', [ReviewController::class, 'create'])->name('review.create');
    Route::post('/review', [ReviewController::class, 'store'])->middleware('throttle:message-forms')->name('review.store');
});
Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
Route::get('/user/{user}/reviews', [ReviewController::class, 'userReviews'])->name('user.reviews');

// Disputes
use App\Http\Controllers\User\DisputeController;
Route::middleware('auth')->group(function () {
    Route::get('/dispute/create', [DisputeController::class, 'create'])->name('dispute.create');
    Route::post('/dispute', [DisputeController::class, 'store'])->middleware('throttle:message-forms')->name('dispute.store');
    Route::get('/my-disputes', [DisputeController::class, 'myDisputes'])->name('dispute.my');
});




require __DIR__.'/auth.php';

//Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
