<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SwapRequest;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KhaltiCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_checkout_accepts_khalti_and_creates_khalti_payment(): void
    {
        $this->configureKhalti();

        [$buyer, $order] = $this->makeOrderContext();

        Http::fake([
            config('khalti.initiate_url') => Http::response([
                'pidx' => 'PIDX-ORDER-1',
                'payment_url' => 'https://test-pay.khalti.com/?pidx=PIDX-ORDER-1',
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ], 200),
        ]);

        $response = $this->actingAs($buyer)->post(route('order.confirm', $order), [
            'payment_gateway' => 'khalti',
        ]);

        $response->assertRedirect('https://test-pay.khalti.com/?pidx=PIDX-ORDER-1');

        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertSame('khalti', $payment->provider);
        $this->assertSame('pending', $payment->status);

        $order->refresh();
        $this->assertSame($payment->id, $order->payment_id);
    }

    public function test_buy_now_store_does_not_create_order_before_payment(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Direct Checkout Product',
            'description' => 'Product for direct checkout',
            'price' => 150.00,
            'quantity' => 3,
            'type' => ['sell'],
            'category' => 'tech',
            'image' => 'default.jpg',
            'status' => 'available',
        ]);

        $response = $this->actingAs($buyer)
            ->post(route('order.store', $product), [
                'quantity' => 2,
            ]);

        $response->assertStatus(302);
        $redirectUrl = (string) $response->headers->get('Location');
        $this->assertStringContainsString(route('order.checkout.product', ['product' => $product->id], absolute: false), $redirectUrl);
        $this->assertStringContainsString('quantity=2', $redirectUrl);
        $this->assertStringContainsString('signature=', $redirectUrl);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cart_payment_initiation_creates_payment_without_creating_orders(): void
    {
        $this->configureKhalti();

        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Cart Product',
            'description' => 'Cart flow product',
            'price' => 120.00,
            'quantity' => 5,
            'type' => ['sell'],
            'category' => 'tech',
            'image' => 'default.jpg',
            'status' => 'available',
        ]);

        $buyer->cartItems()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'type' => 'buy',
        ]);

        Http::fake([
            config('khalti.initiate_url') => Http::response([
                'pidx' => 'PIDX-CART-1',
                'payment_url' => 'https://test-pay.khalti.com/?pidx=PIDX-CART-1',
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ], 200),
        ]);

        $response = $this->actingAs($buyer)->post(route('orders.placeFromCart'), [
            'payment_gateway' => 'khalti',
            'buyer_name' => 'Cart Buyer',
            'buyer_email' => 'cartbuyer@example.com',
        ]);

        $response->assertRedirect('https://test-pay.khalti.com/?pidx=PIDX-CART-1');

        $payment = Payment::latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame('cart', $payment->request_payload['source'] ?? null);
        $this->assertNotEmpty($payment->request_payload['order_items'] ?? []);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_checkout_rejects_invalid_payment_gateway(): void
    {
        [$buyer, $order] = $this->makeOrderContext();

        $response = $this->actingAs($buyer)
            ->from(route('order.checkout', $order))
            ->post(route('order.confirm', $order), [
                'payment_gateway' => 'invalid-gateway',
            ]);

        $response->assertSessionHasErrors('payment_gateway');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_order_checkout_requires_explicit_payment_gateway_selection(): void
    {
        [$buyer, $order] = $this->makeOrderContext();

        $response = $this->actingAs($buyer)
            ->from(route('order.checkout', $order))
            ->post(route('order.confirm', $order), []);

        $response->assertSessionHasErrors('payment_gateway');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancel_from_checkout_marks_pending_order_cancelled(): void
    {
        [$buyer, $order] = $this->makeOrderContext();

        $response = $this->actingAs($buyer)
            ->post(route('order.cancelCheckout', $order));

        $response->assertRedirect(route('products.index', absolute: false));

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
    }

    public function test_khalti_return_completed_marks_order_complete_and_consumes_stock(): void
    {
        $this->configureKhalti();

        [$order, $payment, $product] = $this->makePendingOrderPayment('khalti', 100.00);

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-1',
                'status' => 'Completed',
                'total_amount' => 10000,
                'transaction_id' => 'KHALTI-TXN-123',
            ], 200),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-1',
        ]));

        $response->assertRedirect(route('products.myPurchases', absolute: false));

        $payment->refresh();
        $order->refresh();
        $product->refresh();

        $this->assertSame('complete', $payment->status);
        $this->assertSame('KHALTI-TXN-123', $payment->transaction_code);
        $this->assertSame('completed', $order->status);
        $this->assertSame(1, $product->quantity);
    }

    public function test_khalti_return_pending_keeps_payment_pending_and_redirects_to_purchases(): void
    {
        $this->configureKhalti();

        [$order, $payment] = $this->makePendingOrderPayment('khalti', 100.00);

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-2',
                'status' => 'Pending',
                'total_amount' => 10000,
                'transaction_id' => null,
            ], 200),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-2',
        ]));

        $response->assertRedirect(route('products.myPurchases', absolute: false));

        $payment->refresh();
        $order->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending', $order->status);
    }

    public function test_khalti_return_user_canceled_marks_payment_failed_and_cancels_order(): void
    {
        $this->configureKhalti();

        [$order, $payment] = $this->makePendingOrderPayment('khalti', 100.00);

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-3',
                'status' => 'User canceled',
                'total_amount' => 10000,
                'transaction_id' => null,
            ], 400),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-3',
        ]));

        $response->assertRedirect(route('products.myPurchases', absolute: false));

        $payment->refresh();
        $order->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('cancelled', $order->status);
    }

    public function test_khalti_return_completed_for_swap_redirects_to_dashboard_and_completes_swap(): void
    {
        $this->configureKhalti();

        [$swapRequest, $payment, $requestedProduct, $offeredProduct] = $this->makePendingSwapPayment();

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-SWAP-1',
                'status' => 'Completed',
                'total_amount' => 10000,
                'transaction_id' => 'KHALTI-SWAP-TXN',
            ], 200),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-SWAP-1',
        ]));

        $response->assertRedirect(route('swap.mySwaps', [
            'tab' => 'pending',
            'swap_request_id' => $swapRequest->id,
        ], absolute: false));

        $payment->refresh();
        $swapRequest->refresh();
        $requestedProduct->refresh();
        $offeredProduct->refresh();

        $this->assertSame('complete', $payment->status);
        $this->assertSame('requested', $swapRequest->status);
        $this->assertSame(2, $requestedProduct->quantity);
        $this->assertSame(2, $offeredProduct->quantity);
        $this->assertDatabaseCount('swaps', 0);
    }

    public function test_khalti_return_pending_for_swap_redirects_to_dashboard_and_keeps_pending(): void
    {
        $this->configureKhalti();

        [$swapRequest, $payment] = $this->makePendingSwapPayment();

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-SWAP-2',
                'status' => 'Pending',
                'total_amount' => 10000,
                'transaction_id' => null,
            ], 200),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-SWAP-2',
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));

        $payment->refresh();
        $swapRequest->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('requested', $swapRequest->status);
    }

    public function test_khalti_return_user_canceled_for_swap_redirects_to_dashboard_and_cancels_swap_request(): void
    {
        $this->configureKhalti();

        [$swapRequest, $payment] = $this->makePendingSwapPayment();

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-SWAP-3',
                'status' => 'User canceled',
                'total_amount' => 10000,
                'transaction_id' => null,
            ], 400),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-SWAP-3',
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));

        $payment->refresh();
        $swapRequest->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('requested', $swapRequest->status);
    }

    public function test_khalti_return_completed_for_rental_redirects_to_products_and_completes_rental(): void
    {
        $this->configureKhalti();

        [$payment, $product] = $this->makePendingRentalPayment();

        Http::fake([
            config('khalti.lookup_url') => Http::response([
                'pidx' => 'PIDX-RENT-1',
                'status' => 'Completed',
                'total_amount' => 10000,
                'transaction_id' => 'KHALTI-RENT-TXN',
            ], 200),
        ]);

        $response = $this->get(route('payments.khalti.return', [
            'purchase_order_id' => $payment->transaction_uuid,
            'pidx' => 'PIDX-RENT-1',
        ]));

        $response->assertRedirect(route('products.index', absolute: false));

        $payment->refresh();
        $product->refresh();

        $this->assertSame('complete', $payment->status);
        $this->assertSame('rented', $product->status);
        $this->assertSame(0, $product->quantity);
        $this->assertDatabaseCount('rented_rentals', 1);
        $this->assertDatabaseCount('rental_requests', 0);
    }

    private function configureKhalti(): void
    {
        config()->set('khalti.secret_key', 'test_secret_key');
        config()->set('khalti.initiate_url', 'https://dev.khalti.com/api/v2/epayment/initiate/');
        config()->set('khalti.lookup_url', 'https://dev.khalti.com/api/v2/epayment/lookup/');
        config()->set('khalti.website_url', 'http://localhost');
        config()->set('khalti.return_url', 'http://localhost/payment/khalti/return');
    }

    private function makeOrderContext(): array
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Test Product',
            'description' => 'Test product description',
            'price' => 100.00,
            'quantity' => 2,
            'type' => ['sell'],
            'category' => 'tech',
            'image' => 'default.jpg',
            'status' => 'available',
        ]);

        $order = Order::create([
            'buyer_id' => $buyer->id,
            'product_id' => $product->id,
            'transaction_type' => 'buy',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
            'status' => 'pending',
            'reserved_until' => now()->addMinutes(15),
        ]);

        return [$buyer, $order];
    }

    private function makePendingOrderPayment(string $provider, float $amount): array
    {
        [$buyer, $order] = $this->makeOrderContext();
        $product = Product::findOrFail($order->product_id);

        $payment = Payment::create([
            'user_id' => $buyer->id,
            'provider' => $provider,
            'transaction_uuid' => 'txn-' . uniqid(),
            'product_code' => 'KHALTI',
            'amount' => $amount,
            'tax_amount' => 0,
            'service_charge' => 0,
            'delivery_charge' => 0,
            'total_amount' => $amount,
            'status' => 'pending',
            'request_payload' => [
                'source' => 'order',
                'orders' => [$order->id],
                'gateway' => $provider,
                'khalti' => [
                    'pidx' => 'PIDX-INIT',
                ],
            ],
        ]);

        $order->payment_id = $payment->id;
        $order->save();

        return [$order, $payment, $product];
    }

    private function makePendingSwapPayment(): array
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();

        $requestedProduct = Product::create([
            'user_id' => $owner->id,
            'title' => 'Requested Product',
            'description' => 'Requested item',
            'price' => 200.00,
            'quantity' => 2,
            'type' => ['swap'],
            'category' => 'tech',
            'image' => 'default.jpg',
            'status' => 'available',
        ]);

        $offeredProduct = Product::create([
            'user_id' => $requester->id,
            'title' => 'Offered Product',
            'description' => 'Offered item',
            'price' => 120.00,
            'quantity' => 2,
            'type' => ['swap'],
            'category' => 'tech',
            'image' => 'default.jpg',
            'status' => 'available',
        ]);

        $swapRequest = SwapRequest::create([
            'product_id' => $requestedProduct->id,
            'offered_product_id' => $offeredProduct->id,
            'owner_id' => $owner->id,
            'requester_id' => $requester->id,
            'offered_amount' => 100.00,
            'message' => 'Swap offer',
            'status' => 'requested',
            'reserved_until' => now()->addMinutes(15),
        ]);

        $payment = Payment::create([
            'user_id' => $requester->id,
            'provider' => 'khalti',
            'transaction_uuid' => 'swap-txn-' . uniqid(),
            'product_code' => 'KHALTI',
            'amount' => 100.00,
            'tax_amount' => 0,
            'service_charge' => 0,
            'delivery_charge' => 0,
            'total_amount' => 100.00,
            'status' => 'pending',
            'request_payload' => [
                'source' => 'swap',
                'gateway' => 'khalti',
                'swap_request_id' => $swapRequest->id,
                'product_id' => $requestedProduct->id,
                'offered_product_id' => $offeredProduct->id,
                'khalti' => [
                    'pidx' => 'PIDX-SWAP-INIT',
                ],
            ],
        ]);

        return [$swapRequest, $payment, $requestedProduct, $offeredProduct];
    }

    private function makePendingRentalPayment(): array
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();

        $product = Product::create([
            'user_id' => $owner->id,
            'title' => 'Rental Product',
            'description' => 'Rentable product',
            'price' => 100.00,
            'quantity' => 1,
            'type' => ['rent'],
            'category' => 'tech',
            'image' => 'default.jpg',
            'status' => 'available',
        ]);

        $rental = Rental::create([
            'product_id' => $product->id,
            'owner_id' => $owner->id,
            'rent_fare' => 80.00,
            'rent_deposit' => 20.00,
            'status' => 'available',
        ]);

        $rentalRequest = RentalRequest::create([
            'rental_id' => $rental->id,
            'product_id' => $product->id,
            'owner_id' => $owner->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'duration' => 1,
            'total_amount' => 80.00,
            'rent_deposit' => 20.00,
            'stock_reserved' => false,
            'reserved_until' => now()->addMinutes(15),
            'status' => 'approved',
        ]);

        $payment = Payment::create([
            'user_id' => $renter->id,
            'provider' => 'khalti',
            'transaction_uuid' => 'rental-txn-' . uniqid(),
            'product_code' => 'KHALTI',
            'amount' => 100.00,
            'tax_amount' => 0,
            'service_charge' => 0,
            'delivery_charge' => 0,
            'total_amount' => 100.00,
            'status' => 'pending',
            'request_payload' => [
                'source' => 'rental',
                'gateway' => 'khalti',
                'rental_request_id' => $rentalRequest->id,
                'khalti' => [
                    'pidx' => 'PIDX-RENT-INIT',
                ],
            ],
        ]);

        return [$payment, $product];
    }
}
