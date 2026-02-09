@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-6">
    <h2 class="text-2xl font-semibold mb-6">Shopping Cart</h2>

    @if($cartItems->isEmpty())
        <p class="text-gray-500">Your cart is empty.</p>
    @else
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            @php $grandTotal = 0; @endphp
            @foreach($cartItems as $item)
                @php
                    $unit = $item->product->price ?? 0;
                    $qty = $item->quantity ?? 1;
                    $lineTotal = $unit * $qty;
                    $grandTotal += $lineTotal;
                @endphp
                <div class="flex items-center gap-4 border-b pb-4">
                    @if($item->product->image)
                        <img src="{{ asset('storage/'.$item->product->image) }}" class="w-16 h-16 object-cover rounded" alt="">
                    @endif
                    <div class="flex-1">
                        <h3 class="font-medium">{{ $item->product->title }}</h3>
                        <p class="text-sm text-gray-600">Unit: Rs. {{ number_format($unit,2) }}</p>
                    </div>

                    <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="quantity" value="{{ $qty }}" min="1" max="{{ $item->product->quantity }}"
                               class="w-16 border rounded text-sm px-2 py-1">
                        <button type="submit" class="bg-blue-600 text-white text-xs px-2 py-1 rounded">Update</button>
                    </form>

                    <div class="text-right">
                        <p class="font-semibold">Rs. {{ number_format($lineTotal,2) }}</p>
                    </div>

                    <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                    </form>
                </div>
            @endforeach

            <div class="text-right">
                <p class="text-lg font-bold">Grand Total: Rs. {{ number_format($grandTotal,2) }}</p>
            </div>

            <form action="{{ route('orders.placeFromCart') }}" method="POST" class="text-right">
                @csrf
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    Proceed to Checkout
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
