@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto py-10 text-gray-200">
        <h2 class="text-xl font-semibold mb-6">Swap Request Details</h2>

        <div class="bg-gray-800 p-6 rounded-xl shadow">
            <p><strong>Requester:</strong> {{ $swapRequest->requester->name }}</p>
            <p><strong>Product:</strong> {{ $swapRequest->product->title }}</p>
            @if($swapRequest->offeredProduct)
                <p><strong>Offered Product:</strong> {{ $swapRequest->offeredProduct->title }}</p>
            @endif
            @if($swapRequest->offered_amount)
                <p><strong>Offered Amount:</strong> ${{ $swapRequest->offered_amount }}</p>
            @endif
            @if($swapRequest->message)
                <p><strong>Message:</strong> "{{ $swapRequest->message }}"</p>
            @endif
        </div>
    </div>
@endsection
