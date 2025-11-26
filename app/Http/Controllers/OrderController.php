<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request, $productId)
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

            if ($product->status === 'sold') {
                return back()->with('error', 'This product is already sold.');
            }

            if ($product->quantity < $requestedQty) {
                return back()->with('error', 'Requested quantity exceeds available stock.');
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
            ]);

            // Adjust product quantity
            $product->quantity -= $requestedQty;

            // Only mark sold if no units remain
            if ($product->quantity <= 0) {
                $product->quantity = 0;
                $product->status = 'sold';
            } else {
                // Keep available if still stock
                if ($product->status === 'sold') {
                    $product->status = 'available';
                }
            }

            $product->save();

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
