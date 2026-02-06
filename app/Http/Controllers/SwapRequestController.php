<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\Swap;
use App\Models\Product;
use App\Notifications\SwapRequested;
use App\Notifications\SwapRejected;
use App\Notifications\SwapAccepted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Middleware;

class SwapRequestController extends Controller
{
    public function __construct()
    {
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

        DB::transaction(function () use ($swapRequest) {
            // Decrement quantities (swap is 1 unit each)
            $reqProduct = Product::lockForUpdate()->find($swapRequest->product_id);
            $offProduct = Product::lockForUpdate()->find($swapRequest->offered_product_id);

            if ($reqProduct && $reqProduct->quantity > 0) {
                $reqProduct->quantity -= 1;
                if ($reqProduct->quantity <= 0) {
                    $reqProduct->quantity = 0;
                    $reqProduct->status = 'swapped';
                }
                $reqProduct->save();
            }

            if ($offProduct && $offProduct->quantity > 0) {
                $offProduct->quantity -= 1;
                if ($offProduct->quantity <= 0) {
                    $offProduct->quantity = 0;
                    $offProduct->status = 'swapped';
                }
                $offProduct->save();
            }

            // Update swap request status
            $swapRequest->status = 'accepted';
            $swapRequest->save();

            // Create swap record
            \App\Models\Swap::create([
                'swap_request_id' => $swapRequest->id,
                'product_a_id' => $swapRequest->product_id,
                'product_b_id' => $swapRequest->offered_product_id,
                'owner_a_id' => $swapRequest->owner_id,
                'owner_b_id' => $swapRequest->requester_id,
                'offered_amount' => $swapRequest->offered_amount,
                'notes' => $swapRequest->message,
                'status' => 'completed',
            ]);
        });

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
