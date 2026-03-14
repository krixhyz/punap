<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\Swap;
use App\Models\Product;
use App\Notifications\SwapRequested;
use App\Notifications\SwapRejected;
use App\Notifications\SwapAccepted;
use App\Notifications\SwapCountered;
use App\Services\InventoryReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Middleware;

class SwapRequestController extends Controller
{
    private InventoryReservationService $inventory;

    public function __construct(InventoryReservationService $inventory)
    {
        $this->inventory = $inventory;
        $this->middleware('auth');
    }

    // ================================
    // 1️⃣ Show Swap Request Form
    // ================================
    public function showRequestForm(Product $product)
    {
        if ($product->user_id === Auth::id()) {
            return redirect()->back()->withErrors('You cannot request your own product.');
        }

        $userProducts = Auth::user()->products; // products user can offer
        return view('swaps.create', compact('product', 'userProducts'));
    }

    // ================================
    // 2️⃣ Store Swap Request
    // ================================
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'offered_product_id' => 'nullable|exists:products,id',
            'offered_amount' => 'nullable|numeric|min:0',
            'message' => 'nullable|string|max:2000',
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($product->user_id === Auth::id()) {
            return back()->withErrors('You cannot request your own product.');
        }

        $swapRequest = SwapRequest::create([
            'product_id' => $data['product_id'],
            'offered_product_id' => $data['offered_product_id'] ?? null,
            'owner_id' => $product->user_id,
            'requester_id' => Auth::id(),
            'offered_amount' => $data['offered_amount'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        // Notify product owner
        $product->user->notify(new SwapRequested($swapRequest));

        return redirect()->route('swap.request.incoming')->with('success', 'Swap request sent!');
    }



    public function show(SwapRequest $swapRequest)
{
    // Optional: authorize that the current user is owner or requester
    if (Auth::id() !== $swapRequest->owner_id && Auth::id() !== $swapRequest->requester_id) {
        abort(403);
    }

    return view('swaps.show', compact('swapRequest'));
}

    // ================================
    // 3️⃣ View Incoming Requests (Owner)
    // ================================
    public function incoming()
    {
        $requests = SwapRequest::where('owner_id', Auth::id())
            ->where('status', 'requested')
            ->with(['product', 'offeredProduct', 'requester'])
            ->latest()
            ->get();

        return view('swaps.requests', compact('requests'));
    }

    //  Accept Request
    public function accept($swapRequestId)
    {
        $swapRequest = SwapRequest::with(['requestedProduct', 'offeredProduct'])->findOrFail($swapRequestId);

        // Only owner of requested product can accept
        if ($swapRequest->product->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($swapRequest->status !== 'requested') {
            return redirect()->route('dashboard')->with('error', 'Swap already processed.');
        }

        $finalAmount = (float) ($swapRequest->offered_amount ?? 0);
        $finalNotes = $swapRequest->message;

        if ($finalAmount > 0) {
            $result = $this->reserveSwapItems($swapRequest);
            if ($result !== true) {
                return redirect()->route('dashboard')->with('error', $result);
            }

            $swapRequest->status = 'awaiting_payment';
            $swapRequest->reserved_until = now()->addMinutes(config('esewa.reservation_minutes'));
            $swapRequest->save();

            $swapRequest->requester->notify(new SwapAccepted($swapRequest));

            return redirect()->route('dashboard')->with('success', 'Swap accepted. Requester must complete payment.');
        }

        $result = $this->finalizeSwap(
            $swapRequest,
            $swapRequest->offered_product_id,
            $finalAmount,
            $finalNotes
        );

        if ($result !== true) {
            return redirect()->route('dashboard')->with('error', $result);
        }

        // Notify the requester that their swap was accepted
        $swapRequest->requester->notify(new SwapAccepted($swapRequest));

        return redirect()->route('dashboard')->with('success', 'Swap accepted successfully.');
    }

    // ================================
    // 5️⃣ Reject Request
    // ================================
    public function reject($swapRequestId)
    {
        $swapRequest = SwapRequest::findOrFail($swapRequestId);

        if ($swapRequest->owner_id !== Auth::id()) {
            abort(403);
        }

        if ($swapRequest->status !== 'requested') {
            return redirect()->route('dashboard')->with('error', 'Swap already processed.');
        }

        // Update status to rejected
        $swapRequest->status = 'rejected';
        $swapRequest->save();

        // Notify the requester that their swap was rejected
        $swapRequest->requester->notify(new SwapRejected($swapRequest));

        return redirect()->route('dashboard')->with('info', 'Swap rejected.');
    }

    public function counterOffer(Request $request, SwapRequest $swapRequest)
    {
        if ($swapRequest->owner_id !== Auth::id()) {
            abort(403);
        }

        if ($swapRequest->status !== 'requested') {
            return back()->with('error', 'Swap already processed.');
        }

        $data = $request->validate([
            'counter_amount' => 'nullable|numeric|min:0',
            'counter_message' => 'nullable|string|max:2000',
        ]);

        if (empty($data['counter_amount']) && empty($data['counter_message'])) {
            return back()->with('error', 'Add a counter amount or message to proceed.');
        }

        $swapRequest->counter_amount = $data['counter_amount'] ?? null;
        $swapRequest->counter_message = $data['counter_message'] ?? null;
        $swapRequest->countered_at = now();
        $swapRequest->status = 'countered';
        $swapRequest->save();

        $swapRequest->requester->notify(new SwapCountered($swapRequest));

        return redirect()->route('swap.request.show', $swapRequest)->with('success', 'Counter offer sent.');
    }

    public function acceptCounter(SwapRequest $swapRequest)
    {
        if ($swapRequest->requester_id !== Auth::id()) {
            abort(403);
        }

        if ($swapRequest->status !== 'countered') {
            return back()->with('error', 'No counter offer to accept.');
        }

        $finalAmount = (float) ($swapRequest->counter_amount ?? $swapRequest->offered_amount ?? 0);
        $finalNotes = $swapRequest->counter_message ?? $swapRequest->message;

        $swapRequest->offered_amount = $finalAmount;
        $swapRequest->message = $finalNotes;

        if ($finalAmount > 0) {
            $result = $this->reserveSwapItems($swapRequest);
            if ($result !== true) {
                return back()->with('error', $result);
            }

            $swapRequest->status = 'awaiting_payment';
            $swapRequest->reserved_until = now()->addMinutes(config('esewa.reservation_minutes'));
            $swapRequest->save();

            return redirect()->route('swap.checkout', $swapRequest)->with('success', 'Counter accepted. Please complete payment.');
        }

        $result = $this->finalizeSwap(
            $swapRequest,
            $swapRequest->offered_product_id,
            $finalAmount,
            $finalNotes
        );

        if ($result !== true) {
            return back()->with('error', $result);
        }

        return redirect()->route('dashboard')->with('success', 'Counter offer accepted successfully.');
    }

    public function rejectCounter(SwapRequest $swapRequest)
    {
        if ($swapRequest->requester_id !== Auth::id()) {
            abort(403);
        }

        if ($swapRequest->status !== 'countered') {
            return back()->with('error', 'No counter offer to reject.');
        }

        $swapRequest->status = 'rejected';
        $swapRequest->save();

        $swapRequest->owner->notify(new SwapRejected($swapRequest));

        return redirect()->route('dashboard')->with('info', 'Counter offer rejected.');
    }

    public function checkout(SwapRequest $swapRequest)
    {
        if ($swapRequest->requester_id !== Auth::id()) {
            abort(403);
        }

        if ($swapRequest->status !== 'awaiting_payment') {
            return redirect()->route('dashboard')->with('error', 'Swap is not awaiting payment.');
        }

        if ($swapRequest->reserved_until && $swapRequest->reserved_until->isPast()) {
            $this->releaseSwapReservation($swapRequest);
            return redirect()->route('dashboard')->with('error', 'Swap reservation expired.');
        }

        return view('swaps.checkout', compact('swapRequest'));
    }

    private function finalizeSwap(SwapRequest $swapRequest, $offeredProductId, $offeredAmount, $notes)
    {
        if (!$offeredProductId) {
            return 'An offered product is required to complete a swap.';
        }

        try {
            DB::transaction(function () use ($swapRequest, $offeredProductId, $offeredAmount, $notes) {
                $this->inventory->reserveSwapItems($swapRequest);

                $swapRequest->status = 'accepted';
                $swapRequest->offered_amount = $offeredAmount;
                $swapRequest->message = $notes;
                $swapRequest->reserved_until = null;
                $swapRequest->save();

                Swap::create([
                    'swap_request_id' => $swapRequest->id,
                    'product_a_id' => $swapRequest->product_id,
                    'product_b_id' => $offeredProductId,
                    'owner_a_id' => $swapRequest->owner_id,
                    'owner_b_id' => $swapRequest->requester_id,
                    'offered_amount' => $offeredAmount,
                    'notes' => $notes,
                    'status' => 'completed',
                ]);
            });
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        return true;
    }

    private function reserveSwapItems(SwapRequest $swapRequest)
    {
        try {
            $this->inventory->reserveSwapItems($swapRequest);
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        return true;
    }

    private function releaseSwapReservation(SwapRequest $swapRequest)
    {
        $this->inventory->releaseSwapReservation($swapRequest);
    }
    /**
     * Requester cancels their own swap request (only while requested or countered).
     */
    public function cancel(SwapRequest $swapRequest)
    {
        if ($swapRequest->requester_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($swapRequest->status, ['requested', 'countered'])) {
            return back()->with('error', 'This swap request cannot be cancelled at this stage.');
        }

        $swapRequest->status = 'cancelled';
        $swapRequest->save();

        return redirect()->route('products.myPurchases')->with('success', 'Swap request cancelled.');
    }

    public function myHistory()
{
    $user = Auth::id();

    $swaps = \App\Models\Swap::with([
        'requestedProduct',
        'offeredProduct',
        'ownerA',
        'ownerB'
    ])
    ->where(function ($q) use ($user) {
        $q->where('owner_a_id', $user)
          ->orWhere('owner_b_id', $user);
    })
    ->latest()
    ->get();

    $orders = \App\Models\Order::with('product')
        ->where('user_id', $user)
        ->latest()
        ->get();

    $rentedRentals = \App\Models\Rental::with(['product', 'owner'])
        ->where('renter_id', $user)
        ->latest()
        ->get();

    return view('profile.my-history', compact('orders', 'rentedRentals', 'swaps'));
}

}
