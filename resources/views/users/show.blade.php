@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-8">
    <section class="surface-card-strong p-8">
        <div class="flex items-start gap-5">
            @php
                $avatarInitial = \Illuminate\Support\Str::upper(
                    \Illuminate\Support\Str::substr(trim((string) $user->name), 0, 1)
                );
                if ($avatarInitial === '') {
                    $avatarInitial = 'U';
                }
            @endphp
            <div class="allow-loop-circle flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-[#006a38] text-4xl font-semibold text-white shadow-sm">{{ $avatarInitial }}</div>
            <div class="flex-1 min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-[var(--reloop-ink)]">{{ $user->name }}</h1>
                    @if($user->email_verified_at)
                        <span class="status-chip status-success">Verified</span>
                    @endif
                    @if($user->profile_status === 'VERIFIED')
                        <span class="bg-[#d1fae5] text-[#065f46] text-xs font-space font-bold uppercase tracking-[0.05em] px-3 py-1 rounded-full">★ Verified Seller</span>
                    @endif
                </div>

                <div class="mb-3 flex flex-wrap items-center gap-2 text-[var(--reloop-ink-soft)]">
                    @php $roundedStars = $avgRating ? round($avgRating) : 0; @endphp
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-5 w-5 {{ $i <= $roundedStars ? 'text-amber-400' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <span class="font-semibold text-[var(--reloop-ink)]">{{ $avgRating ? number_format($avgRating, 1) : 'No rating yet' }}</span>
                    <span class="text-sm">({{ $reviewsCount }} {{ \Illuminate\Support\Str::plural('review', $reviewsCount) }})</span>
                </div>

                <p class="text-sm text-[var(--reloop-ink-soft)]">
                    @if ($user->city && $user->province)
                        {{ $user->city->name }}, {{ $user->province->name }}
                    @elseif ($user->city)
                        {{ $user->city->name }}
                    @elseif ($user->province)
                        {{ $user->province->name }}
                    @else
                        Location not specified
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-6"><p class="text-3xl font-bold text-[var(--reloop-ink)]">{{ $activeListingsCount }}</p><p class="mt-1 text-[var(--reloop-ink-soft)]">Active Listings</p></div>
        <div class="surface-card p-6"><p class="text-3xl font-bold text-[var(--reloop-ink)]">{{ $completedDeals }}</p><p class="mt-1 text-[var(--reloop-ink-soft)]">Completed Deals</p><p class="mt-2 text-xs text-[var(--reloop-ink-soft)]">Sales {{ $completedSales }} · Rentals {{ $completedRentals }} · Swaps {{ $completedSwaps }}</p></div>
        <div class="surface-card p-6"><p class="text-3xl font-bold text-[var(--reloop-ink)]">{{ $avgRating ? number_format($avgRating, 1) : '-' }}</p><p class="mt-1 text-[var(--reloop-ink-soft)]">Average Rating</p></div>
        <div class="surface-card p-6"><p class="text-3xl font-bold text-[var(--reloop-ink)]">{{ $user->created_at?->format('Y') }}</p><p class="mt-1 text-[var(--reloop-ink-soft)]">Member Since</p></div>
    </section>

    <section class="surface-card p-6">
        <div class="mb-5 flex items-center justify-between"><h2 class="text-2xl font-bold text-[var(--reloop-ink)]">Active Listings</h2><span class="text-sm text-[var(--reloop-ink-soft)]">{{ $activeListingsCount }} {{ \Illuminate\Support\Str::plural('item', $activeListingsCount) }}</span></div>
        @if($activeListings->isEmpty())
            <p class="text-[var(--reloop-ink-soft)]">No active listings.</p>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($activeListings as $listing)
                    <a href="{{ route('products.show', $listing->id) }}" class="surface-card overflow-hidden border transition hover:-translate-y-0.5 hover:shadow-[6px_6px_0_var(--reloop-shadow)]">
                        <div class="h-44 bg-[var(--reloop-primary-soft)]/30">
                            @if($listing->image)
                                <img src="{{ asset('storage/' . $listing->image) }}" alt="{{ $listing->title }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-[var(--reloop-ink-soft)]"><svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                            @endif
                        </div>
                        <div class="p-4"><h3 class="truncate text-lg font-semibold text-[var(--reloop-ink)]">{{ $listing->title }}</h3><p class="mt-2 text-sm text-[var(--reloop-ink-soft)]">{{ \Illuminate\Support\Str::limit($listing->description, 70) }}</p><div class="mt-3 flex items-center justify-between text-sm"><span class="font-semibold text-[var(--reloop-primary-dark)]">Rs. {{ number_format((float) $listing->price, 2) }}</span><span class="text-[var(--reloop-ink-soft)]">Qty: {{ $listing->quantity }}</span></div></div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="surface-card p-6">
        <h2 class="mb-5 text-2xl font-bold text-[var(--reloop-ink)]">Recent Reviews</h2>
        @if($recentReviews->isEmpty())
            <p class="text-[var(--reloop-ink-soft)]">No reviews yet.</p>
        @else
                <div class="space-y-3">
                @foreach($recentReviews as $review)
                    <article class="bg-[var(--reloop-surface-low)] px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-[var(--reloop-ink)]">{{ $review->reviewer?->name ?? 'User' }}</p>
                                <div class="mt-1 flex">@for($i = 1; $i <= 5; $i++)<svg class="h-5 w-5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor</div>
                            </div>
                            <span class="text-sm text-[var(--reloop-ink-soft)]">{{ $review->created_at->format('M j, Y') }}</span>
                        </div>
                        @if($review->body)
                            <p class="mt-2 text-[var(--reloop-ink-soft)]">{{ $review->body }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
