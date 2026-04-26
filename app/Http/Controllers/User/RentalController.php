<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalDeposit;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use App\Services\InventoryReservationService;
use App\Services\WalletLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Notifications\User\RentalRequestNotification;
use App\Notifications\User\RentalRejectedNotification;
use App\Notifications\User\RentalApprovedNotification;
use App\Http\Controllers\Controller;

class RentalController extends Controller
{
    /**
     * Show rental form for a product.
     */
    public function create(Product $product)
    {
        if ($product->user_id == Auth::id()) {
            return redirect()->route('products.show', $product->id)
                ->with('error', 'You cannot rent your own item.');
        }

        return view('rental.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $rentalConfig = $product->rentals()->first();
        $redirectToForm = fn () => redirect()->route('rental.create', $product->id);

        // 1. Validate inputs
        $validated = Validator::make(
            $request->all(),
            [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'duration' => 'required|integer|min:1|max:365',
                'total_amount' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            ],
            [
                'start_date.required' => 'Please select a rental start date.',
                'start_date.after_or_equal' => 'Rental start date cannot be in the past.',
                'end_date.required' => 'Please select a rental end date.',
                'end_date.after_or_equal' => 'Rental end date must be on or after start date.',
                'duration.required' => 'Rental duration is required.',
                'duration.integer' => 'Rental duration must be a whole number.',
                'duration.min' => 'Rental duration must be at least 1 day.',
                'duration.max' => 'Rental duration cannot exceed 365 days.',
                'total_amount.required' => 'Total rental amount is required.',
                'total_amount.numeric' => 'Total amount must be a valid number.',
                'total_amount.regex' => 'Total amount must have at most 2 decimal places.',
            ]
        )->validate();

        if (!$rentalConfig || !$rentalConfig->available_from || !$rentalConfig->available_duration) {
            return $redirectToForm()->withInput()->withErrors([
                'start_date' => 'This item does not have a valid rental availability window.',
            ]);
        }

        $ownerStartDate = Carbon::parse($rentalConfig->available_from)->startOfDay();
        $ownerEndDate = $ownerStartDate->copy()->addDays(max(((int) $rentalConfig->available_duration) - 1, 0));
        $requestedStartDate = Carbon::parse($request->start_date)->startOfDay();
        $requestedEndDate = Carbon::parse($request->end_date)->startOfDay();

        if ($requestedStartDate->lt($ownerStartDate) || $requestedStartDate->gt($ownerEndDate)) {
            return $redirectToForm()->withInput()->withErrors([
                'start_date' => 'Start date must be within the owner\'s available rental range.',
            ]);
        }

        if ($requestedEndDate->lt($requestedStartDate) || $requestedEndDate->gt($ownerEndDate)) {
            return $redirectToForm()->withInput()->withErrors([
                'end_date' => 'End date must be on or after start date and within the owner\'s available rental range.',
            ]);
        }

        $calculatedDuration = $requestedStartDate->diffInDays($requestedEndDate) + 1;

        // 2. Prevent self-renting
        if ($product->user_id == Auth::id()) {
            return $redirectToForm()->with('error', 'You cannot rent your own item.');
        }

        // 3. Prevent duplicate pending requests
        $conflict = RentalRequest::where('product_id', $product->id)
            ->where('renter_id', Auth::id())
            ->whereIn('status', ['requested', 'approved'])
            ->exists();

        if ($conflict) {
            return $redirectToForm()->with('error', 'You already have a pending request for this item.');
        }

        // Prevent renting when there is only 1 unit and it's already rented out
        if ($product->quantity <= 1) {
            $hasActive = RentedRentals::where('product_id', $product->id)
                ->where('status', 'active')
                ->exists();
            if ($hasActive) {
                return $redirectToForm()->with('error', 'This item is currently rented out.');
            }
        }

        // Block if no stock (single-unit logic)
        if ($product->quantity < 1) {
            return $redirectToForm()->with('error', 'No available stock to rent.');
        }

        // 4. Create rental request (race-safe capacity check)
        try {
            $rentalRequest = DB::transaction(function () use ($product, $rentalConfig, $validated, $calculatedDuration, $request) {
                $lockedProduct = Product::lockForUpdate()->find($product->id);

                if (!$lockedProduct || $lockedProduct->quantity < 1) {
                    throw new \RuntimeException('No available stock to rent.');
                }

                $duplicate = RentalRequest::where('product_id', $lockedProduct->id)
                    ->where('renter_id', Auth::id())
                    ->whereIn('status', ['requested', 'approved'])
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new \RuntimeException('You already have a pending request for this item.');
                }

                $openRequestCount = RentalRequest::where('product_id', $lockedProduct->id)
                    ->whereIn('status', ['requested', 'approved'])
                    ->lockForUpdate()
                    ->count();

                if ($openRequestCount >= (int) $lockedProduct->quantity) {
                    throw new \RuntimeException('This item already has the maximum number of open rental requests. Please try again later.');
                }

                return RentalRequest::create([
                    'rental_id' => $rentalConfig->id,
                    'product_id' => $lockedProduct->id,
                    'owner_id' => $lockedProduct->user_id,
                    'renter_id' => Auth::id(),
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'duration' => $calculatedDuration,
                    'total_amount' => $validated['total_amount'],
                    'rent_deposit' => $request->rent_deposit ?? 0,
                    'status' => 'requested',
                ]);
            });
        } catch (\RuntimeException $e) {
            return $redirectToForm()->withInput()->with('error', $e->getMessage());
        }

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
        $this->authorize('pay', $rentalRequest);

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
        $this->authorize('pay', $rentalRequest);

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
        $this->authorize('ownerManage', $rental);

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
        $this->authorize('ownerManage', $rentalRequest);

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
        $this->authorize('ownerManage', $rentalRequest);

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
        $this->authorize('cancel', $rentalRequest);

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

    public function returnRental(RentedRentals $rentedRental, WalletLedgerService $walletLedgerService)
    {
        $this->authorize('markReturned', $rentedRental);

        if ($rentedRental->status !== 'active') {
            return back()->with('error', 'Rental already processed.');
        }

        DB::transaction(function () use ($rentedRental, $walletLedgerService) {
            $rentedRental->status = 'completed';
            $rentedRental->returned_at = now();
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

            // Re-open rental window so the same item can be rented again.
            $rentalConfig = $product?->rentals()->first();
            if ($rentalConfig) {
                $rentalConfig->status = 'available';
                $rentalConfig->available_from = now()->toDateString();
                $rentalConfig->save();
            }

            $this->autoReleaseDepositIfEligible($rentedRental, $walletLedgerService);
        });

        return back()->with('success', 'Rental marked as returned and stock updated.');
    }

    private function autoReleaseDepositIfEligible(RentedRentals $rentedRental, WalletLedgerService $walletLedgerService): void
    {
        $hasOpenDispute = Dispute::where('rented_rental_id', $rentedRental->id)
            ->whereIn('status', ['open', 'in_review'])
            ->exists();

        if ($hasOpenDispute) {
            return;
        }

        $deposit = RentalDeposit::where('rented_rental_id', $rentedRental->id)->first();

        if (!$deposit) {
            $payment = $this->resolveRentalPaymentForDeposit($rentedRental);
            $deposit = RentalDeposit::create([
                'rented_rental_id' => $rentedRental->id,
                'payment_id' => $payment?->id,
                'amount' => (float) ($rentedRental->rent_deposit ?? 0),
                'deduction_amount' => 0,
                'refund_amount' => 0,
                'status' => 'held',
                'refund_status' => 'pending',
                'gateway' => $payment?->provider,
                'gateway_reference' => $payment?->transaction_code,
            ]);
        }

        if ($deposit->refund_status === 'success' || $deposit->status !== 'held') {
            return;
        }

        $depositAmount = (float) ($deposit->amount ?? 0);
        if ($depositAmount <= 0) {
            $deposit->update([
                'status' => 'refunded',
                'refund_status' => 'success',
                'refund_amount' => 0,
                'refund_completed_at' => now(),
                'notes' => trim(($deposit->notes ? $deposit->notes . PHP_EOL : '') . 'Auto-closed: no refundable deposit amount.'),
            ]);
            return;
        }

        $walletLedgerService->creditSaleIfMissing(
            (int) $rentedRental->renter_id,
            $depositAmount,
            'rental_deposit_refund',
            'rental_deposit',
            (int) $deposit->id,
            [
                'rented_rental_id' => $rentedRental->id,
                'auto_refund_on_return' => true,
            ]
        );

        $deposit->update([
            'deduction_amount' => 0,
            'refund_amount' => $depositAmount,
            'status' => 'refunded',
            'refund_status' => 'success',
            'refund_reference' => 'wallet-ledger:' . $deposit->id,
            'refund_requested_at' => now(),
            'refund_completed_at' => now(),
            'refund_failed_at' => null,
            'failure_reason' => null,
            'notes' => trim(($deposit->notes ? $deposit->notes . PHP_EOL : '') . 'Auto-refunded to renter wallet after return confirmation.'),
        ]);
    }

    private function resolveRentalPaymentForDeposit(RentedRentals $rental): ?Payment
    {
        $reference = trim((string) ($rental->payment_reference ?? ''));
        if ($reference !== '') {
            $match = Payment::where('status', 'complete')
                ->where(function ($query) use ($reference) {
                    $query->where('transaction_code', $reference)
                        ->orWhere('payment_reference', $reference);
                })
                ->latest('id')
                ->first();

            if ($match) {
                return $match;
            }
        }

        return Payment::where('status', 'complete')
            ->where('user_id', (int) $rental->renter_id)
            ->where(function ($query) use ($rental) {
                $query->where('total_amount', (float) ($rental->total_amount ?? 0))
                    ->orWhere('request_payload->source', 'rental');
            })
            ->latest('id')
            ->first();
    }

    /**
     * Renter requests that a rental be marked as returned.
     */
    public function requestReturn(RentedRentals $rentedRental)
    {
        $this->authorize('requestReturn', $rentedRental);

        if ($rentedRental->status !== 'active') {
            return back()->with('error', 'This rental is no longer active.');
        }

        if ($rentedRental->return_requested_at) {
            return back()->with('info', 'Return has already been requested.');
        }

        $rentedRental->return_requested_at = now();
        $rentedRental->save();

        return back()->with('success', 'Return requested. The owner will confirm after inspection.');
    }

    /**
     * View user's rentals (as a renter).
     */
    public function myRentals()
    {
        $rentals = RentedRentals::with(['product', 'owner', 'deposit'])
            ->where('renter_id', Auth::id())
            ->latest()
            ->get();

        $rentedItems = RentedRentals::with(['product', 'renter', 'deposit'])
            ->where('owner_id', Auth::id())
            ->where('status', 'active')
            ->latest()
            ->get();

        $ownerCompletedItems = RentedRentals::with(['product', 'renter', 'deposit'])
            ->where('owner_id', Auth::id())
            ->whereIn('status', ['completed', 'returned'])
            ->latest()
            ->get();

        $incomingRequests = RentalRequest::where('owner_id', Auth::id())
            ->where('status', 'requested')
            ->with(['product', 'renter'])
            ->latest()
            ->get();

        return view('rental.my_rentals', compact('rentals', 'rentedItems', 'ownerCompletedItems', 'incomingRequests'));
    }

    /**
     * Show rental details (for renter to view their active/past rentals).
     */
    public function show(RentedRentals $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['product', 'renter', 'owner']);

        return view('rental.show', compact('rental'));
    }
}
