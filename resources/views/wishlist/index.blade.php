@extends('layouts.dashboard')

@section('content')
<section class="px-0 md:px-8 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div>
                <p class="font-space text-[12px] font-bold uppercase tracking-widest text-[#888] mb-2">Personal</p>
                <h1 class="font-space font-bold text-3xl text-[#1a1c1c]">My Wishlist</h1>
            </div>
            <span class="bg-[#f0f8f5] text-[#006a38] text-[10px] font-space font-bold px-3 py-1.5 rounded">
                {{ $wishlistItems->count() }} {{ \Illuminate\Support\Str::plural('item', $wishlistItems->count()) }}
            </span>
        </div>
        <a href="{{ route('products.index') }}" class="bg-transparent border-2 border-[#006a38] text-[#006a38] px-6 py-[10px] font-space font-bold text-sm uppercase tracking-wider rounded-lg hover:bg-[rgba(0,106,56,0.06)] transition-all">Browse Products</a>
    </div>
</section>

@if($wishlistItems->isEmpty())
    <section class="px-0 md:px-8 py-6">
        <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] px-8 py-14 md:px-12 md:py-16 text-center">
            <div class="mx-auto mb-6 inline-flex items-center justify-center rounded-2xl bg-[#eaf4ef] p-4 border border-[rgba(0,106,56,0.12)]">
                <svg class="h-8 w-8 text-[#006a38]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h2 class="font-space font-bold text-xl text-[#1a1c1c] mb-2">Your wishlist is empty</h2>
            <p class="font-manrope text-base text-[#444746] mb-6">Save items you value to revisit them later.</p>
            <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center rounded-lg bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-6 py-3 font-space font-bold text-sm uppercase tracking-wider hover:brightness-110">Explore Products</a>
        </div>
    </section>
@else
    <section class="px-0 md:px-8 py-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($wishlistItems as $item)
                @php $product = $item->product; @endphp
                <div class="bg-white rounded-lg shadow-[0_4px_6px_rgba(0,0,0,0.07)] border border-[rgba(189,202,189,0.1)] hover:shadow-[0_12px_24px_rgba(26,28,28,0.08)] transition-all overflow-hidden">
                    <!-- Image Container -->
                    <div class="relative w-full aspect-[4/3] overflow-hidden bg-[#f3f3f3]">
                        @php
                            $imagePath = null;
                            if (is_array($product->images ?? null) && !empty($product->images)) {
                                $firstImage = $product->images[0];
                                $imagePath = is_array($firstImage)
                                    ? ($firstImage['path'] ?? null)
                                    : (is_string($firstImage) ? $firstImage : null);
                            }

                            $imagePath = $imagePath ?: (is_string($product->image ?? null) ? $product->image : null);
                        @endphp
                        @if($imagePath)
                            <img src="{{ \App\Helpers\ImageUrlHelper::getProductImageUrl($imagePath) }}" alt="{{ $product->title }}" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="flex h-full items-center justify-center text-[#aaa]">
                                <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif

                        <!-- Wishlist Remove Button (top-right) -->
                        <div class="absolute right-3 top-3 z-10">
                            <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST" data-wishlist-action data-product-id="{{ $product->id }}">
                                @csrf
                                <button type="submit" title="Remove from wishlist" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#006a38] text-white hover:brightness-110 transition">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <a href="{{ route('products.show', $product->id) }}" class="block p-4">
                        <h3 class="truncate font-space font-bold text-[#1a1c1c]">{{ $product->title }}</h3>
                        <p class="mt-1 line-clamp-2 font-manrope text-sm text-[#444746]">{{ $product->description }}</p>

                        <!-- Type Chips -->
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if(in_array('sell', $product->type ?? []))
                                <span class="bg-[#d1ecf1] text-[#0c5460] text-[10px] font-space font-bold px-2 py-1">Buy</span>
                            @endif
                            @if(in_array('rent', $product->type ?? []))
                                <span class="bg-[#ffd580] text-[#664d03] text-[10px] font-space font-bold px-2 py-1">Rent</span>
                            @endif
                            @if(in_array('swap', $product->type ?? []))
                                <span class="bg-[#d4edda] text-[#155724] text-[10px] font-space font-bold px-2 py-1">Swap</span>
                            @endif
                        </div>

                        <!-- Price & Status -->
                        <div class="mt-4 flex items-end justify-between">
                            @if(in_array('sell', $product->type ?? []))
                                <p class="font-space font-bold text-lg text-[#006a38]">Rs. {{ number_format($product->price, 2) }}</p>
                            @else
                                <p class="font-manrope text-sm text-[#444746]">Exchange only</p>
                            @endif
                            <span class="text-xs font-space font-bold {{ $product->status === 'available' ? 'text-[#006a38]' : 'text-[#ba1a1a]' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </div>

                        <p class="mt-2 font-manrope text-xs text-[#444746]">By {{ $product->user?->name ?? 'Unknown' }} | Saved {{ $item->created_at->diffForHumans() }}</p>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif

<div class="h-8"></div>
@endsection
