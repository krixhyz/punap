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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

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
        Review::observe(ReviewObserver::class);
        Dispute::observe(DisputeObserver::class);

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(RentalRequest::class, RentalRequestPolicy::class);
        Gate::policy(RentedRentals::class, RentedRentalPolicy::class);
        Gate::policy(SwapRequest::class, SwapRequestPolicy::class);
       
        if (app()->environment('production')) {
        URL::forceScheme('https');
    }
    }

 
}
