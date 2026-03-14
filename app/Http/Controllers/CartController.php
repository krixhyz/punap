<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Services\InventoryReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = Auth::user()->cartItems()->with('product')->get();
        return view('cart.index', compact('cartItems'));
    }

    public function store(Request $request, $productId, InventoryReservationService $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($productId);

        if ($product->user_id === Auth::id()) {
            return back()->with('error', 'Cannot add your own product to cart.');
        }

        try {
            $inventory->ensurePurchasableQuantity($product, (int) $validated['quantity'], now());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Check if already in cart
        $existing = Auth::user()->cartItems()->where('product_id', $productId)->first();
        if ($existing) {
            $newQty = $existing->quantity + $validated['quantity'];
            try {
                $inventory->ensurePurchasableQuantity($product, (int) $newQty, now());
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
            $existing->quantity = $newQty;
            $existing->save();
        } else {
            Auth::user()->cartItems()->create([
                'product_id' => $productId,
                'quantity' => $validated['quantity'],
                'type' => 'buy',
            ]);
        }

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, $id, InventoryReservationService $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Auth::user()->cartItems()->findOrFail($id);

        $product = $cartItem->product;
        try {
            $inventory->ensurePurchasableQuantity($product, (int) $validated['quantity'], now());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $cartItem->quantity = $validated['quantity'];
        $cartItem->save();

        return back()->with('success', 'Cart updated.');
    }

    public function destroy($id)
    {
        Auth::user()->cartItems()->findOrFail($id)->delete();
        return back()->with('success', 'Item removed from cart.');
    }

    public function checkout()
    {
        $cartItems = Auth::user()->cartItems()->with('product')->get();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Cart is empty.');
        }
        return view('cart.checkout', compact('cartItems'));
    }

    public function placeFromCart(Request $request, InventoryReservationService $inventory)
    {
        $cartItems = Auth::user()->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Cart is empty.');
        }

        try {
            DB::transaction(function () use ($cartItems, $inventory) {
                foreach ($cartItems as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);

                    if (!$product) {
                        throw new \RuntimeException('Product no longer exists.');
                    }

                    try {
                        $inventory->ensurePurchasableQuantity($product, (int) $item->quantity, now());
                    } catch (\RuntimeException $e) {
                        throw new \RuntimeException(($product->title ?? 'Product') . ': ' . $e->getMessage());
                    }

                    $unitPrice = $product->price ?? 0;
                    $totalPrice = $unitPrice * $item->quantity;

                    Order::create([
                        'buyer_id' => Auth::id(),
                        'product_id' => $product->id,
                        'transaction_type' => 'buy',
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'status' => 'completed',
                    ]);

                    $inventory->consumeProductQuantity($product, (int) $item->quantity, 'sold');

                    $item->delete();
                }
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.checkout')->with('error', $e->getMessage());
        }

        return redirect()->route('products.myPurchases')->with('success', 'Orders placed successfully.');
    }
}
