<?php

namespace App\Http\Controllers\User;

use App\Models\SwapRequest;
use App\Models\SwapOrderConfirmation;
use App\Models\Swap;
use App\Models\Product;
use App\Notifications\User\SwapRequested;
use App\Notifications\User\SwapRejected;
use App\Notifications\User\SwapAccepted;
use App\Notifications\User\SwapCountered;
use App\Notifications\User\SwapConfirmedNotification;
use App\Notifications\User\SwapCompletedNotification;
use App\Services\InventoryReservationService;
use App\Services\SwapOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SwapOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Middleware;

class SwapRequestController extends Controller
{
    public function __construct(
        private InventoryReservationService $inventory,
        private SwapOrderService $swapOrderService
    ) {
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

        // Fetch user's swappable products (available & not this product)
        $userProducts = Auth::user()->products()
            ->where('status', '=', 'available')
            ->where('id', '!=', $product->id)
            ->get();

        return view('swaps.create', compact('product', 'userProducts'));
    }

    // ================================
    // 2️⃣ Store Swap Request
    // ================================
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'offered_product_id' => 'required|integer|different:product_id|exists:products,id',
            'money_direction' => 'required|in:none,owner_asks_cash,requester_offers_cash',
            'asking_amount' => ['nullable', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'offered_amount' => ['nullable', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'message' => 'nullable|string|max:2000',
        ], [
            'product_id.required' => 'Requested product is required.',
            'product_id.exists' => 'Requested product does not exist.',
            'offered_product_id.required' => 'Please select your offered product.',
            'offered_product_id.different' => 'Offered product must be different from requested product.',
            'offered_product_id.exists' => 'Selected offered product is invalid.',
            'money_direction.required' => 'Please choose a cash direction.',
            'money_direction.in' => 'Selected cash direction is invalid.',
            'asking_amount.numeric' => 'Asking amount must be a valid number.',
            'asking_amount.gt' => 'Asking amount must be greater than 0.',
            'asking_amount.regex' => 'Asking amount must have at most 2 decimal places.',
            'offered_amount.numeric' => 'Offered amount must be a valid number.',
            'offered_amount.gt' => 'Offered amount must be greater than 0.',
            'offered_amount.regex' => 'Offered amount must have at most 2 decimal places.',
            'message.max' => 'Message cannot exceed 2000 characters.',
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($product->user_id === Auth::id()) {
            return back()->withErrors('You cannot request your own product.');
        }

        $offeredProduct = Product::find($data['offered_product_id']);

        // Validate money direction logic
        if ($data['money_direction'] === 'owner_asks_cash' && empty($data['asking_amount'])) {
            return back()->withErrors('Amount required when asking for cash.');
        }
        if ($data['money_direction'] === 'requester_offers_cash' && empty($data['offered_amount'])) {
            return back()->withErrors('Amount required when offering cash.');
        }

        // Validate offered product if trading
        if ($data['money_direction'] !== 'none') {
            if (empty($data['offered_product_id'])) {
                return back()->withErrors('Product required for this swap type.');
            }

            if (!$offeredProduct || $offeredProduct->user_id !== Auth::id()) {
                return back()->withErrors('Invalid offered product.');
            }
            if ($offeredProduct->status !== 'available' || $offeredProduct->quantity <= 0) {
                return back()->withErrors('Your offered product is not available.');
            }
            if ($offeredProduct->id === $product->id) {
                return back()->withErrors('Cannot trade the same product.');
            }
        }

        if ($data['money_direction'] === 'owner_asks_cash') {
            if (!$offeredProduct) {
                return back()->withErrors('Select your product before requesting cash.');
            }
        }

        if (!$offeredProduct) {
            return back()->withErrors('Invalid offered product.');
        }

        if ($offeredProduct) {
            $offeredPrice = (float) ($offeredProduct->price ?? 0);
            $requestedPrice = (float) ($product->price ?? 0);

            if ($offeredPrice < $requestedPrice && $data['money_direction'] === 'owner_asks_cash') {
                return back()->withErrors('You cannot ask for cash when your offered product is lower-valued. You may add cash or continue without cash.');
            }

            if ($offeredPrice > $requestedPrice && $data['money_direction'] === 'requester_offers_cash') {
                return back()->withErrors('You cannot add cash when your offered product is higher-valued. You may ask cash or continue without cash.');
            }

            if (abs($offeredPrice - $requestedPrice) < 0.01 && $data['money_direction'] !== 'none') {
                return back()->withErrors('Cash direction is not allowed when both products have equal price.');
            }
        }

        if ($data['money_direction'] === 'requester_offers_cash' && !empty($data['asking_amount'])) {
            return back()->withErrors('Do not include asking amount when you are offering cash.');
        }

        if ($data['money_direction'] === 'owner_asks_cash' && !empty($data['offered_amount'])) {
            return back()->withErrors('Do not include offered amount when you are requesting cash.');
        }

        if ($data['money_direction'] === 'none' && (!empty($data['offered_amount']) || !empty($data['asking_amount']))) {
            return back()->withErrors('Remove cash amounts when no cash is involved.');
        }

        $openStatuses = ['requested', 'countered', 'awaiting_payment', 'paid', 'confirmation_pending'];

        try {
            $swapRequest = DB::transaction(function () use ($data, $openStatuses) {
                $productIds = collect([(int) $data['product_id'], (int) $data['offered_product_id']])->unique()->sort()->values()->all();
                $lockedProducts = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

                $lockedRequested = $lockedProducts->get((int) $data['product_id']);
                $lockedOffered = $lockedProducts->get((int) $data['offered_product_id']);

                if (!$lockedRequested || !$lockedOffered) {
                    throw new \RuntimeException('Swap products are no longer available.');
                }

                if ($lockedRequested->status !== 'available' || (int) $lockedRequested->quantity < 1) {
                    throw new \RuntimeException('Requested product is no longer available for swap.');
                }

                if ($lockedOffered->user_id !== Auth::id() || $lockedOffered->status !== 'available' || (int) $lockedOffered->quantity < 1) {
                    throw new \RuntimeException('Your offered product is not available.');
                }

                $requestedOpenCount = SwapRequest::where('product_id', $lockedRequested->id)
                    ->whereIn('status', $openStatuses)
                    ->lockForUpdate()
                    ->count();

                if ($requestedOpenCount >= (int) $lockedRequested->quantity) {
                    throw new \RuntimeException('This item already has the maximum number of open swap requests. Please try again later.');
                }

                $offeredOpenCount = SwapRequest::where('offered_product_id', $lockedOffered->id)
                    ->whereIn('status', $openStatuses)
                    ->lockForUpdate()
                    ->count();

                if ($offeredOpenCount >= (int) $lockedOffered->quantity) {
                    throw new \RuntimeException('Your offered item is already tied to the maximum number of open swap requests.');
                }

                return SwapRequest::create([
                    'product_id' => $lockedRequested->id,
                    'offered_product_id' => $lockedOffered->id,
                    'owner_id' => $lockedRequested->user_id,
                    'requester_id' => Auth::id(),
                    'asking_amount' => $data['asking_amount'] ?? null,
                    'offered_amount' => $data['offered_amount'] ?? null,
                    'money_direction' => $data['money_direction'],
                    'message' => $data['message'] ?? null,
                    'status' => 'requested',
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }

        // Add to negotiation timeline
        $this->swapOrderService->createNegotiationEvent(
            $swapRequest,
            Auth::id(),
            'initial_offer',
            [
                'offered_product_id' => $data['offered_product_id'] ?? null,
                'offered_amount' => $data['offered_amount'] ?? null,
                'asking_amount' => $data['asking_amount'] ?? null,
                'message' => $data['message'] ?? null,
                'metadata' => ['money_direction' => $data['money_direction']],
            ]
        );

        // Notify product owner
        $product->user->notify(new SwapRequested($swapRequest));

        return redirect()->route('dashboard')->with('success', 'Swap request sent!');
    }



    public function show(SwapRequest $swapRequest)
{
    $this->authorize('view', $swapRequest);

        $payerId = $this->resolveSwapPayerId($swapRequest);
        if ($swapRequest->status === 'awaiting_payment' && $payerId && (int) Auth::id() === (int) $payerId) {
            return redirect()->route('swap.checkout', $swapRequest);
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
        $this->authorize('ownerManage', $swapRequest);

        if ($swapRequest->status !== 'requested') {
            return redirect()->route('dashboard')->with('error', 'Swap already processed.');
        }

        $direction = $swapRequest->money_direction;
        $finalAmount = $direction === 'requester_offers_cash'
            ? (float) ($swapRequest->offered_amount ?? 0)
            : ($direction === 'owner_asks_cash' ? (float) ($swapRequest->asking_amount ?? 0) : 0.0);
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

            $payer = $this->resolveSwapPayerLabel($swapRequest);
            return redirect()->route('dashboard')->with('success', 'Swap accepted. ' . $payer . ' must complete payment.');
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
        $this->authorize('ownerManage', $swapRequest);

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
        $this->authorize('ownerManage', $swapRequest);

        if ($swapRequest->status !== 'requested') {
            return back()->with('error', 'Swap already processed.');
        }

        $data = $request->validate([
            'counter_amount' => ['nullable', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'counter_message' => 'nullable|string|max:2000',
        ], [
            'counter_amount.numeric' => 'Counter amount must be a valid number.',
            'counter_amount.gt' => 'Counter amount must be greater than 0.',
            'counter_amount.regex' => 'Counter amount must have at most 2 decimal places.',
            'counter_message.max' => 'Counter message cannot exceed 2000 characters.',
        ]);

        if (empty($data['counter_amount']) && empty($data['counter_message'])) {
            return back()->with('error', 'Add a counter amount or message to proceed.');
        }

        $ownerProductPrice = (float) ($swapRequest->product->price ?? 0);
        $requesterProductPrice = (float) ($swapRequest->offeredProduct->price ?? 0);

        $lockedDirection = 'none';
        if ($ownerProductPrice > $requesterProductPrice) {
            $lockedDirection = 'requester_offers_cash';
        } elseif ($ownerProductPrice < $requesterProductPrice) {
            $lockedDirection = 'owner_asks_cash';
        }

        if (!empty($data['counter_amount']) && $lockedDirection === 'none') {
            return back()->with('error', 'Counter cash is not allowed when both product prices are equal.');
        }

        $swapRequest->counter_amount = $data['counter_amount'] ?? null;
        $swapRequest->counter_message = $data['counter_message'] ?? null;
        if (!empty($data['counter_amount'])) {
            $swapRequest->money_direction = $lockedDirection;
        }
        $swapRequest->countered_at = now();
        $swapRequest->status = 'countered';
        $swapRequest->save();

        $swapRequest->requester->notify(new SwapCountered($swapRequest));

        return redirect()->route('swap.request.show', $swapRequest)->with('success', 'Counter offer sent.');
    }

    public function acceptCounter(SwapRequest $swapRequest)
    {
        $this->authorize('requesterManage', $swapRequest);

        if ($swapRequest->status !== 'countered') {
            return back()->with('error', 'No counter offer to accept.');
        }

        $direction = $swapRequest->money_direction;
        $baseAmount = $direction === 'owner_asks_cash'
            ? ($swapRequest->asking_amount ?? 0)
            : ($swapRequest->offered_amount ?? 0);

        $finalAmount = (float) ($swapRequest->counter_amount ?? $baseAmount ?? 0);
        $finalNotes = $swapRequest->counter_message ?? $swapRequest->message;

        if ($direction === 'owner_asks_cash') {
            $swapRequest->asking_amount = $finalAmount > 0 ? $finalAmount : null;
            $swapRequest->offered_amount = null;
        } elseif ($direction === 'requester_offers_cash') {
            $swapRequest->offered_amount = $finalAmount > 0 ? $finalAmount : null;
            $swapRequest->asking_amount = null;
        } else {
            $swapRequest->offered_amount = null;
            $swapRequest->asking_amount = null;
        }
        $swapRequest->message = $finalNotes;

        if (in_array($direction, ['requester_offers_cash', 'owner_asks_cash'], true) && $finalAmount > 0) {
            $result = $this->reserveSwapItems($swapRequest);
            if ($result !== true) {
                return back()->with('error', $result);
            }

            $swapRequest->status = 'awaiting_payment';
            $swapRequest->reserved_until = now()->addMinutes(config('esewa.reservation_minutes'));
            $swapRequest->save();

            if ($direction === 'owner_asks_cash') {
                $swapRequest->owner->notify(new SwapAccepted($swapRequest));
            }

            if ($this->resolveSwapPayerId($swapRequest) === Auth::id()) {
                return redirect()->route('swap.checkout', $swapRequest)->with('success', 'Counter accepted. Please complete payment.');
            }

            return redirect()->route('dashboard')->with('success', 'Counter accepted. Waiting for payment from ' . $this->resolveSwapPayerLabel($swapRequest) . '.');
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
        $this->authorize('requesterManage', $swapRequest);

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
        $this->authorize('pay', $swapRequest);

        $payerId = $this->resolveSwapPayerId($swapRequest);

        if ($swapRequest->status !== 'awaiting_payment') {
            return redirect()->route('dashboard')->with('error', 'Swap is not awaiting payment.');
        }

        if ($swapRequest->reserved_until && $swapRequest->reserved_until->isPast()) {
            $this->releaseSwapReservation($swapRequest);
            return redirect()->route('dashboard')->with('error', 'Swap reservation expired.');
        }

        return view('swaps.checkout', compact('swapRequest'));
    }

    private function resolveSwapPayerId(SwapRequest $swapRequest): ?int
    {
        return match ($swapRequest->money_direction) {
            'requester_offers_cash' => $swapRequest->requester_id,
            'owner_asks_cash' => $swapRequest->owner_id,
            default => null,
        };
    }

    private function resolveSwapPayerLabel(SwapRequest $swapRequest): string
    {
        return match ($swapRequest->money_direction) {
            'requester_offers_cash' => 'Requester',
            'owner_asks_cash' => 'Owner',
            default => 'Participant',
        };
    }

    private function finalizeSwap(SwapRequest $swapRequest, $offeredProductId, $offeredAmount, $notes)
    {
        if (!$offeredProductId) {
            return 'An offered product is required to complete a swap.';
        }

        try {
            DB::transaction(function () use ($swapRequest, $offeredProductId, $offeredAmount, $notes) {
                $this->inventory->reserveSwapItems($swapRequest);

                $swapRequest->status = 'completed';
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
        $this->authorize('requesterManage', $swapRequest);

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

    /**
     * View user's swaps (both pending and completed).
     */
    public function mySwaps()
    {
        // Self-heal any swap requests that have both confirmations but were left in a paid-like state.
        SwapRequest::with('orderConfirmation')
            ->where(function ($q) {
                $q->where('owner_id', Auth::id())
                    ->orWhere('requester_id', Auth::id());
            })
            ->whereIn('status', ['paid', 'confirmation_pending'])
            ->get()
            ->each(function (SwapRequest $request) {
                $confirmation = $request->orderConfirmation;
                if ($confirmation && $confirmation->owner_confirmed_at && $confirmation->requester_confirmed_at) {
                    $this->swapOrderService->completeSwapAfterConfirmation($request);
                }
            });

        $completedSwaps = Swap::with([
            'requestedProduct',
            'offeredProduct',
            'ownerA',
            'ownerB'
        ])
        ->where(function ($q) {
            $q->where('owner_a_id', Auth::id())
              ->orWhere('owner_b_id', Auth::id());
        })
        ->where('status', 'completed')
        ->latest()
        ->get();

        $pendingSwapRequests = SwapRequest::with([
            'product',
            'offeredProduct',
            'owner',
            'requester',
            'orderConfirmation',
        ])
        ->where(function ($q) {
            $q->where('owner_id', Auth::id())
              ->orWhere('requester_id', Auth::id());
        })
        ->whereNotNull('offered_product_id')
        ->whereIn('status', ['awaiting_payment', 'paid', 'confirmation_pending'])
                ->where(function ($q) {
                        $q->whereDoesntHave('orderConfirmation')
                            ->orWhereHas('orderConfirmation', function ($confirmQ) {
                                    $confirmQ->whereNull('owner_confirmed_at')
                                                     ->orWhereNull('requester_confirmed_at');
                            });
                })
        ->latest()
        ->get();

        return view('swaps.my_swaps', compact('completedSwaps', 'pendingSwapRequests'));
    }

    // ================================
    // Confirmation Flow (Phase 2.1)
    // ================================

    /**
     * Show swap order confirmation page (after payment).
     */
    public function confirmation(SwapRequest $swapRequest)
    {
        $this->authorize('view', $swapRequest);

        // Confirmation page is available during paid state and after completion for audit/history.
        if (!in_array($swapRequest->status, ['paid', 'completed'], true)) {
            return redirect()->route('swap.request.show', $swapRequest)
                ->with('error', 'Swap is not awaiting confirmation.');
        }

        $swapRequest->load(['orderConfirmation', 'product', 'offeredProduct', 'owner', 'requester']);

        return view('swaps.confirmation', compact('swapRequest'));
    }

    /**
     * Confirm receipt of items (set owner_confirmed_at or requester_confirmed_at).
     * If both confirmed, trigger fund release and completion.
     */
    public function confirmReceived(Request $request, SwapRequest $swapRequest)
    {
        $this->authorize('view', $swapRequest);

        // Must be in paid status
        if ($swapRequest->status !== 'paid') {
            return back()->with('error', 'Swap is not in confirmation phase.');
        }

        // Get confirmation record
        $confirmation = $swapRequest->orderConfirmation;
        if (!$confirmation) {
            return back()->with('error', 'Confirmation record not found.');
        }

        // Validate input
        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
        ], [
            'notes.max' => 'Confirmation notes cannot exceed 500 characters.',
        ]);

        // Determine if user is owner or requester
        $isOwner = Auth::id() === $swapRequest->owner_id;

        // Prevent double confirmation
        if ($isOwner && $confirmation->owner_confirmed_at) {
            return back()->with('info', 'You already confirmed receipt.');
        }
        if (!$isOwner && $confirmation->requester_confirmed_at) {
            return back()->with('info', 'You already confirmed receipt.');
        }

        DB::transaction(function () use ($isOwner, $confirmation, $data, $swapRequest) {
            // Set confirmation timestamp & notes
            if ($isOwner) {
                $confirmation->owner_confirmed_at = now();
                $confirmation->owner_notes = $data['notes'] ?? null;
            } else {
                $confirmation->requester_confirmed_at = now();
                $confirmation->requester_notes = $data['notes'] ?? null;
            }
            $confirmation->save();

            // Check if both confirmed
            if ($confirmation->owner_confirmed_at && $confirmation->requester_confirmed_at) {
                $this->completeSwap($swapRequest);
            } else {
                // Notify other party that one confirmed
                $otherUserId = $isOwner ? $swapRequest->requester_id : $swapRequest->owner_id;
                $otherUser = \App\Models\User\User::find($otherUserId);
                if ($otherUser) {
                    $otherUser->notify(new SwapConfirmedNotification($swapRequest));
                }
            }
        });

        $swapRequest->refresh()->load('orderConfirmation');
        $latestConfirmation = $swapRequest->orderConfirmation;

        if ($swapRequest->status === 'completed' || ($latestConfirmation && $latestConfirmation->both_confirmed)) {
            return redirect()->route('swap.mySwaps')
                ->with('success', 'Swap completed! Both parties confirmed. Funds transferred to your wallet.');
        }

        return redirect()->route('swap.confirmation', $swapRequest)
            ->with('success', 'Receipt confirmed. Awaiting the other party.');
    }

    /**
     * Complete swap after both confirmations: release funds, create Swap record, send notifications.
     */
    private function completeSwap(SwapRequest $swapRequest): void
    {
        $this->swapOrderService->completeSwapAfterConfirmation($swapRequest);

        // Send completion notifications to both parties
        $swapRequest->owner->notify(new SwapCompletedNotification($swapRequest));
        $swapRequest->requester->notify(new SwapCompletedNotification($swapRequest));
    }
}
