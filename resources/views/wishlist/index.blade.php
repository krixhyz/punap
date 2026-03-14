@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-extrabold text-slate-900">My Wishlist</h1>
            <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">
                {{ $wishlistItems->count() }} {{ Str::plural('item', $wishlistItems->count()) }}
            </span>
        </div>
        <a href="{{ route('products.index') }}" class="btn-pill btn-pill-soft">Browse Products</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($wishlistItems->isEmpty())
        <div class="surface-card p-14 text-center">
            <h2 class="text-xl font-bold text-slate-700">Your wishlist is empty</h2>
            <p class="mt-2 text-sm text-slate-500">Save items you love to revisit them later.</p>
            <a href="{{ route('products.index') }}" class="btn-pill btn-pill-dark mt-6">Explore Products</a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($wishlistItems as $item)
                @php $product = $item->product; @endphp
                <article class="group surface-card overflow-hidden transition hover:-translate-y-1 hover:shadow-[0_18px_35px_rgba(15,23,42,0.14)]">
                    <div class="relative h-52 overflow-hidden bg-slate-100">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="flex h-full items-center justify-center text-slate-300">
                                <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif

                        <div class="absolute right-3 top-3 z-10">
                            <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST">
                                @csrf
                                <button type="submit" title="Remove from wishlist" class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white shadow-sm hover:bg-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <a href="{{ route('products.show', $product->id) }}" class="block p-4">
                        <h3 class="truncate text-lg font-bold text-slate-900">{{ $product->title }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $product->description }}</p>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if(in_array('sell', $product->type ?? []))
                                <span class="rounded-full bg-blue-600 px-2 py-1 text-[11px] font-semibold text-white">Buy</span>
                            @endif
                            @if(in_array('rent', $product->type ?? []))
                                <span class="rounded-full bg-amber-400 px-2 py-1 text-[11px] font-semibold text-slate-900">Rent</span>
                            @endif
                            @if(in_array('swap', $product->type ?? []))
                                <span class="rounded-full bg-emerald-600 px-2 py-1 text-[11px] font-semibold text-white">Swap</span>
                            @endif
                        </div>

                        <div class="mt-4 flex items-end justify-between">
                            @if(in_array('sell', $product->type ?? []))
                                <p class="text-xl font-extrabold text-slate-900">Rs. {{ number_format($product->price, 2) }}</p>
                            @else
                                <p class="text-sm font-semibold text-slate-500">Exchange only</p>
                            @endif
                            <span class="text-xs font-semibold {{ $product->status === 'available' ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </div>

                        <p class="mt-2 text-xs text-slate-400">By {{ $product->user?->name ?? 'Unknown' }} · Saved {{ $item->created_at->diffForHumans() }}</p>
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
