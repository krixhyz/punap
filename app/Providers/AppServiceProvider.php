<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use Illuminate\Support\ServiceProvider;
use App\Models\Review;
use App\Models\Dispute;
use App\Models\SwapRequest;
use App\Policies\OrderPolicy;
use App\Policies\RentalRequestPolicy;
use App\Policies\RentedRentalPolicy;
use App\Policies\SwapRequestPolicy;
use App\Observers\ReviewObserver;
use App\Observers\DisputeObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(20)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perHour(20)->by($request->ip());
        });

        RateLimiter::for('password-reset-request', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(10)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('product-create', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perHour(40)->by(($userId ? 'u:' . $userId : 'ip:' . $request->ip()));
        });

        RateLimiter::for('checkout-payment', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perMinute(60)->by(($userId ? 'u:' . $userId : 'ip:' . $request->ip()));
        });

        RateLimiter::for('message-forms', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perMinute(30)->by(($userId ? 'u:' . $userId : 'ip:' . $request->ip()));
        });

        Review::observe(ReviewObserver::class);
        Dispute::observe(DisputeObserver::class);

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(RentalRequest::class, RentalRequestPolicy::class);
        Gate::policy(RentedRentals::class, RentedRentalPolicy::class);
        Gate::policy(SwapRequest::class, SwapRequestPolicy::class);
       
        if (app()->environment('production')) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
        }
    }

 
}
