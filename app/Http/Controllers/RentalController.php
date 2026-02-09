<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\RentalRequestNotification;
use App\Notifications\RentalRejectedNotification;
use App\Notifications\RentalApprovedNotification;

class RentalController extends Controller
{
    /**
     * Show rental form for a product.
     */
    public function create($productId)
    {
        $product = Product::findOrFail($productId);

        if ($product->user_id == Auth::id()) {
            return back()->with('error', 'You cannot rent your own item.');
        }

        return view('rental.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        // 1. Validate inputs
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
        ]);

        // 2. Prevent self-renting
        if ($product->user_id == Auth::id()) {
            return back()->with('error', 'You cannot rent your own item.');
        }

        // 3. Prevent duplicate pending requests
        $conflict = RentalRequest::where('product_id', $product->id)
            ->where('renter_id', Auth::id())
            ->whereIn('status', ['requested', 'approved'])
            ->exists();

        if ($conflict) {
            return back()->with('error', 'You already have a pending request for this item.');
        }

        // Prevent renting when there is only 1 unit and it's already rented out
        if ($product->quantity <= 1) {
            $hasActive = RentedRentals::where('product_id', $product->id)
                ->where('status', 'active')
                ->exists();
            if ($hasActive) {
                return back()->with('error', 'This item is currently rented out.');
            }
        }

        // Block if no stock (single-unit logic)
        if ($product->quantity < 1) {
            return back()->with('error', 'No available stock to rent.');
        }

        // 4. Create rental request
        $rentalRequest = RentalRequest::create([
            'rental_id' => optional($product->rentals()->first())->id,
            'product_id' => $product->id,
            'owner_id' => $product->user_id,
            'renter_id' => Auth::id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'duration' => $request->duration,
            'total_amount' => $request->total_amount,
            'rent_deposit' => $request->rent_deposit ?? 0,
            'status' => 'requested',
        ]);

        // 5. Notify the owner
        $owner = $product->owner ?? $product->user;
        if ($owner) {
            $owner->notify(new RentalRequestNotification($rentalRequest));
        }

        // 6. Redirect with confirmation
        return redirect()->route('products.index')->with('success', 'Rental request submitted! The owner will review your request soon.');
    }

    /**
     * Checkout screen for renter.
     */
    public function checkout($requestId)
    {
        $requestData = RentalRequest::with('product')->findOrFail($requestId);

        if ($requestData->renter_id != Auth::id()) {
            abort(403);
        }

        return view('rental.checkout', compact('requestData'));
    }

    /**
     * Review rental request for owner.
     */
    public function review($requestId)
    {
        $rental = RentalRequest::with(['product', 'renter'])
            ->findOrFail($requestId);

        // Ensure only the owner can view this request
        if ($rental->owner_id != Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Optional: mark related notification as read
        $user = Auth::user();
        $user->unreadNotifications()
            ->where('data->rental_request_id', $rental->id)
            ->update(['read_at' => now()]);

        // Return the view with a variable name matching your Blade file
        return view('rental.review', compact('rental'));
    }


    /**
     * Approve rental request → move to rented_rentals.
     */

    public function approveRequest($requestId)
    {
        $req = RentalRequest::with('product', 'rental')->findOrFail($requestId);

        // Ensure only owner can approve
        if ($req->owner_id != Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Prevent double approval
        if ($req->status !== 'requested') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($req) {
            // Get rental listing
            $rental = $req->rental;

            // If rental listing exists, use it, else create one from product
            if (!$rental) {
                $rental = Rental::create([
                    'product_id' => $req->product_id,
                    'owner_id' => $req->owner_id,
                    'rent_fare' => $req->product->rent_fare ?? 0,
                    'rent_deposit' => $req->rent_deposit ?? 0,
                    'status' => 'rented',
                ]);
            }

            // Create record in rented_rentals
            RentedRentals::create([
                'rental_id' => $rental->id,
                'product_id' => $req->product_id,
                'owner_id' => $req->owner_id,
                'renter_id' => $req->renter_id,
                'rent_fare' => $rental->rent_fare ?? 0,
                'rent_deposit' => $req->rent_deposit ?? 0,
                'rent_type' => $rental->rent_type ?? 'daily',
                'duration' => $req->duration,
                'start_date' => $req->start_date,
                'end_date' => $req->end_date,
                'total_amount' => $req->total_amount,
                'payment_status' => 'pending',
                'status' => 'active',
            ]);

            // Decrement product quantity for an approved rental (single unit)
            if ($req->product->quantity > 0) {
                $req->product->quantity -= 1;
            }
            // Update product status depending on remaining quantity
            $req->product->status = $req->product->quantity > 0 ? 'available' : 'rented';
            $req->product->save();


            $req->renter->notify(new RentalApprovedNotification($req));

            // Delete the rental request
            $req->delete();
        });

        return redirect()->route('dashboard') // ENSURE route
                     ->with('success', 'Rental approved and moved to rented items.');
    }

    /**
     * Reject a rental request.
     */
    public function reject($requestId)
    {
        $req = RentalRequest::findOrFail($requestId);

        if ($req->owner_id != Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Notify renter
        $req->renter->notify(new RentalRejectedNotification($req));

        // Delete the rental request
        $req->delete();

        return redirect()->route('dashboard') // ENSURE route
                         ->with('info', 'Rental request rejected and removed.');
    }

    public function returnRental(RentedRentals $rentedRental)
    {
        // Only owner can mark returned
        if ($rentedRental->owner_id !== Auth::id()) {
            abort(403);
        }
        if ($rentedRental->status !== 'active') {
            return back()->with('error', 'Rental already processed.');
        }

        DB::transaction(function () use ($rentedRental) {
            $rentedRental->status = 'returned';
            $rentedRental->save();

            $product = $rentedRental->product;
            if ($product) {
                $product->quantity += 1;
                // Restore availability if was rented and now has stock
                if ($product->status === 'rented' && $product->quantity > 0) {
                    $product->status = 'available';
                }
                $product->save();
            }
        });

        return back()->with('success', 'Rental marked as returned and stock updated.');
    }
}
