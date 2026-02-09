@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-8 px-6">
    <h2 class="text-2xl font-semibold mb-6">Checkout Summary</h2>

    @php $total = 0; @endphp
    <div class="bg-white shadow rounded-lg p-6 space-y-3">
        @foreach($cartItems as $item)
            @php
                $unit = $item->product->price ?? 0;
                $qty = $item->quantity ?? 1;
                $line = $unit * $qty;
                $total += $line;
            @endphp
            <div class="flex justify-between text-sm">
                <span>{{ $item->product->title }} (x{{ $qty }})</span>
                <span>Rs. {{ number_format($line,2) }}</span>
            </div>
        @endforeach
        <hr>
        <div class="flex justify-between font-bold">
            <span>Total</span>
            <span>Rs. {{ number_format($total,2) }}</span>
        </div>

        <form action="{{ route('orders.placeFromCart') }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded mt-4">
                Confirm & Place Orders
            </button>
        </form>
    </div>
</div>
@endsection
