<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RentedRentals;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Seller metrics
        $sellerProducts = $user->products()->with(['orders' => function($q){
            $q->where('transaction_type','buy');
        }])->get();

        $totalUnitsListed = $sellerProducts->sum('quantity');
        $activeUnits = $sellerProducts->where('status','available')->sum('quantity');

        $sellerOrders = $sellerProducts->flatMap->orders;
        $unitsSold = $sellerOrders->sum(fn($o) => $o->quantity ?? 1);
        $salesRevenue = $sellerOrders->sum(fn($o) => ($o->unit_price ?? $o->product->price ?? 0) * ($o->quantity ?? 1));

        $activeRentalsOwner = RentedRentals::where('owner_id',$user->id)->where('status','active')->count();
        $rentalRevenueOwner = RentedRentals::where('owner_id',$user->id)->where('status','active')
            ->sum('total_amount');

        $sellerMetrics = [
            'total_units_listed' => $totalUnitsListed,
            'active_units' => $activeUnits,
            'units_sold' => $unitsSold,
            'sales_revenue' => $salesRevenue,
            'active_rentals_owner' => $activeRentalsOwner,
            'rental_revenue_owner' => $rentalRevenueOwner,
        ];

        // Buyer metrics
        $buyerOrders = Order::with('product')
            ->where('buyer_id',$user->id)
            ->where('transaction_type','buy')
            ->get();

        $purchasesCount = $buyerOrders->count();
        $purchasedUnits = $buyerOrders->sum(fn($o)=> $o->quantity ?? 1);
        $totalSpent = $buyerOrders->sum(fn($o)=> ($o->unit_price ?? $o->product->price ?? 0) * ($o->quantity ?? 1));

        $activeRentalsRenter = RentedRentals::where('renter_id',$user->id)->where('status','active')->count();
        $swapCount = \App\Models\Swap::where(function($q) use ($user){
            $q->where('owner_a_id',$user->id)->orWhere('owner_b_id',$user->id);
        })->where('status','completed')->count();

        $buyerMetrics = [
            'purchases_count' => $purchasesCount,
            'purchased_units' => $purchasedUnits,
            'total_spent' => $totalSpent,
            'active_rentals_renter' => $activeRentalsRenter,
            'completed_swaps' => $swapCount,
        ];

        return view('dashboard', compact('sellerMetrics','buyerMetrics','user'));
    }
}
