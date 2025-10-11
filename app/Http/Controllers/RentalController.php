<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentalController extends Controller
{
   public function create($productId)
{
    $product = Product::findOrFail($productId);

    if ($product->user_id == Auth::id()) {
        return back()->with('error', 'You cannot rent your own item.');
    }

    return view('rental.create', compact('product'));
}

public function store(Request $request, $productId)
{
    $product = Product::findOrFail($productId);

    if ($product->user_id == Auth::id()) {
        return back()->with('error', 'You cannot rent your own item.');
    }

    $request->validate([
        'duration' => 'required|integer|min:1',
    ]);

    $fare = $product->rent_fare;
    $total = $fare * $request->duration;

    $rental = Rental::create([
        'product_id' => $product->id,
        'owner_id' => $product->user_id,
        'renter_id' => Auth::id(),
        'rent_fare' => $fare,
        'rent_deposit' => $product->rent_deposit,
        'duration' => $request->duration,
        'total_amount' => $total,
        'rental_status' => 'requested',
    ]);

    // Redirect to checkout summary
    return redirect()->route('rental.checkout', $rental->id)
                     ->with('success', 'Rental request created! Please review and proceed to checkout.');
}

public function checkout($rentalId)
{
    $rental = Rental::with('product')->findOrFail($rentalId);

    if ($rental->renter_id != Auth::id()) {
        abort(403);
    }

    return view('rental.checkout', compact('rental'));
}
}

