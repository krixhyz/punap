<?php

namespace App\Http\Controllers\User;

use App\Models\User\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalDeposit;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use App\Models\Swap;
use App\Models\SwapRequest;
use App\Models\SwapOrderConfirmation;
use App\Services\CheckoutPricingService;
use App\Services\EsewaService;
use App\Services\KhaltiService;
use App\Services\InventoryReservationService;
use App\Services\EcoScoreService;
use App\Services\WalletLedgerService;
use App\Notifications\User\SwapPaymentReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Mail\RentalOrderCreated;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function calculateCheckout(Request $request, CheckoutPricingService $pricingService)
    {
        $validated = $request->validate([
            'flow_type' => 'required|in:purchase,rent,swap',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'rent_fee' => 'nullable|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'cash_topup' => 'nullable|numeric|min:0',
        ], [
            'flow_type.required' => 'Checkout flow type is required.',
            'flow_type.in' => 'Selected checkout flow is invalid.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        $flow = $validated['flow_type'];

        if ($flow === 'purchase') {
            $price = (float) ($validated['price'] ?? 0);
            $quantity = (int) ($validated['quantity'] ?? 1);
            $result = $pricingService->calculatePurchase($price * $quantity);
            $result['quantity'] = $quantity;

            return response()->json($result);
        }

        if ($flow === 'rent') {
            return response()->json(
                $pricingService->calculateRent(
                    (float) ($validated['rent_fee'] ?? 0),
                    (float) ($validated['deposit'] ?? 0)
                )
            );
        }

        return response()->json(
            $pricingService->calculateSwap((float) ($validated['cash_topup'] ?? 0))
        );
    }

    public function checkoutPay(
        Request $request,
        EsewaService $esewaService,
        KhaltiService $khaltiService,
        InventoryReservationService $inventory
    ) {
        $validated = $request->validate([
            'flow_type' => 'required|in:purchase,rent,swap',
            'product_id' => 'required_if:flow_type,purchase|nullable|integer|exists:products,id',
            'rental_request_id' => 'required_if:flow_type,rent|nullable|integer|exists:rental_requests,id',
            'swap_request_id' => 'required_if:flow_type,swap|nullable|integer|exists:swap_requests,id',
            'quantity' => 'required_if:flow_type,purchase|nullable|integer|min:1',
            'buyer_name' => 'required|string|min:2|max:255',
            'buyer_phone' => 'nullable|digits:10',
            'buyer_email' => 'required|email:rfc|max:255',
            'buyer_address' => 'nullable|string|max:1000',
            'payment_gateway' => 'required|in:esewa,khalti',
        ], [
            'product_id.required_if' => 'Product selection is required for purchase checkout.',
            'rental_request_id.required_if' => 'Rental request is required for rental checkout.',
            'swap_request_id.required_if' => 'Swap request is required for swap checkout.',
            'quantity.required_if' => 'Quantity is required for purchase checkout.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'payment_gateway.required' => 'Please choose a payment gateway.',
            'payment_gateway.in' => 'Selected payment gateway is invalid.',
        ]);

        if ($validated['flow_type'] === 'purchase') {
            $product = Product::findOrFail((int) $validated['product_id']);
            $request->merge(['quantity' => (int) ($validated['quantity'] ?? 1)]);

            return $this->createDirectOrderPayment($request, $product, $esewaService, $khaltiService, $inventory);
        }

        if ($validated['flow_type'] === 'rent') {
            $rentalRequest = RentalRequest::findOrFail((int) $validated['rental_request_id']);

            return $this->createRentalPayment($request, $rentalRequest, $esewaService, $khaltiService, $inventory);
        }

        $swapRequest = SwapRequest::findOrFail((int) $validated['swap_request_id']);

        return $this->createSwapPayment($request, $swapRequest, $esewaService, $khaltiService, $inventory);
    }

    public function verifyPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_reference' => 'nullable|string',
            'transaction_uuid' => 'nullable|string',
            'provider' => 'nullable|in:esewa,khalti,stripe',
        ]);

        $reference = (string) ($validated['payment_reference'] ?? '');
        $transactionUuid = (string) ($validated['transaction_uuid'] ?? '');

        if ($reference === '' && $transactionUuid === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Provide payment_reference or transaction_uuid.',
            ], 422);
        }

        $payment = Payment::query()
            ->when($reference !== '', function ($query) use ($reference) {
                $query->where('payment_reference', $reference);
            })
            ->when($reference === '' && $transactionUuid !== '', function ($query) use ($transactionUuid) {
                $query->where('transaction_uuid', $transactionUuid);
            })
            ->first();

        if (!$payment) {
            return response()->json(['ok' => false, 'message' => 'Payment not found.'], 404);
        }

        if (!empty($validated['provider']) && $payment->provider !== $validated['provider']) {
            return response()->json(['ok' => false, 'message' => 'Provider mismatch.'], 409);
        }

        if ($payment->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'ok' => true,
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'provider' => $payment->provider,
            'payment_reference' => $payment->payment_reference,
            'transaction_uuid' => $payment->transaction_uuid,
            'gross_amount' => (float) $payment->gross_amount,
            'fee_amount' => (float) $payment->fee_amount,
            'seller_amount' => (float) $payment->seller_amount,
            'platform_amount' => (float) $payment->platform_amount,
            'total_amount' => (float) $payment->total_amount,
        ]);
    }

    public function orderDetails(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['product', 'payment', 'buyer', 'seller']);

        return response()->json([
            'id' => $order->id,
            'transaction_type' => $order->transaction_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'quantity' => (int) $order->quantity,
            'unit_price' => (float) $order->unit_price,
            'subtotal' => (float) $order->subtotal,
            'service_fee' => (float) $order->service_fee,
            'total_amount' => (float) $order->total_amount,
            'buyer' => $order->buyer?->only(['id', 'name', 'email']),
            'seller' => $order->seller?->only(['id', 'name', 'email']),
            'product' => $order->product?->only(['id', 'title', 'status']),
            'payment' => $order->payment ? [
                'id' => $order->payment->id,
                'provider' => $order->payment->provider,
                'status' => $order->payment->status,
                'payment_reference' => $order->payment->payment_reference,
            ] : null,
        ]);
    }

    public function myTransactionHistory()
    {
        $userId = Auth::id();

        $rentalReferences = RentedRentals::where('renter_id', $userId)
            ->whereNotNull('payment_reference')
            ->pluck('payment_reference')
            ->unique()
            ->values();

        $rentalPaymentMap = Payment::whereIn('payment_reference', $rentalReferences)
            ->get()
            ->keyBy('payment_reference');

        $orders = Order::with(['product', 'payment'])
            ->where('buyer_id', $userId)
            ->latest()
            ->get()
            ->map(function (Order $order) {
                return [
                    'type' => 'purchase',
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'item' => $order->product?->title,
                    'subtotal' => (float) $order->subtotal,
                    'service_fee' => (float) $order->service_fee,
                    'total_amount' => (float) $order->total_amount,
                    'payment_reference' => $order->payment?->payment_reference,
                    'created_at' => $order->created_at,
                ];
            });

        $rentals = RentedRentals::with(['product'])
            ->where('renter_id', $userId)
            ->latest()
            ->get()
            ->map(function (RentedRentals $rental) use ($rentalPaymentMap) {
                $payment = $rentalPaymentMap->get($rental->payment_reference);

                return [
                    'type' => 'rent',
                    'id' => $rental->id,
                    'status' => $rental->status,
                    'payment_status' => $rental->payment_status,
                    'item' => $rental->product?->title,
                    'subtotal' => (float) $rental->rent_fare,
                    'service_fee' => (float) ($payment->fee_amount ?? 0),
                    'total_amount' => (float) $rental->total_amount,
                    'payment_reference' => $rental->payment_reference,
                    'created_at' => $rental->created_at,
                ];
            });

        $swaps = Swap::with(['requestedProduct'])
            ->where('owner_b_id', $userId)
            ->latest()
            ->get()
            ->map(function (Swap $swap) {
                return [
                    'type' => 'swap',
                    'id' => $swap->id,
                    'status' => $swap->status,
                    'payment_status' => $swap->offered_amount > 0 ? 'paid' : 'n/a',
                    'item' => $swap->requestedProduct?->title,
                    'subtotal' => (float) ($swap->offered_amount ?? 0),
                    'service_fee' => 0.0,
                    'total_amount' => (float) ($swap->offered_amount ?? 0),
                    'payment_reference' => null,
                    'created_at' => $swap->created_at,
                ];
            });

        $history = collect()
            ->concat($orders)
            ->concat($rentals)
            ->concat($swaps)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($history);
    }

    public function createDirectOrderPayment(
        Request $request,
        Product $product,
        EsewaService $esewaService,
        KhaltiService $khaltiService,
        InventoryReservationService $inventory
    ) {
        $provider = $this->resolveProvider($request);
        $buyerDetails = $this->validateCheckoutBuyerDetails($request, false);

        if ($product->user_id === Auth::id()) {
            return redirect()->route('products.show', $product->id)->with('error', 'You cannot buy your own product.');
        }

        if (!in_array('sell', $product->type ?? [])) {
            return redirect()->route('products.show', $product->id)->with('error', 'This item is not available for purchase.');
        }

        $availableQty = (int) $product->quantity;
        if ($availableQty < 1) {
            return redirect()->route('products.show', $product->id)->with('error', 'This item is out of stock.');
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $availableQty,
        ], [
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => "Quantity cannot exceed available stock ({$availableQty}).",
        ]);

        $quantity = (int) $validated['quantity'];

        try {
            DB::transaction(function () use ($product, $quantity, $inventory) {
                $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();
                $inventory->ensurePurchasableQuantity($lockedProduct, $quantity, now());
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('products.show', $product->id)->with('error', $e->getMessage());
        }

        $unitPrice = (float) ($product->price ?? 0);
        $items = [[
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
        ]];

        $payment = $this->createPaymentForOrderItems($items, 'order', $provider);

        // Add buyer details to payment payload
        $payload = $payment->request_payload ?? [];
        $payload['buyer_details'] = $buyerDetails;
        $payment->request_payload = $payload;
        $payment->save();
        return $this->initiatePayment($payment, $provider, $esewaService, $khaltiService, 'Order Payment');
    }

    public function createOrderPayment(Request $request, Order $order, EsewaService $esewaService, KhaltiService $khaltiService)
    {
        $provider = $this->resolveProvider($request);
        $buyerDetails = $this->validateCheckoutBuyerDetails($request);
        $this->authorize('buyerAccess', $order);

        if ($order->status !== 'pending') {
            return redirect()->route('products.myPurchases')->with('info', 'Order already processed.');
        }

        if (!in_array('sell', $order->product->type ?? [])) {
            return redirect()->route('products.myPurchases')->with('error', 'This item is no longer available for purchase.');
        }

        if ($order->reserved_until && $order->reserved_until->isPast()) {
            $order->status = 'cancelled';
            $order->save();
            return redirect()->route('products.index')->with('error', 'Order reservation expired.');
        }

        $order->fill($buyerDetails);
        $order->save();

        $payment = $this->createPaymentForOrders([$order], 'order', $provider, $buyerDetails);

        return $this->initiatePayment($payment, $provider, $esewaService, $khaltiService, 'Order Payment');
    }

    public function createCartPayment(Request $request, EsewaService $esewaService, KhaltiService $khaltiService, InventoryReservationService $inventory)
    {
        $validated = $this->validateCheckoutBuyerDetails($request);

        // Log for debugging
        \Log::info('createCartPayment - Validated buyer details', [
            'buyer_name' => $validated['buyer_name'],
            'buyer_phone' => $validated['buyer_phone'] ?? null,
            'buyer_email' => $validated['buyer_email'],
            'buyer_address' => substr($validated['buyer_address'] ?? '', 0, 100),
        ]);

        $provider = $this->resolveProvider($request);

        $cartItems = Auth::user()->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.checkout')->with('error', 'Cart is empty.');
        }

        try {
            $items = DB::transaction(function () use ($cartItems, $inventory) {
                $items = [];
                $now = now();

                $cartItems = $cartItems->sortBy('product_id')->values();

                foreach ($cartItems as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                    if (!$product || $product->quantity < 1) {
                        throw new \RuntimeException('No available stock for ' . ($product->title ?? 'product'));
                    }

                    try {
                        $inventory->ensurePurchasableQuantity($product, (int) $item->quantity, $now);
                    } catch (\RuntimeException $e) {
                        throw new \RuntimeException(($product->title ?? 'Product') . ': ' . $e->getMessage());
                    }

                    $unitPrice = (float) ($product->price ?? 0);
                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * (int) $item->quantity,
                    ];
                }

                return $items;
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.checkout')->with('error', $e->getMessage());
        }

        $payment = $this->createPaymentForOrderItems($items, 'cart', $provider);

        // Add buyer details to payment payload
        $buyerDetails = [
            'buyer_name' => $validated['buyer_name'],
            'buyer_phone' => $validated['buyer_phone'] ?? null,
            'buyer_email' => $validated['buyer_email'],
            'buyer_address' => $validated['buyer_address'] ?? null,
        ];
        $payload = $payment->request_payload ?? [];
        $payload['buyer_details'] = $buyerDetails;
        $payment->request_payload = $payload;
        $payment->save();

        return $this->initiatePayment($payment, $provider, $esewaService, $khaltiService, 'Cart Checkout');
    }

    public function esewaSuccess(Request $request, EsewaService $esewaService, InventoryReservationService $inventory)
    {
        $payload = $this->decodeEsewaPayload($request);
        if (!$payload) {
            return redirect()->route('products.index')->with('error', 'Invalid payment response.');
        }

        $transactionUuid = $payload['transaction_uuid'] ?? null;
        if (!$transactionUuid) {
            return redirect()->route('products.index')->with('error', 'Missing transaction reference.');
        }

        $payment = Payment::where('transaction_uuid', $transactionUuid)->first();
        if (!$payment) {
            return redirect()->route('products.index')->with('error', 'Payment not found.');
        }

        if ($payment->provider !== 'esewa') {
            return redirect()->route('products.index')->with('error', 'Payment provider mismatch.');
        }

        if ($payment->status === 'complete') {
            return $this->alreadyProcessedRedirect($payment);
        }

        $secretKey = config('esewa.secret_key');
        if (!$esewaService->verifySignature($payload, $secretKey)) {
            $this->failPaymentBySource($payment, [
                'callback' => $payload,
                'reason' => 'signature_mismatch',
            ], $inventory);
            return redirect()->route('products.index')->with('error', 'Payment signature mismatch.');
        }

        $statusResponse = $this->checkEsewaStatus($payment);
        $responsePayload = [
            'callback' => $payload,
            'status' => $statusResponse,
        ];

        if (($statusResponse['status'] ?? '') !== 'COMPLETE') {
            $payment->status = 'pending';
            $payment->response_payload = $responsePayload;
            $payment->save();
            return redirect()->route('products.index')->with('info', 'Payment pending verification.');
        }

        return $this->completePaymentBySource(
            $payment,
            $payload['transaction_code'] ?? ($statusResponse['ref_id'] ?? null),
            $inventory,
            $responsePayload
        );
    }

    public function esewaFailure(Request $request, InventoryReservationService $inventory)
    {
        $payload = $this->decodeEsewaPayload($request);
        $transactionUuid = $payload['transaction_uuid'] ?? null;

        if ($transactionUuid) {
            $payment = Payment::where('transaction_uuid', $transactionUuid)->first();
            if ($payment) {
                $this->failPaymentBySource($payment, $payload, $inventory);
                return $this->redirectBySource($payment, 'error', 'eSewa payment failed or was cancelled.');
            }
        }

        return redirect()->route('products.index')->with('error', 'Payment failed or was cancelled.');
    }

    public function khaltiReturn(Request $request, KhaltiService $khaltiService, InventoryReservationService $inventory)
    {
        $transactionUuid = (string) ($request->input('purchase_order_id') ?? $request->input('transaction_uuid'));
        if ($transactionUuid === '') {
            return redirect()->route('products.index')->with('error', 'Missing purchase order identifier.');
        }

        $payment = Payment::where('transaction_uuid', $transactionUuid)->first();
        if (!$payment) {
            return redirect()->route('products.index')->with('error', 'Payment not found.');
        }

        if ($payment->provider !== 'khalti') {
            return redirect()->route('products.index')->with('error', 'Payment provider mismatch.');
        }

        if ($payment->status === 'complete') {
            return $this->alreadyProcessedRedirect($payment);
        }

        $callbackPayload = $request->all();
        $pidx = (string) ($request->input('pidx') ?? ($payment->request_payload['khalti']['pidx'] ?? ''));
        if ($pidx === '') {
            $this->failPaymentBySource($payment, [
                'callback' => $callbackPayload,
                'reason' => 'missing_pidx',
            ], $inventory);

            return $this->redirectBySource($payment, 'error', 'Missing Khalti payment identifier.');
        }

        $lookupResponse = $khaltiService->lookupPayment($pidx);
        $lookupBody = is_array($lookupResponse['body'] ?? null) ? $lookupResponse['body'] : [];

        $responsePayload = [
            'callback' => $callbackPayload,
            'lookup' => $lookupBody,
            'lookup_http_status' => $lookupResponse['status'] ?? null,
        ];

        $lookupStatus = strtolower((string) ($lookupBody['status'] ?? ''));
        if (($lookupResponse['ok'] ?? false) && $lookupStatus === 'completed') {
            $receivedAmountPaisa = (int) ($lookupBody['total_amount'] ?? 0);
            $expectedAmountPaisa = $khaltiService->toPaisa((float) $payment->total_amount);

            if ($receivedAmountPaisa !== $expectedAmountPaisa) {
                $responsePayload['reason'] = 'amount_mismatch';
                $responsePayload['expected_amount_paisa'] = $expectedAmountPaisa;
                $responsePayload['received_amount_paisa'] = $receivedAmountPaisa;
                $this->failPaymentBySource($payment, $responsePayload, $inventory);

                return $this->redirectBySource($payment, 'error', 'Khalti amount verification failed.');
            }

            return $this->completePaymentBySource(
                $payment,
                (string) ($lookupBody['transaction_id'] ?? $request->input('transaction_id') ?? ''),
                $inventory,
                $responsePayload
            );
        }

        if ($lookupStatus === 'pending' || $lookupStatus === 'initiated') {
            $payment->status = 'pending';
            $payment->response_payload = $responsePayload;
            $payment->save();

            return $this->redirectBySource($payment, 'info', 'Khalti payment is pending verification.');
        }

        $this->failPaymentBySource($payment, $responsePayload, $inventory);
        return $this->redirectBySource($payment, 'error', 'Khalti payment failed or was cancelled.');
    }

    public function createRentalPayment(Request $request, RentalRequest $rentalRequest, EsewaService $esewaService, KhaltiService $khaltiService, InventoryReservationService $inventory)
    {
        $provider = $this->resolveProvider($request);
        $pricingService = new CheckoutPricingService();
        $this->authorize('pay', $rentalRequest);

        if ($rentalRequest->status !== 'approved') {
            return redirect()->route('products.index')->with('error', 'Rental request is not approved for payment.');
        }

        if ($rentalRequest->reserved_until && $rentalRequest->reserved_until->isPast()) {
            $inventory->releaseRentalReservation($rentalRequest);
            return redirect()->route('products.index')->with('error', 'Rental reservation expired. Please submit a new request.');
        }

        $existingPayment = $this->findExistingSourcePayment('rental', 'rental_request_id', $rentalRequest->id);
        if ($existingPayment && in_array($existingPayment->status, ['pending', 'complete'], true)) {
            return redirect()->route('products.index')->with('info', 'A payment already exists for this rental request.');
        }

        $validated = $this->validateCheckoutBuyerDetails($request);

        $pricing = $pricingService->calculateRent(
            (float) ($rentalRequest->total_amount ?? 0),
            (float) ($rentalRequest->rent_deposit ?? 0)
        );

        $transactionUuid = (string) Str::uuid();
        $productCode = $provider === 'esewa' ? config('esewa.product_code') : 'KHALTI';

        $payment = Payment::create([
            'user_id' => Auth::id(),
            'provider' => $provider,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'amount' => $pricing['subtotal'] + $pricing['deposit'],
            'gross_amount' => $pricing['subtotal'] + $pricing['deposit'],
            'fee_amount' => $pricing['service_fee'],
            'seller_amount' => $pricing['seller_amount'],
            'platform_amount' => $pricing['platform_amount'],
            'fee_percentage' => $pricing['fee_percentage'],
            'tax_amount' => 0,
            'service_charge' => $pricing['service_fee'],
            'delivery_charge' => 0,
            'total_amount' => $pricing['total_amount'],
            'status' => 'pending',
            'payment_reference' => $transactionUuid,
            'request_payload' => [
                'source' => 'rental',
                'gateway' => $provider,
                'rental_request_id' => $rentalRequest->id,
                'pricing' => $pricing,
                'buyer_details' => [
                    'buyer_name' => $validated['buyer_name'],
                    'buyer_phone' => $validated['buyer_phone'] ?? null,
                    'buyer_email' => $validated['buyer_email'],
                    'buyer_address' => $validated['buyer_address'] ?? null,
                ],
            ],
        ]);

        return $this->initiatePayment($payment, $provider, $esewaService, $khaltiService, 'Rental Checkout');
    }

    public function createSwapPayment(Request $request, SwapRequest $swapRequest, EsewaService $esewaService, KhaltiService $khaltiService, InventoryReservationService $inventory)
    {
        $provider = $this->resolveProvider($request);
        $pricingService = new CheckoutPricingService();

        $this->authorize('pay', $swapRequest);

        $payerId = match ($swapRequest->money_direction) {
            'requester_offers_cash' => $swapRequest->requester_id,
            'owner_asks_cash' => $swapRequest->owner_id,
            default => null,
        };

        if ($swapRequest->status !== 'awaiting_payment') {
            return redirect()->route('dashboard')->with('error', 'Swap is not awaiting payment.');
        }

        if (!$swapRequest->offered_product_id) {
            return redirect()->route('dashboard')->with('error', 'Swap requires an offered product to proceed.');
        }

        if ($swapRequest->reserved_until && $swapRequest->reserved_until->isPast()) {
            $inventory->releaseSwapReservation($swapRequest);
            return redirect()->route('dashboard')->with('error', 'Swap reservation expired.');
        }

        $existingPayment = $this->findExistingSourcePayment('swap', 'swap_request_id', $swapRequest->id);
        if ($existingPayment && in_array($existingPayment->status, ['pending', 'complete'], true)) {
            return redirect()->route('dashboard')->with('info', 'A payment already exists for this swap request.');
        }

        $validated = $this->validateCheckoutBuyerDetails($request);

        $cashTopup = $swapRequest->money_direction === 'owner_asks_cash'
            ? (float) ($swapRequest->asking_amount ?? 0)
            : (float) ($swapRequest->offered_amount ?? 0);

        if ($cashTopup <= 0) {
            return redirect()->route('swap.checkout', $swapRequest)->with('error', 'No payment required for this swap.');
        }

        $pricing = $pricingService->calculateSwap($cashTopup);

        $transactionUuid = (string) Str::uuid();
        $productCode = $provider === 'esewa' ? config('esewa.product_code') : 'KHALTI';

        $payment = Payment::create([
            'user_id' => Auth::id(),
            'provider' => $provider,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'amount' => $pricing['subtotal'],
            'gross_amount' => $pricing['subtotal'],
            'fee_amount' => $pricing['service_fee'],
            'seller_amount' => $pricing['seller_amount'],
            'platform_amount' => $pricing['platform_amount'],
            'fee_percentage' => $pricing['fee_percentage'],
            'tax_amount' => 0,
            'service_charge' => 0,
            'delivery_charge' => 0,
            'total_amount' => $pricing['total_amount'],
            'status' => 'pending',
            'payment_reference' => $transactionUuid,
            'request_payload' => [
                'source' => 'swap',
                'gateway' => $provider,
                'swap_request_id' => $swapRequest->id,
                'money_direction' => $swapRequest->money_direction,
                'payer_id' => $payerId,
                'product_id' => $swapRequest->product_id,
                'offered_product_id' => $swapRequest->offered_product_id,
                'pricing' => $pricing,
                'buyer_details' => [
                    'buyer_name' => $validated['buyer_name'],
                    'buyer_phone' => $validated['buyer_phone'] ?? null,
                    'buyer_email' => $validated['buyer_email'],
                    'buyer_address' => $validated['buyer_address'] ?? null,
                ],
            ],
        ]);

        return $this->initiatePayment($payment, $provider, $esewaService, $khaltiService, 'Swap Checkout');
    }

    private function createPaymentForOrders(array $orders, string $source, string $provider, array $buyerDetails = []): Payment
    {
        $subtotal = 0;
        foreach ($orders as $order) {
            $subtotal += (float) ($order->total_price ?? 0);
        }

        $pricingService = new CheckoutPricingService();
        $pricing = $pricingService->calculatePurchase($subtotal);

        $transactionUuid = (string) Str::uuid();
        $productCode = $provider === 'esewa' ? config('esewa.product_code') : 'KHALTI';

        $payment = Payment::create([
            'user_id' => Auth::id(),
            'provider' => $provider,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'amount' => $pricing['subtotal'],
            'gross_amount' => $pricing['subtotal'],
            'fee_amount' => $pricing['service_fee'],
            'seller_amount' => $pricing['seller_amount'],
            'platform_amount' => $pricing['platform_amount'],
            'fee_percentage' => $pricing['fee_percentage'],
            'tax_amount' => 0,
            'service_charge' => $pricing['service_fee'],
            'delivery_charge' => 0,
            'total_amount' => $pricing['total_amount'],
            'status' => 'pending',
            'payment_reference' => $transactionUuid,
            'request_payload' => [
                'orders' => collect($orders)->pluck('id')->all(),
                'source' => $source,
                'gateway' => $provider,
                'pricing' => $pricing,
                'buyer_details' => $buyerDetails,
            ],
        ]);

        foreach ($orders as $order) {
            $order->payment_id = $payment->id;
            $order->save();
        }

        return $payment;
    }

    private function createPaymentForOrderItems(array $items, string $source, string $provider): Payment
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['total_price'] ?? 0);
        }

        $pricingService = new CheckoutPricingService();
        $pricing = $pricingService->calculatePurchase($subtotal);

        $transactionUuid = (string) Str::uuid();
        $productCode = $provider === 'esewa' ? config('esewa.product_code') : 'KHALTI';

        return Payment::create([
            'user_id' => Auth::id(),
            'provider' => $provider,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'amount' => $pricing['subtotal'],
            'gross_amount' => $pricing['subtotal'],
            'fee_amount' => $pricing['service_fee'],
            'seller_amount' => $pricing['seller_amount'],
            'platform_amount' => $pricing['platform_amount'],
            'fee_percentage' => $pricing['fee_percentage'],
            'tax_amount' => 0,
            'service_charge' => $pricing['service_fee'],
            'delivery_charge' => 0,
            'total_amount' => $pricing['total_amount'],
            'status' => 'pending',
            'payment_reference' => $transactionUuid,
            'request_payload' => [
                'source' => $source,
                'gateway' => $provider,
                'order_items' => $items,
                'pricing' => $pricing,
            ],
        ]);
    }

    private function initiatePayment(Payment $payment, string $provider, EsewaService $esewaService, KhaltiService $khaltiService, string $purchaseOrderName)
    {
        if ($provider === 'khalti') {
            return $this->renderKhaltiRedirect($payment, $khaltiService, $purchaseOrderName);
        }

        return $this->renderEsewaForm($payment, $esewaService);
    }

    private function renderKhaltiRedirect(Payment $payment, KhaltiService $khaltiService, string $purchaseOrderName)
    {
        $secretKey = config('khalti.secret_key');
        $initiateUrl = config('khalti.initiate_url');

        if (blank($secretKey) || blank($initiateUrl)) {
            return redirect()
                ->route('products.index')
                ->with('error', 'Khalti payment is not configured. Please set KHALTI_SECRET_KEY and KHALTI_INITIATE_URL.');
        }

        $user = Auth::user();
        $payload = [
            'return_url' => config('khalti.return_url'),
            'website_url' => config('khalti.website_url'),
            'amount' => $khaltiService->toPaisa((float) $payment->total_amount),
            'purchase_order_id' => $payment->transaction_uuid,
            'purchase_order_name' => $purchaseOrderName,
            'customer_info' => array_filter([
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
                'phone' => $user->phone ?? null,
            ]),
        ];

        $response = $khaltiService->initiatePayment($payload);
        $responseBody = is_array($response['body'] ?? null) ? $response['body'] : [];
        $paymentUrl = $responseBody['payment_url'] ?? null;

        $payment->request_payload = array_merge($payment->request_payload ?? [], [
            'khalti_initiate_payload' => $payload,
            'khalti_initiate_response' => $responseBody,
        ]);

        if (!($response['ok'] ?? false) || blank($paymentUrl)) {
            $payment->status = 'failed';
            $payment->response_payload = [
                'initiate_response' => $responseBody,
                'http_status' => $response['status'] ?? null,
            ];
            $payment->save();

            return redirect()->route('products.index')->with('error', 'Unable to initiate Khalti payment. Please try again.');
        }

        $payment->request_payload = array_merge($payment->request_payload, [
            'khalti' => [
                'pidx' => $responseBody['pidx'] ?? null,
                'payment_url' => $paymentUrl,
                'expires_at' => $responseBody['expires_at'] ?? null,
            ],
        ]);
        $payment->save();

        return redirect()->away($paymentUrl);
    }

    private function renderEsewaForm(Payment $payment, EsewaService $esewaService)
    {
        $productCode = $payment->product_code;
        $signedFieldNames = $esewaService->buildSignedFields();
        $totalAmount = $this->formatAmount($payment->total_amount);
        $transactionUuid = $payment->transaction_uuid;
        $secretKey = config('esewa.secret_key');
        $formUrl = config('esewa.form_url');

        if (blank($productCode) || blank($secretKey) || blank($formUrl)) {
            return redirect()
                ->route('products.index')
                ->with('error', 'eSewa payment is not configured. Please set ESEWA_PRODUCT_CODE, ESEWA_SECRET_KEY and ESEWA_FORM_URL.');
        }

        $signature = $esewaService->buildSignature(
            $totalAmount,
            $transactionUuid,
            $productCode,
            $secretKey
        );

        $payload = [
            'amount' => $this->formatAmount($payment->amount),
            'tax_amount' => $this->formatAmount($payment->tax_amount),
            'product_service_charge' => $this->formatAmount($payment->service_charge),
            'product_delivery_charge' => $this->formatAmount($payment->delivery_charge),
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'success_url' => config('esewa.success_url'),
            'failure_url' => config('esewa.failure_url'),
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ];

        $payment->request_payload = array_merge($payment->request_payload ?? [], $payload);
        $payment->save();

        return view('payments.esewa_form', [
            'formUrl' => $formUrl,
            'payload' => $payload,
        ]);
    }

    private function decodeEsewaPayload(Request $request): ?array
    {
        $encoded = $request->input('data') ?? $request->input('response') ?? $request->input('payload');

        if ($encoded) {
            $decoded = base64_decode($encoded, true);
            if ($decoded !== false) {
                $json = json_decode($decoded, true);
                if (is_array($json)) {
                    return $json;
                }
            }
        }

        $data = $request->all();
        return is_array($data) && !empty($data) ? $data : null;
    }

    private function checkEsewaStatus(Payment $payment): array
    {
        $url = config('esewa.status_url');
        $query = [
            'product_code' => $payment->product_code,
            'total_amount' => $this->formatAmount($payment->total_amount),
            'transaction_uuid' => $payment->transaction_uuid,
        ];

        $response = Http::get($url, $query);
        if (!$response->ok()) {
            return ['status' => 'AMBIGUOUS'];
        }

        return $response->json() ?? ['status' => 'AMBIGUOUS'];
    }

    private function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function completePaymentBySource(
        Payment $payment,
        ?string $transactionCode,
        InventoryReservationService $inventory,
        array $responsePayload = []
    ) {
        $source = $payment->request_payload['source'] ?? 'order';
        $ecoScoreService = new EcoScoreService();
        $walletLedgerService = new WalletLedgerService();

        if ($source === 'swap') {
            DB::transaction(function () use ($payment, $transactionCode, $responsePayload, $inventory, $ecoScoreService, $walletLedgerService) {
                $payment->status = 'complete';
                $payment->transaction_code = $transactionCode;
                if (!empty($transactionCode)) {
                    $payment->payment_reference = $transactionCode;
                }
                $payment->response_payload = $responsePayload;
                $payment->save();

                $swapRequestId = $payment->request_payload['swap_request_id'] ?? null;
                $swapRequest = SwapRequest::with(['product', 'offeredProduct', 'owner', 'requester'])
                    ->lockForUpdate()
                    ->find($swapRequestId);

                if (!$swapRequest || $swapRequest->status !== 'awaiting_payment') {
                    return;
                }

                // Set status to 'paid' (not 'accepted') - funds held in escrow
                $swapRequest->status = 'paid';
                $swapRequest->reserved_until = null;
                $swapRequest->order_details_sent_at = now();
                $swapRequest->save();

                // Create swap order confirmation for dual confirmation flow
                SwapOrderConfirmation::firstOrCreate([
                    'swap_request_id' => $swapRequest->id,
                ]);

                // Send order-details email to both parties
                Mail::to($swapRequest->owner->email)->send(
                    new \App\Mail\SwapOrderCreated($swapRequest, role: 'owner')
                );
                Mail::to($swapRequest->requester->email)->send(
                    new \App\Mail\SwapOrderCreated($swapRequest, role: 'requester')
                );

                // Mark order email as sent
                $swapRequest->orderConfirmation?->update([
                    'order_details_email_sent_at' => now(),
                ]);

                // DO NOT create Swap record - wait until both confirm
                // DO NOT credit wallet yet - wait until both confirm
            });

            $swapRequestId = $payment->request_payload['swap_request_id'] ?? null;
            $swapRequest = SwapRequest::with(['owner', 'requester'])->find($swapRequestId);
            if ($swapRequest) {
                $counterParty = ((int) $payment->user_id === (int) $swapRequest->owner_id)
                    ? $swapRequest->requester
                    : $swapRequest->owner;

                if ($counterParty) {
                    $counterParty->notify(new SwapPaymentReceivedNotification($swapRequest));
                }
            }

            return redirect()->route('swap.mySwaps', [
                'tab' => 'pending',
                'swap_request_id' => $swapRequestId,
            ])->with('success', 'Payment received. Continue dispatch and receipt confirmation from My Swaps.');
        }

        if ($source === 'rental') {
            $rentalCompleted = false;

            DB::transaction(function () use ($payment, $transactionCode, $responsePayload, &$rentalCompleted, $inventory, $ecoScoreService, $walletLedgerService) {
                $payment->status = 'complete';
                $payment->transaction_code = $transactionCode;
                if (!empty($transactionCode)) {
                    $payment->payment_reference = $transactionCode;
                }
                $payment->response_payload = $responsePayload;
                $payment->save();

                $rentalRequestId = $payment->request_payload['rental_request_id'] ?? null;
                $rentalRequest = RentalRequest::with(['product', 'rental'])->lockForUpdate()->find($rentalRequestId);
                if (!$rentalRequest || $rentalRequest->status !== 'approved') {
                    return;
                }

                if ($rentalRequest->reserved_until && $rentalRequest->reserved_until->isPast()) {
                    $inventory->releaseRentalReservation($rentalRequest);
                    $payment->status = 'failed';
                    $payment->save();
                    return;
                }

                if (!$rentalRequest->stock_reserved) {
                    $product = Product::lockForUpdate()->find($rentalRequest->product_id);
                    if (!$product || $product->quantity < 1) {
                        $payment->status = 'failed';
                        $payment->save();
                        return;
                    }

                    $inventory->consumeProductQuantity($product, 1, 'rented');

                    $rentalRequest->stock_reserved = true;
                }

                $rental = $rentalRequest->rental;
                if (!$rental) {
                    $rental = Rental::create([
                        'product_id' => $rentalRequest->product_id,
                        'owner_id' => $rentalRequest->owner_id,
                        'rent_fare' => $rentalRequest->product->rent_fare ?? 0,
                        'rent_deposit' => $rentalRequest->rent_deposit ?? 0,
                        'status' => 'rented',
                    ]);
                }

                $rentedRental = RentedRentals::create([
                    'rental_id' => $rental->id,
                    'product_id' => $rentalRequest->product_id,
                    'owner_id' => $rentalRequest->owner_id,
                    'renter_id' => $rentalRequest->renter_id,
                    'rent_fare' => $rental->rent_fare ?? 0,
                    'rent_deposit' => $rentalRequest->rent_deposit ?? 0,
                    'rent_type' => $rental->rent_type ?? 'daily',
                    'duration' => $rentalRequest->duration,
                    'start_date' => $rentalRequest->start_date,
                    'end_date' => $rentalRequest->end_date,
                    'total_amount' => $payment->total_amount,
                    'payment_status' => 'paid',
                    'payment_reference' => $payment->payment_reference ?? $payment->transaction_code,
                    'status' => 'active',
                ]);

                $rentalRequest->loadMissing(['owner', 'renter', 'product']);
                Mail::to($rentalRequest->owner->email)->send(
                    new RentalOrderCreated($rentalRequest, $rentedRental, role: 'owner')
                );
                Mail::to($rentalRequest->renter->email)->send(
                    new RentalOrderCreated($rentalRequest, $rentedRental, role: 'renter')
                );

                $depositAmount = (float) ($rentalRequest->rent_deposit ?? 0);
                if ($depositAmount > 0) {
                    RentalDeposit::updateOrCreate(
                        ['rented_rental_id' => $rentedRental->id],
                        [
                            'payment_id' => $payment->id,
                            'amount' => $depositAmount,
                            'deduction_amount' => 0,
                            'refund_amount' => 0,
                            'status' => 'held',
                            'refund_status' => 'pending',
                            'gateway' => $payment->provider,
                            'gateway_reference' => $payment->transaction_code,
                        ]
                    );
                }
                
                // Record eco-impact for rented product
                $product = Product::find($rentalRequest->product_id);
                if ($product) {
                    $ecoScoreService->recordEcoImpact($product, 'rent', $payment->user_id, (int) $rentedRental->id);
                }

                $walletLedgerService->creditSaleIfMissing(
                    (int) $rentalRequest->owner_id,
                    (float) ($payment->seller_amount ?? 0),
                    'rental_income',
                    'rented_rental',
                    (int) $rentedRental->id,
                    [
                        'payment_id' => $payment->id,
                        'transaction_code' => $payment->transaction_code,
                    ]
                );

                $walletLedgerService->creditPlatformFeeIfMissing(
                    (float) ($payment->platform_amount ?? 0),
                    'rental_service_fee',
                    'payment',
                    (int) $payment->id,
                    [
                        'rental_request_id' => $rentalRequestId,
                    ]
                );

                $rentalRequest->delete();
                $rentalCompleted = true;
            });

            if (!$rentalCompleted) {
                return redirect()->route('products.index')->with('error', 'Rental payment could not be completed. Reservation expired or stock unavailable.');
            }

            return redirect()->route('products.index')->with('success', 'Rental payment completed successfully.');
        }

        DB::transaction(function () use ($payment, $transactionCode, $responsePayload, $inventory, $source, $ecoScoreService, $walletLedgerService) {
            $orderItems = $payment->request_payload['order_items'] ?? [];
            $buyerDetails = $payment->request_payload['buyer_details'] ?? [];
            $legacyOrders = $payment->orders()->with('product')->lockForUpdate()->get();
            $paymentSubtotal = (float) ($payment->gross_amount ?: $payment->amount);
            $paymentFee = (float) ($payment->fee_amount ?: $payment->service_charge);

            if (!empty($orderItems)) {
                $orderedItems = collect($orderItems)->sortBy('product_id')->values();

                foreach ($orderedItems as $item) {
                    $product = Product::where('id', $item['product_id'] ?? 0)->lockForUpdate()->first();
                    if (!$product) {
                        throw new \RuntimeException('Product no longer exists.');
                    }

                    $quantity = (int) ($item['quantity'] ?? 0);
                    $lineSubtotal = (float) ($item['total_price'] ?? 0);
                    $lineFee = $paymentSubtotal > 0
                        ? round(($lineSubtotal / $paymentSubtotal) * $paymentFee, 2)
                        : 0.0;
                    $inventory->ensurePurchasableQuantity($product, $quantity, now());
                    $inventory->consumeProductQuantity($product, $quantity, 'sold');

                    $order = Order::create([
                        'buyer_id' => $payment->user_id,
                        'seller_id' => $product->user_id,
                        'product_id' => $product->id,
                        'payment_id' => $payment->id,
                        'transaction_type' => 'buy',
                        'quantity' => $quantity,
                        'unit_price' => (float) ($item['unit_price'] ?? ($product->price ?? 0)),
                        'total_price' => $lineSubtotal,
                        'subtotal' => $lineSubtotal,
                        'service_fee' => $lineFee,
                        'total_amount' => $lineSubtotal + $lineFee,
                        'status' => 'completed',
                        'payment_status' => 'paid',
                        'reserved_until' => null,
                        'buyer_name' => $buyerDetails['buyer_name'] ?? '',
                        'buyer_phone' => $buyerDetails['buyer_phone'] ?? '',
                        'buyer_email' => $buyerDetails['buyer_email'] ?? '',
                        'buyer_address' => $buyerDetails['buyer_address'] ?? '',
                    ]);

                    // Send notifications to seller
                    $seller = $product->owner ?? $product->user;
                    if ($seller) {
                        $seller->notify(new \App\Notifications\User\OrderNotification($order));
                        Mail::to($seller->email)->send(new \App\Mail\OrderCreated($order));
                    }

                    $walletLedgerService->creditSaleIfMissing(
                        (int) $order->seller_id,
                        (float) $lineSubtotal,
                        'product_sale',
                        'order',
                        (int) $order->id,
                        [
                            'payment_id' => $payment->id,
                            'service_fee' => $lineFee,
                        ]
                    );
                    
                    // Record eco-impact for sold product
                    $ecoScoreService->recordEcoImpact($product, 'sell', $payment->user_id, (int) $order->id);
                }
            } else {
                foreach ($legacyOrders as $order) {
                    if ($order->status === 'completed') {
                        continue;
                    }

                    $product = Product::where('id', $order->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $inventory->consumeProductQuantity($product, (int) $order->quantity, 'sold');
                        
                        // Record eco-impact for sold product
                        $ecoScoreService->recordEcoImpact($product, 'sell', $payment->user_id, (int) $order->id);
                    }

                    $order->status = 'completed';
                    $order->payment_status = 'paid';
                    if ((float) $order->subtotal === 0.0) {
                        $order->subtotal = (float) ($order->total_price ?? 0);
                    }
                    if (is_null($order->service_fee)) {
                        $order->service_fee = 0;
                    }
                    if ((float) $order->total_amount === 0.0) {
                        $order->total_amount = (float) $order->subtotal + (float) $order->service_fee;
                    }
                    $order->save();

                    $sellerId = (int) ($order->seller_id ?: ($product?->user_id ?? 0));
                    if ($sellerId > 0) {
                        $walletLedgerService->creditSaleIfMissing(
                            $sellerId,
                            (float) $order->subtotal,
                            'product_sale',
                            'order',
                            (int) $order->id,
                            [
                                'payment_id' => $payment->id,
                                'service_fee' => (float) $order->service_fee,
                            ]
                        );
                    }
                }
            }

            $payment->status = 'complete';
            $payment->transaction_code = $transactionCode;
            if (!empty($transactionCode)) {
                $payment->payment_reference = $transactionCode;
            }
            $payment->response_payload = $responsePayload;
            $payment->save();

            $walletLedgerService->creditPlatformFeeIfMissing(
                (float) ($payment->platform_amount ?? 0),
                'order_service_fee',
                'payment',
                (int) $payment->id,
                [
                    'source' => $source,
                ]
            );

            if ($source === 'cart') {
                $productIds = collect($orderItems)->pluck('product_id')->filter()->all();
                if (!empty($productIds)) {
                    CartItem::where('user_id', $payment->user_id)
                        ->whereIn('product_id', $productIds)
                        ->delete();
                }
            }
        });

        return redirect()->route('products.myPurchases')->with('success', 'Payment completed successfully.');
    }

    private function failPaymentBySource(Payment $payment, array $responsePayload, InventoryReservationService $inventory): void
    {
        if ($payment->status === 'complete') {
            return;
        }

        $payment->status = 'failed';
        $payment->response_payload = $responsePayload;
        $payment->save();

        $source = $payment->request_payload['source'] ?? null;

        if ($source === 'swap') {
            $swapRequestId = $payment->request_payload['swap_request_id'] ?? null;
            if ($swapRequestId) {
                $swapRequest = SwapRequest::find($swapRequestId);
                if ($swapRequest && $swapRequest->status === 'awaiting_payment') {
                    $inventory->releaseSwapReservation($swapRequest);
                }
            }
            return;
        }

        if ($source === 'rental') {
            $rentalRequestId = $payment->request_payload['rental_request_id'] ?? null;
            if ($rentalRequestId) {
                $rentalRequest = RentalRequest::find($rentalRequestId);
                if ($rentalRequest && $rentalRequest->status === 'approved') {
                    $inventory->releaseRentalReservation($rentalRequest);
                }
            }
            return;
        }

        if ($payment->orders()->exists()) {
            $payment->orders()->where('status', 'pending')->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'reserved_until' => now(),
            ]);
        }
    }

    private function findExistingSourcePayment(string $source, string $key, int $value): ?Payment
    {
        return Payment::query()
            ->whereIn('status', ['pending', 'complete'])
            ->where('request_payload->source', $source)
            ->where('request_payload->' . $key, $value)
            ->latest('id')
            ->first();
    }

    private function alreadyProcessedRedirect(Payment $payment)
    {
        return $this->redirectBySource($payment, 'info', 'Payment already processed.');
    }

    private function validateCheckoutBuyerDetails(Request $request, bool $strict = true): array
    {
        $phone = preg_replace('/\D+/', '', (string) $request->input('buyer_phone', ''));
        $user = $request->user();

        $request->merge([
            'buyer_name' => trim((string) $request->input('buyer_name', (string) ($user?->name ?? ''))),
            'buyer_email' => strtolower(trim((string) $request->input('buyer_email', (string) ($user?->email ?? '')))),
            'buyer_address' => trim((string) $request->input('buyer_address', (string) ($user?->address ?? ''))),
            'buyer_phone' => $phone !== '' ? substr($phone, -10) : null,
        ]);

        $rules = [
            'buyer_name' => ($strict ? 'required' : 'nullable') . '|string|min:2|max:255',
            'buyer_phone' => 'nullable|digits:10',
            'buyer_email' => ($strict ? 'required' : 'nullable') . '|email:rfc|max:255',
            'buyer_address' => 'nullable|string|max:1000',
        ];

        $validated = $request->validate($rules, [
            'buyer_name.required' => 'Please enter your full name.',
            'buyer_name.min' => 'Full name must be at least 2 characters.',
            'buyer_phone.digits' => 'Phone number must be exactly 10 digits.',
            'buyer_email.required' => 'Please enter your email address.',
            'buyer_email.email' => 'Please enter a valid email address.',
        ], [
            'buyer_name' => 'full name',
            'buyer_phone' => 'phone number',
            'buyer_email' => 'email address',
            'buyer_address' => 'delivery address',
        ]);

        return [
            'buyer_name' => $validated['buyer_name'] ?? ($user?->name ?? ''),
            'buyer_phone' => $validated['buyer_phone'] ?? null,
            'buyer_email' => $validated['buyer_email'] ?? ($user?->email ?? ''),
            'buyer_address' => $validated['buyer_address'] ?? null,
        ];
    }

    private function resolveProvider(Request $request): string
    {
        $validated = $request->validate([
            'payment_gateway' => ['required', 'in:esewa,khalti'],
        ], [
            'payment_gateway.required' => 'Please choose a payment gateway.',
            'payment_gateway.in' => 'Selected payment gateway is invalid.',
        ]);

        return strtolower((string) $validated['payment_gateway']);
    }

    private function redirectBySource(Payment $payment, string $level, string $message)
    {
        $source = $payment->request_payload['source'] ?? 'order';

        if ($source === 'swap') {
            return redirect()->route('dashboard')->with($level, $message);
        }

        if ($source === 'rental') {
            return redirect()->route('products.index')->with($level, $message);
        }

        return redirect()->route('products.myPurchases')->with($level, $message);
    }
}
