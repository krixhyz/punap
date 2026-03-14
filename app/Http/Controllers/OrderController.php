<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryReservationService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request, $productId, InventoryReservationService $inventory)
    {
        // Validate quantity from form (defaults to 1)
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $requestedQty = (int) $validated['quantity'];

        return DB::transaction(function () use ($productId, $requestedQty) {
            // Lock row for update to avoid race conditions
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            if ($product->user_id === Auth::id()) {
                return back()->with('error', 'You cannot buy your own product.');
            }

            try {
                $inventory->ensurePurchasableQuantity($product, $requestedQty, now());
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            $unitPrice = $product->price ?? 0;
            $totalPrice = $unitPrice * $requestedQty;

            // Create order (assumes orders table has a quantity column; add one if missing)
            $order = Order::create([
                'buyer_id' => Auth::id(),
                'product_id' => $product->id,
                'transaction_type' => 'buy',
                'quantity' => $requestedQty,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'reserved_until' => now()->addMinutes(config('esewa.reservation_minutes')),
            ]);

            return redirect()
                ->route('order.checkout', $order->id)
                ->with('success', 'Order placed successfully! Proceed to checkout.');
        });
    }

    public function checkout($orderId)
    {
        $order = Order::with('product')->findOrFail($orderId);

        if ($order->buyer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('orders.checkout', compact('order'));
    }

    public function cancel(Order $order)
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending orders can be cancelled.');
        }

        $order->status = 'cancelled';
        $order->save();

        return back()->with('success', 'Order cancelled successfully.');
    }

    public function confirm(Request $request, $orderId)
    {
        $order = Order::with('product')->where('id', $orderId)->where('buyer_id', Auth::id())->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->route('products.myPurchases')->with('info', 'Order already processed.');
        }

        // Recalculate in case product price changed (keep original if stored)
        $unit = $order->unit_price ?? ($order->product->price ?? 0);
        $qty  = $order->quantity ?? 1;
        $total = $unit * $qty;

        // Persist if columns exist
        $order->unit_price = $unit;
        $order->total_price = $total;
        $order->status = 'completed';
        $order->save();

        return redirect()->route('products.myPurchases')
            ->with('success', 'Purchase completed successfully.');
    }
}
