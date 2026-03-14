@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-extrabold text-slate-900">Shopping Cart</h1>
        <a href="{{ route('products.index') }}" class="btn-pill btn-pill-soft">Continue Browsing</a>
    </div>

    @if($cartItems->isEmpty())
        <div class="surface-card p-12 text-center">
            <p class="text-lg font-semibold text-slate-700">Your cart is empty.</p>
        </div>
    @else
        <div class="surface-card p-5 space-y-4">
            @php $grandTotal = 0; @endphp
            @foreach($cartItems as $item)
                @php
                    $unit = $item->product->price ?? 0;
                    $qty = $item->quantity ?? 1;
                    $lineTotal = $unit * $qty;
                    $grandTotal += $lineTotal;
                @endphp
                <div class="flex flex-wrap items-center gap-4 border-b border-slate-200 pb-4">
                    @if($item->product->image)
                        <img src="{{ asset('storage/'.$item->product->image) }}" class="h-16 w-16 rounded object-cover" alt="">
                    @endif
                    <div class="flex-1">
                        <h3 class="font-medium text-slate-900">{{ $item->product->title }}</h3>
                        <p class="text-sm text-slate-600">Unit: Rs. {{ number_format($unit,2) }}</p>
                    </div>

                    <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="quantity" value="{{ $qty }}" min="1" max="{{ $item->product->quantity }}"
                               class="w-16 rounded border border-slate-300 px-2 py-1 text-sm">
                        <button type="submit" class="rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700">Update</button>
                    </form>

                    <div class="text-right">
                        <p class="font-semibold text-slate-900">Rs. {{ number_format($lineTotal,2) }}</p>
                    </div>

                    <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                    </form>
                </div>
            @endforeach

            <div class="text-right">
                <p class="text-lg font-bold text-slate-900">Grand Total: Rs. {{ number_format($grandTotal,2) }}</p>
            </div>

            <form action="{{ route('orders.placeFromCart') }}" method="POST" class="text-right">
                @csrf
                <button type="submit" class="btn-pill btn-pill-dark">
                    Proceed to Checkout
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
