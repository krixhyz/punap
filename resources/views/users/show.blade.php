@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-8">
    <section class="surface-card-strong p-8">
        <div class="flex items-start gap-5">
            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-emerald-600 text-4xl font-semibold text-white shrink-0">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div class="flex-1 min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-slate-900">{{ $user->name }}</h1>
                    @if($user->email_verified_at)
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-700">Verified</span>
                    @endif
                </div>

                <div class="mb-3 flex flex-wrap items-center gap-2 text-slate-600">
                    @php $roundedStars = $avgRating ? round($avgRating) : 0; @endphp
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-5 w-5 {{ $i <= $roundedStars ? 'text-amber-400' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <span class="font-semibold text-slate-800">{{ $avgRating ? number_format($avgRating, 1) : 'No rating yet' }}</span>
                    <span class="text-sm">({{ $reviewsCount }} {{ \Illuminate\Support\Str::plural('review', $reviewsCount) }})</span>
                </div>

                <p class="text-sm text-slate-600">{{ $location ?: 'Location not specified' }}</p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-6"><p class="text-3xl font-bold text-slate-900">{{ $activeListingsCount }}</p><p class="mt-1 text-slate-600">Active Listings</p></div>
        <div class="surface-card p-6"><p class="text-3xl font-bold text-slate-900">{{ $completedDeals }}</p><p class="mt-1 text-slate-600">Completed Deals</p><p class="mt-2 text-xs text-slate-500">Sales {{ $completedSales }} · Rentals {{ $completedRentals }} · Swaps {{ $completedSwaps }}</p></div>
        <div class="surface-card p-6"><p class="text-3xl font-bold text-slate-900">{{ $avgRating ? number_format($avgRating, 1) : '-' }}</p><p class="mt-1 text-slate-600">Average Rating</p></div>
        <div class="surface-card p-6"><p class="text-3xl font-bold text-slate-900">{{ $user->created_at?->format('Y') }}</p><p class="mt-1 text-slate-600">Member Since</p></div>
    </section>

    <section class="surface-card p-6">
        <div class="mb-5 flex items-center justify-between"><h2 class="text-2xl font-bold text-slate-900">Active Listings</h2><span class="text-sm text-slate-500">{{ $activeListingsCount }} {{ \Illuminate\Support\Str::plural('item', $activeListingsCount) }}</span></div>
        @if($activeListings->isEmpty())
            <p class="text-slate-500">No active listings.</p>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($activeListings as $listing)
                    <a href="{{ route('products.show', $listing->id) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:shadow-md">
                        <div class="h-44 bg-slate-100">
                            @if($listing->image)
                                <img src="{{ asset('storage/' . $listing->image) }}" alt="{{ $listing->title }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-400"><svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                            @endif
                        </div>
                        <div class="p-4"><h3 class="truncate text-lg font-semibold text-slate-900">{{ $listing->title }}</h3><p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($listing->description, 70) }}</p><div class="mt-3 flex items-center justify-between text-sm"><span class="font-semibold text-purple-700">Rs. {{ number_format((float) $listing->price, 2) }}</span><span class="text-slate-500">Qty: {{ $listing->quantity }}</span></div></div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="surface-card p-6">
        <h2 class="mb-5 text-2xl font-bold text-slate-900">Recent Reviews</h2>
        @if($recentReviews->isEmpty())
            <p class="text-slate-500">No reviews yet.</p>
        @else
            <div class="space-y-5">
                @foreach($recentReviews as $review)
                    <article class="border-b border-slate-100 pb-5 last:border-b-0 last:pb-0">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $review->reviewer?->name ?? 'User' }}</p>
                                <div class="mt-1 flex">@for($i = 1; $i <= 5; $i++)<svg class="h-5 w-5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor</div>
                            </div>
                            <span class="text-sm text-slate-500">{{ $review->created_at->format('M j, Y') }}</span>
                        </div>
                        @if($review->body)
                            <p class="mt-2 text-slate-700">{{ $review->body }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection