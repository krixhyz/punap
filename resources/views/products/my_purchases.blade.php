@extends('layouts.app')

@section('content')
<div class="py-10 max-w-6xl mx-auto px-6 space-y-10">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            My Purchases & Rentals
        </h2>
        <a href="{{ route('products.myListings') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    {{-- ==================== Rented Items ==================== --}}
    <div class="bg-white shadow-md rounded-2xl overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Rented Items</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Product</th>
                        <th class="px-6 py-3">Owner</th>
                        <th class="px-6 py-3">Duration</th>
                        <th class="px-6 py-3">Total Paid</th>
                        <th class="px-6 py-3">Rental Dates</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rentedRentals as $rental)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $rental->product?->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $rental->owner?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $rental->duration }} days</td>
                            <td class="px-6 py-4 font-semibold text-indigo-600">
                                Rs. {{ $rental->total_amount + $rental->rent_deposit }}
                            </td>
                            <td class="px-6 py-4">{{ $rental->start_date }} → {{ $rental->end_date }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-gray-400">No rentals yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== Purchased Products ==================== --}}
    <div class="bg-white shadow-md rounded-2xl overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Purchased Products</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Product</th>
                        <th class="px-6 py-3">Unit Price</th>
                        <th class="px-6 py-3">Quantity</th>
                        <th class="px-6 py-3">Total Paid</th>
                        <th class="px-6 py-3">Date Purchased</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        @php
                            $qty = $order->quantity ?? 1;
                            $unit = $order->product?->price ?? 0;
                            $total = $qty * $unit;
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $order->product?->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4 font-semibold text-indigo-600">Rs. {{ number_format($unit,2) }}</td>
                            <td class="px-6 py-4">{{ $qty }}</td>
                            <td class="px-6 py-4 font-semibold text-green-600">Rs. {{ number_format($total,2) }}</td>
                            <td class="px-6 py-4">{{ $order->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-gray-400">No purchases yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== Swapped Products ==================== --}}
    <div class="bg-white shadow-md rounded-2xl overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Swapped Products</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Your Product</th>
                        <th class="px-6 py-3">Swapped With</th>
                        <th class="px-6 py-3">Other User</th>
                        <th class="px-6 py-3">Extra Cash</th>
                        <th class="px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($swaps as $swap)
                        @php
                            $isOwnerA = $swap->owner_a_id === auth()->id();
                            $yourProduct = $isOwnerA ? $swap->requestedProduct : $swap->offeredProduct;
                            $otherProduct = $isOwnerA ? $swap->offeredProduct : $swap->requestedProduct;
                            $otherUser = $isOwnerA ? $swap->ownerB : $swap->ownerA;
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $yourProduct->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $otherProduct->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $otherUser?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 font-semibold text-indigo-600">
                                {{ $swap->offered_amount > 0 ? '+Rs. '.$swap->offered_amount : 'None' }}
                            </td>
                            <td class="px-6 py-4">{{ $swap->updated_at?->format('Y-m-d') ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-gray-400">No swaps yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
