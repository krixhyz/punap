@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-3xl font-extrabold text-slate-900">Checkout</h1>

    @php
        $unit = $order->unit_price ?? ($order->product?->price ?? 0);
        $qty  = $order->quantity ?? 1;
        $total = $unit * $qty;
    @endphp

    <div class="surface-card p-6 space-y-5">
        <div class="flex items-center gap-4">
            @if($order->product?->image)
                <img src="{{ asset('storage/'.$order->product->image) }}" class="w-20 h-20 object-cover rounded" alt="">
            @endif
            <div>
                <h3 class="text-lg font-semibold text-slate-900">{{ $order->product?->title ?? 'Product' }}</h3>
                <p class="text-xs text-slate-600">
                    {{ \Illuminate\Support\Str::limit($order->product?->description ?? '', 100) }}
                </p>
            </div>
        </div>

        <div class="text-sm space-y-1 text-slate-700">
            <p><strong>Unit Price:</strong> Rs. {{ number_format($unit,2) }}</p>
            <p><strong>Quantity:</strong> {{ $qty }}</p>
            <p><strong>Total:</strong> <span class="font-semibold text-emerald-700">Rs. {{ number_format($total,2) }}</span></p>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        </div>

        <form method="POST" action="{{ route('order.confirm', $order->id) }}" class="space-y-3">
            @csrf
            <button type="submit" class="btn-pill btn-pill-dark w-full justify-center py-3">
                Pay with eSewa
            </button>
        </form>

        <a href="{{ route('products.index') }}"
           class="inline-block text-xs text-slate-500 hover:text-slate-700">
            ← Continue Browsing
        </a>
    </div>
</div>
@endsection
