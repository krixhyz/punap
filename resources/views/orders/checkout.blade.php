@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-8 px-6">
    <h2 class="text-2xl font-semibold mb-6">Checkout</h2>

    @php
        $unit = $order->unit_price ?? ($order->product?->price ?? 0);
        $qty  = $order->quantity ?? 1;
        $total = $unit * $qty;
    @endphp

    <div class="bg-white shadow rounded-lg p-6 space-y-5">
        <div class="flex items-center gap-4">
            @if($order->product?->image)
                <img src="{{ asset('storage/'.$order->product->image) }}" class="w-20 h-20 object-cover rounded" alt="">
            @endif
            <div>
                <h3 class="text-lg font-medium">{{ $order->product?->title ?? 'Product' }}</h3>
                <p class="text-xs text-gray-600">
                    {{ \Illuminate\Support\Str::limit($order->product?->description ?? '', 100) }}
                </p>
            </div>
        </div>

        <div class="text-sm space-y-1">
            <p><strong>Unit Price:</strong> Rs. {{ number_format($unit,2) }}</p>
            <p><strong>Quantity:</strong> {{ $qty }}</p>
            <p><strong>Total:</strong> <span class="text-green-600 font-semibold">Rs. {{ number_format($total,2) }}</span></p>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        </div>

        <form method="POST" action="{{ route('order.confirm', $order->id) }}" class="space-y-3">
            @csrf
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded transition">
                Confirm Purchase
            </button>
        </form>

        <a href="{{ route('products.index') }}"
           class="text-xs text-gray-500 hover:text-gray-700 inline-block">
            ← Continue Browsing
        </a>
    </div>
</div>
@endsection
