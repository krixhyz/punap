<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
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

    public function store(Request $request, $productId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($productId);

        if ($product->user_id === Auth::id()) {
            return back()->with('error', 'Cannot add your own product to cart.');
        }

        if ($product->quantity < $validated['quantity']) {
            return back()->with('error', 'Insufficient stock.');
        }

        // Check if already in cart
        $existing = Auth::user()->cartItems()->where('product_id', $productId)->first();
        if ($existing) {
            $newQty = $existing->quantity + $validated['quantity'];
            if ($newQty > $product->quantity) {
                return back()->with('error', 'Exceeds available stock.');
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

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Auth::user()->cartItems()->findOrFail($id);

        if ($cartItem->product->quantity < $validated['quantity']) {
            return back()->with('error', 'Insufficient stock.');
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

    public function placeFromCart(Request $request)
    {
        $cartItems = Auth::user()->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Cart is empty.');
        }

        DB::transaction(function () use ($cartItems) {
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->quantity < $item->quantity) {
                    throw new \Exception('Insufficient stock for ' . $product->title);
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

                $product->quantity -= $item->quantity;
                if ($product->quantity <= 0) {
                    $product->quantity = 0;
                    $product->status = 'sold';
                }
                $product->save();

                $item->delete();
            }
        });

        return redirect()->route('products.myPurchases')->with('success', 'Orders placed successfully.');
    }
}
