@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10 max-w-6xl space-y-12">

    {{-- ==================== HEADER ==================== --}}
    <h2 class="font-semibold text-2xl text-gray-800 text-center mb-6">
        My Listings Dashboard
    </h2>

    {{-- ==================== SECTION 1: My Products ==================== --}}
    <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold mb-5 text-gray-800 flex items-center gap-2">
            <span class="text-blue-600"></span> My Products
        </h3>

        <table class="w-full text-sm text-left text-gray-600 border-collapse">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th class="px-6 py-3">Image</th>
                    <th class="px-6 py-3">Title</th>
                    <th class="px-6 py-3">Price</th>
                    <th class="px-6 py-3">Quantity</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products->where('status', '!=', 'sold') as $product)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <img src="{{ asset('storage/' . $product->image) }}" alt="Image"
                                 class="w-16 h-16 object-cover rounded-md shadow-sm border">
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $product->title }}</td>
                        <td class="px-6 py-4">
                            @if($product->price)
                                <span class="font-semibold text-gray-800">Rs. {{ $product->price }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $product->quantity }}</td>
                        <td class="px-6 py-4">
                            <form action="{{ route('products.updateStatus', $product->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()"
                                        class="border-gray-300 rounded-md text-sm p-1.5 focus:ring-2 focus:ring-blue-400">
                                    <option value="available" {{ $product->status == 'available' ? 'selected' : '' }}>Available</option>
                                    <option value="sold" {{ $product->status == 'sold' ? 'selected' : '' }}>Sold</option>
                                    <option value="rented" {{ $product->status == 'rented' ? 'selected' : '' }}>Rented</option>
                                    <option value="swapped" {{ $product->status == 'swapped' ? 'selected' : '' }}>Swapped</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4 flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('products.edit', $product->id) }}"
                               class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-md font-medium transition">
                                Edit
                            </a>
                            <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded-md font-medium transition">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-6 text-gray-500">No active products listed yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ==================== SECTION 2: Pending Rental Requests ==================== --}}
    <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold mb-5 text-gray-800"> Pending Rental Requests</h3>

        <x-section-table :data="$pendingRequests" empty="No pending rental requests.">
            <x-slot name="header">
                <tr>
                    <th>Product</th>
                    <th>Renter</th>
                    <th>Duration</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </x-slot>

            @foreach ($pendingRequests as $request)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $request->product->title ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $request->renter->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $request->duration }} days</td>
                    <td class="px-6 py-4">Rs. {{ $request->total_amount }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-yellow-100 text-yellow-700 px-2 py-1 text-xs font-semibold rounded">
                            {{ ucfirst($request->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('rental.review', $request->id) }}"
                           class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded">
                            Review
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-section-table>
    </div>

    {{-- ==================== SECTION 3: Active Rentals ==================== --}}
    <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold mb-5 text-gray-800"> Active Rentals</h3>

        <x-section-table :data="$activeRentals" empty="No active rentals.">
            <x-slot name="header">
                <tr>
                    <th>Product</th>
                    <th>Renter</th>
                    <th>From - To</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </x-slot>

            @foreach ($activeRentals as $rental)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $rental->product->title ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $rental->renter->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $rental->start_date }} → {{ $rental->end_date }}</td>
                    <td class="px-6 py-4">Rs. {{ $rental->total_amount }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-green-100 text-green-700 px-2 py-1 text-xs font-semibold rounded">
                            {{ ucfirst($rental->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="#" class="bg-gray-700 hover:bg-gray-800 text-white text-xs px-3 py-1 rounded">
                            View
                        </a>

                        @if($rental->status === 'active')
                            <form action="{{ route('rental.return', $rental->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button
                                    class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded ml-1">
                                    Mark Returned
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-section-table>
    </div>

    {{-- ==================== SECTION 4: Swap Requests ==================== --}}
    <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold mb-5 text-gray-800"> Swap Requests</h3>

        <x-section-table :data="$swapRequests" empty="No pending swap requests.">
            <x-slot name="header">
                <tr>
                    <th>Requested Product</th>
                    <th>Offered Product</th>
                    <th>Requester</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </x-slot>

            @foreach ($swapRequests as $swap)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $swap->requestedProduct->title ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $swap->offeredProduct->title ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $swap->requester->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-blue-100 text-blue-700 px-2 py-1 text-xs font-semibold rounded">
                            {{ ucfirst($swap->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 flex gap-2">
                        <a href="{{ route('swap.accept', $swap->id) }}"
                           class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">Accept</a>
                        <a href="{{ route('swap.reject', $swap->id) }}"
                           class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded">Reject</a>
                    </td>
                </tr>
            @endforeach
        </x-section-table>
    </div>

    {{-- ==================== SECTION 5: Active Swaps ==================== --}}
    <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold mb-5 text-gray-800"> Swapped Products</h3>

        <x-section-table :data="$activeSwaps" empty="No active swaps yet.">
            <x-slot name="header">
                <tr>
                    <th>My Product</th>
                    <th>Swapped With</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </x-slot>

            @foreach ($activeSwaps as $swap)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $swap->myProduct->title ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $swap->otherProduct->title ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $swap->created_at->format('Y-m-d') }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-green-100 text-green-700 px-2 py-1 text-xs font-semibold rounded">
                            {{ ucfirst($swap->status) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-section-table>
    </div>

    {{-- ==================== SECTION 6: Sales Summary ==================== --}}
    <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold mb-5 text-gray-800"> Sales Summary </h3>

        <table class="w-full text-sm text-left text-gray-600 border-collapse">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th class="px-6 py-3">Product</th>
                    <th class="px-6 py-3">Unit Price</th>
                    <th class="px-6 py-3">Units Sold</th>
                    <th class="px-6 py-3">Remaining Qty</th>
                    <th class="px-6 py-3">Total Revenue</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Last Sale</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($soldProducts as $sold)
                    @php
                        $unitsSold = $sold->orders->sum(fn($o) => $o->quantity ?? 1);
                        $totalRevenue = ($sold->price ?? 0) * $unitsSold;
                        $lastSale = $sold->orders->max('created_at');
                    @endphp
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $sold->title }}</td>
                        <td class="px-6 py-4">Rs. {{ number_format($sold->price,2) }}</td>
                        <td class="px-6 py-4">{{ $unitsSold }}</td>
                        <td class="px-6 py-4">{{ $sold->quantity }}</td>
                        <td class="px-6 py-4">Rs. {{ number_format($totalRevenue,2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs
                                @if($sold->status==='sold') bg-red-100 text-red-700
                                @elseif($sold->status==='available') bg-green-100 text-green-700
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($sold->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $lastSale ? $lastSale->format('Y-m-d') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-6 text-gray-500">No sales yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
