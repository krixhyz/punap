<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::where('user_id', Auth::id())->with('product')->get();
        return view('cart.index', compact('cartItems'));
    }

    public function store(Request $request, $productId)
    {
        $request->validate([
            'type' => 'nullable|string',
            'rent_duration' => 'nullable|integer|min:1',
        ]);
    
        $product = Product::findOrFail($productId);
    
        CartItem::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $product->id,
            ],
            [
                'type' => $request->type ?? 'buy',
                'quantity' => 1,
                'rent_duration' => $request->rent_duration,
            ]
        );
    
        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function destroy($id)
    {
        $item = CartItem::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Item removed from cart.');
    }
}
