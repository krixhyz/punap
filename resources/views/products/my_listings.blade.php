@extends('layouts.dashboard')

@section('content')
@php
    $activeProducts = $products->where('status', '!=', 'sold');
    $listedUnits = $products->sum('quantity');
    $soldUnits = $soldProducts->sum(fn($p) => $p->orders->sum(fn($o) => $o->quantity ?? 1));
    $salesRevenue = $soldProducts->sum(fn($p) => $p->orders->sum(fn($o) => ($o->unit_price ?? $p->price ?? 0) * ($o->quantity ?? 1)));
    $pendingActionCount = $pendingRequests->count() + $swapRequests->count();
@endphp

<!-- Header Section -->
<section class="px-0 md:px-8 py-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-space text-[12px] font-bold uppercase tracking-widest text-[#888] mb-2">Seller Workspace</p>
            <h1 class="font-space font-bold text-4xl text-[#1a1c1c] mb-2">My Listings</h1>
            <p class="font-manrope text-base text-[#444746]">Manage your inventory, track requests, and monitor sales.</p>
        </div>
        <a href="{{ route('products.create') }}" class="bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-6 py-3 font-space font-bold text-sm uppercase tracking-wider hover:brightness-110 transition-all rounded-lg h-fit">Add Listing</a>
    </div>
</section>

<!-- Quick Stats -->
<section class="px-0 md:px-8 py-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] p-6">
        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] mb-1">Active Listings</p>
        <p class="font-space font-bold text-3xl text-[#006a38]">{{ $activeProducts->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] p-6">
        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] mb-1">Total Units</p>
        <p class="font-space font-bold text-3xl text-[#006a38]">{{ $listedUnits }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] p-6">
        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] mb-1">Units Sold</p>
        <p class="font-space font-bold text-3xl text-[#006a38]">{{ $soldUnits }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] p-6">
        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] mb-1">Sales Revenue</p>
        <p class="font-space font-bold text-3xl text-[#006a38]">Rs. {{ number_format($salesRevenue, 0) }}</p>
    </div>
</section>

<!-- Pending Requests Cards -->
@if($pendingActionCount > 0)
<section class="px-0 md:px-8 py-6">
    <h2 class="font-space text-lg font-bold text-[#1a1c1c] mb-4">Pending Requests</h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rental Requests -->
        @if($pendingRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)]">
                <div class="px-6 py-4 border-b border-[rgba(189,202,189,0.1)] flex items-center justify-between">
                    <h3 class="font-space font-bold text-sm uppercase tracking-widest text-[#1a1c1c]">Rental Requests</h3>
                    <span class="bg-[#ffd580] text-[#664d03] text-[10px] font-space font-bold px-3 py-1 rounded">{{ $pendingRequests->count() }}</span>
                </div>
                <div class="divide-y divide-[rgba(189,202,189,0.1)]">
                    @foreach($pendingRequests->take(3) as $request)
                        <a href="{{ route('rental.review', $request->id) }}" class="block px-6 py-4 hover:bg-[#f9f9f9] transition-colors">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <h4 class="font-space font-bold text-sm text-[#1a1c1c] flex-1">{{ $request->product->title ?? 'N/A' }}</h4>
                                <span class="text-[10px] font-space font-bold px-2 py-1 bg-[#f0f8f5] text-[#006a38] rounded flex-shrink-0">{{ $request->duration }}d</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-[#888]">
                                <p>From: {{ $request->renter->name ?? 'N/A' }}</p>
                                <p class="font-space font-bold text-[#006a38]">Rs. {{ number_format($request->total_amount, 0) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if($pendingRequests->count() > 3)
                    <div class="px-6 py-3 bg-[#f9f9f9] text-center">
                        <a href="#rental-requests" class="text-[12px] text-[#006a38] font-space font-bold hover:underline">View all {{ $pendingRequests->count() }} requests</a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Swap Requests -->
        @if($swapRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)]">
                <div class="px-6 py-4 border-b border-[rgba(189,202,189,0.1)] flex items-center justify-between">
                    <h3 class="font-space font-bold text-sm uppercase tracking-widest text-[#1a1c1c]">Swap Requests</h3>
                    <span class="bg-[#ffd580] text-[#664d03] text-[10px] font-space font-bold px-3 py-1 rounded">{{ $swapRequests->count() }}</span>
                </div>
                <div class="divide-y divide-[rgba(189,202,189,0.1)]">
                    @foreach($swapRequests->take(3) as $swap)
                        <div class="px-6 py-4 hover:bg-[#f9f9f9] transition-colors">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-space font-bold text-sm text-[#1a1c1c] mb-1">{{ $swap->requestedProduct->title ?? 'N/A' }}</h4>
                                    <p class="text-xs text-[#888]">↔ {{ $swap->offeredProduct->title ?? 'N/A' }}</p>
                                </div>
                                <span class="text-[10px] font-space font-bold px-2 py-1 bg-[#f0f8f5] text-[#006a38] rounded flex-shrink-0">New</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <p class="text-xs text-[#888]">From: {{ $swap->requester?->name ?? 'N/A' }}</p>
                                <div class="flex gap-2">
                                    <form action="{{ route('swap.request.accept', $swap->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full bg-[#006a38] text-white px-3 py-2 font-space text-[10px] font-bold uppercase text-center rounded hover:bg-[#004a29] transition-all">Accept</button>
                                    </form>
                                    <form action="{{ route('swap.request.reject', $swap->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full bg-[#f9f9f9] border border-[#ba1a1a] text-[#ba1a1a] px-3 py-2 font-space text-[10px] font-bold uppercase text-center rounded hover:bg-[rgba(186,26,26,0.06)] transition-all">Reject</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endif

<!-- My Listings Grid -->
<section class="px-0 md:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="font-space text-lg font-bold text-[#1a1c1c]">Active Listings</h2>
        <a href="#" class="text-[12px] text-[#006a38] font-space font-bold hover:underline">Filter</a>
    </div>

    @if($activeProducts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($activeProducts as $product)
                <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] overflow-hidden hover:shadow-[0_8px_12px_rgba(0,0,0,0.1)] transition-all">
                    <!-- Image -->
                    <div class="aspect-square bg-[#e2e2e2] overflow-hidden relative group">
                        @php
                            $firstImage = null;
                            if (is_array($product->images) && !empty($product->images)) {
                                $firstImage = $product->images[0];
                            } elseif (is_string($product->images) && $product->images !== '') {
                                $firstImage = $product->images;
                            }

                            if (is_array($firstImage)) {
                                $firstImage = $firstImage['path'] ?? null;
                            }

                            $displayImage = $firstImage ?: $product->image;
                        @endphp
                        @if($displayImage)
                            <img src="{{ asset('storage/' . $displayImage) }}" alt="{{ $product->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-[#888]">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute top-0 right-0 m-3">
                            <span class="bg-[#006a38] text-white text-[10px] font-space font-bold px-3 py-1 rounded">{{ $product->quantity }} in stock</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <a href="{{ route('products.show', $product->id) }}" class="block">
                            <h3 class="font-space font-bold text-sm text-[#1a1c1c] mb-2 line-clamp-2 hover:text-[#006a38] transition-colors">{{ $product->title }}</h3>
                        </a>
                        
                        <p class="text-sm text-[#888] mb-4 line-clamp-2">{{ Str::limit($product->description, 60) }}</p>

                        <div class="mb-4 pb-4 border-b border-[rgba(189,202,189,0.1)]">
                            <p class="font-space font-bold text-2xl text-[#006a38] mb-2">Rs. {{ number_format($product->price, 0) }}</p>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-space font-bold {{ $product->approval_status === 'APPROVED' ? 'bg-[#d4edda] text-[#155724]' : 'bg-[#ffd580] text-[#664d03]' }} px-2 py-1 rounded">
                                    {{ ucfirst($product->approval_status) }}
                                </span>
                                <span class="text-[10px] font-space font-bold bg-[#f0f8f5] text-[#006a38] px-2 py-1 rounded">{{ ucfirst($product->status) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="{{ route('products.edit', $product->id) }}" class="flex-1 bg-[#006a38] text-white px-4 py-2 font-space text-[10px] font-bold uppercase text-center rounded hover:bg-[#004a29] transition-all">Edit</a>
                            @if(($canDeleteByProduct[$product->id] ?? true) === true)
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete this listing?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full bg-[#f9f9f9] border border-[#ba1a1a] text-[#ba1a1a] px-3 py-2 font-space text-[10px] font-bold uppercase rounded hover:bg-[rgba(186,26,26,0.06)] transition-all">Delete</button>
                                </form>
                            @else
                                <button type="button" class="flex-1 w-full bg-[#f3f3f3] border border-[#b7b7b7] text-[#888] px-3 py-2 font-space text-[10px] font-bold uppercase rounded cursor-not-allowed" title="{{ $deleteBlockersByProduct[$product->id] ?? 'This listing has active obligations and cannot be deleted.' }}" disabled>
                                    Locked
                                </button>
                            @endif
                        </div>
                        @if(($canDeleteByProduct[$product->id] ?? true) === false)
                            <p class="mt-3 text-[11px] text-[#ba1a1a] leading-snug">{{ $deleteBlockersByProduct[$product->id] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] p-12 text-center">
            <svg class="w-16 h-16 text-[#ccc] mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8-4m0 0l8 4m0 6l-8 4-8-4m0 0l8-4m0 0l8 4m0 6l-8 4-8-4"></path>
            </svg>
            <p class="font-manrope text-base text-[#888] mb-4">No active listings yet</p>
            <a href="{{ route('products.create') }}" class="inline-block bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-6 py-3 font-space font-bold text-sm uppercase tracking-wider hover:brightness-110 transition-all rounded-lg">Create Your First Listing</a>
        </div>
    @endif
</section>

<!-- Sold Products Section -->
@if($soldProducts->count() > 0)
<section class="px-0 md:px-8 py-6">
    <h2 class="font-space text-lg font-bold text-[#1a1c1c] mb-4">Recently Sold</h2>
    
    <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)]">
        <div class="divide-y divide-[rgba(189,202,189,0.1)]">
            @foreach($soldProducts->take(5) as $product)
                @php
                    $units = $product->orders->sum(fn($o) => $o->quantity ?? 1);
                    $revenue = $product->orders->sum(fn($o) => ($o->unit_price ?? $product->price ?? 0) * ($o->quantity ?? 1));
                    $firstImage = null;
                    if (is_array($product->images) && !empty($product->images)) {
                        $firstImage = $product->images[0];
                    } elseif (is_string($product->images) && $product->images !== '') {
                        $firstImage = $product->images;
                    }

                    if (is_array($firstImage)) {
                        $firstImage = $firstImage['path'] ?? null;
                    }

                    $displayImage = $firstImage ?: $product->image;
                @endphp
                <div class="px-6 py-4 hover:bg-[#f9f9f9] transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="w-20 h-20 bg-[#e2e2e2] rounded-lg overflow-hidden flex-shrink-0">
                            @if($displayImage)
                                <img src="{{ asset('storage/' . $displayImage) }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-[#888]">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-space font-bold text-sm text-[#1a1c1c] mb-2">{{ $product->title }}</h3>
                            <div class="grid grid-cols-3 gap-4 text-xs text-[#888]">
                                <div>
                                    <p class="font-space font-bold text-[#006a38]">{{ $units }} units</p>
                                </div>
                                <div>
                                    <p class="font-space font-bold text-[#006a38]">Rs. {{ number_format($revenue, 0) }}</p>
                                </div>
                                <div>
                                    <p>{{ $product->orders->max('created_at')?->format('M d, Y') ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<div class="h-8"></div>
@endsection
