<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request, $productId)
{
    $product = Product::findOrFail($productId);

    Order::create([
        'buyer_id' => Auth::user()->id,
        'product_id' => $product->id,
        'transaction_type' => $request->type, // 'buy', 'rent', or 'swap'
        'rent_duration' => $request->rent_duration,
    ]);

    return back()->with('success', 'Request sent successfully!');
}

}
