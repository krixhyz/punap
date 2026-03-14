<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use App\Services\InventoryReservationService;
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
    public function checkout(RentalRequest $rentalRequest, InventoryReservationService $inventory)
    {
        if ($rentalRequest->renter_id != Auth::id()) {
            abort(403);
        }

        if ($rentalRequest->status !== 'approved') {
            return redirect()->route('products.index')->with('error', 'Rental request is not approved yet.');
        }

        if ($rentalRequest->reserved_until && $rentalRequest->reserved_until->isPast()) {
            $inventory->releaseRentalReservation($rentalRequest);
            return redirect()->route('products.index')->with('error', 'Rental reservation expired. Please request again.');
        }

        return view('rental.checkout', compact('rentalRequest'));
    }

    /**
     * Payment page for approved rental.
     */
    public function payment(RentalRequest $rentalRequest, InventoryReservationService $inventory)
    {
        if ($rentalRequest->renter_id != Auth::id()) {
            abort(403);
        }

        if ($rentalRequest->status !== 'approved') {
            return redirect()->route('products.index')->with('error', 'Rental request is not approved yet.');
        }

        if ($rentalRequest->reserved_until && $rentalRequest->reserved_until->isPast()) {
            $inventory->releaseRentalReservation($rentalRequest);
            return redirect()->route('products.index')->with('error', 'Rental reservation expired. Please request again.');
        }

        return view('rental.payment', compact('rentalRequest'));
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
        $rentalRequest = $rental;
        return view('rental.review', compact('rentalRequest'));
    }


    /**
     * Approve rental request → move to rented_rentals.
     */

    public function approveRequest(RentalRequest $rentalRequest, InventoryReservationService $inventory)
    {
        // Ensure only owner can approve
        if ($rentalRequest->owner_id != Auth::id()) {
            abort(403, 'Unauthorized');
        }

        try {
            $inventory->reserveRentalRequest($rentalRequest, (int) config('esewa.reservation_minutes'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $rentalRequest->renter->notify(new RentalApprovedNotification($rentalRequest));

        return redirect()->route('dashboard')
            ->with('success', 'Rental approved. The renter can now proceed to payment.');
    }

    /**
     * Reject a rental request.
     */
    public function reject(RentalRequest $rentalRequest)
    {
        if ($rentalRequest->owner_id != Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($rentalRequest->status !== 'requested') {
            return back()->with('error', 'This request has already been processed.');
        }

        // Notify renter
        $rentalRequest->renter->notify(new RentalRejectedNotification($rentalRequest));

        // Keep record for history / notification resolution
        $rentalRequest->status = 'rejected';
        $rentalRequest->save();

        return redirect()->route('dashboard') // ENSURE route
                         ->with('info', 'Rental request rejected.');
    }

    /**
     * Renter cancels their own pending rental request.
     */
    public function cancelRequest(RentalRequest $rentalRequest, InventoryReservationService $inventory)
    {
        if ($rentalRequest->renter_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($rentalRequest->status, ['requested', 'approved'], true)) {
            return back()->with('error', 'Only pending or approved rental requests can be cancelled.');
        }

        if ($rentalRequest->status === 'approved') {
            $inventory->releaseRentalReservation($rentalRequest);
        } else {
            $rentalRequest->status = 'cancelled';
            $rentalRequest->save();
        }

        return redirect()->route('products.myPurchases')->with('success', 'Rental request cancelled.');
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
