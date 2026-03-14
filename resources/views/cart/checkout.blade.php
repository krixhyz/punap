@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-3xl font-extrabold text-slate-900">Checkout Summary</h1>

    @php $total = 0; @endphp
    <div class="surface-card p-6 space-y-3">
        @foreach($cartItems as $item)
            @php
                $unit = $item->product->price ?? 0;
                $qty = $item->quantity ?? 1;
                $line = $unit * $qty;
                $total += $line;
            @endphp
            <div class="flex justify-between text-sm text-slate-700">
                <span>{{ $item->product->title }} (x{{ $qty }})</span>
                <span>Rs. {{ number_format($line,2) }}</span>
            </div>
        @endforeach
        <hr class="border-slate-200">
        <div class="flex justify-between font-bold text-slate-900">
            <span>Total</span>
            <span>Rs. {{ number_format($total,2) }}</span>
        </div>

        <form action="{{ route('orders.placeFromCart') }}" method="POST">
            @csrf
            <button type="submit" class="btn-pill btn-pill-dark mt-4 w-full justify-center py-3">
                Pay with eSewa
            </button>
        </form>
    </div>
</div>
@endsection
