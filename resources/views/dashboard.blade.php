@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <section class="surface-card-strong relative overflow-hidden px-6 py-9 sm:px-10">
            <div class="absolute -right-20 -top-20 h-60 w-60 rounded-full bg-cyan-200/30 blur-3xl"></div>
            <div class="absolute -left-14 bottom-0 h-56 w-56 rounded-full bg-emerald-200/30 blur-3xl"></div>
            <div class="relative">
                <h1 class="hero-title">Welcome Back, {{ auth()->user()->name ?? 'User' }}</h1>
                <p class="hero-subtitle">Manage listings, requests, and purchases from one place.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('products.create') }}" class="btn-pill btn-pill-dark">Add Listing</a>
                    <a href="{{ route('products.myListings') }}" class="btn-pill btn-pill-soft">My Listings</a>
                    <a href="{{ route('products.myPurchases') }}" class="btn-pill btn-pill-soft">Orders</a>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="surface-card lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-200 p-5">
                    <h2 class="text-xl font-bold text-slate-900">Notifications</h2>
                    <span id="notification-badge" class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                        {{ auth()->user()->unreadNotifications->count() }} New
                    </span>
                </div>

                <div id="dashboard-notification-list" class="space-y-4 p-5 min-h-[220px]">
            @php
                $grouped = [
                    'requests' => [],
                    'accepted' => [],
                    'rejected' => [],
                ];

                foreach (auth()->user()->unreadNotifications as $notification) {
                    $type = $notification->data['type'] ?? 'unknown';
                    $group = match(true) {
                            in_array($type, ['rental', 'swap', 'swapCounter']) => 'requests',
                            in_array($type, ['rentalAccept', 'swapAccept']) => 'accepted',
                            in_array($type, ['rentalReject', 'swapReject']) => 'rejected',
                        default => null,
                    };
                    if ($group) $grouped[$group][] = $notification;
                }
            @endphp

            @foreach (['requests' => 'New Requests', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $key => $label)
                @if (count($grouped[$key]))
                    <div>
                        <h4 class="mb-3 text-sm font-semibold text-slate-700">{{ $label }}</h4>
                        <div class="space-y-3">
                            @foreach ($grouped[$key] as $notification)
                                @php
                                    $type = $notification->data['type'] ?? 'unknown';
                                    $id = $notification->id;

                                    $message = match($type) {
                                        'rentalAccept' => 'Your rental request has been accepted.',
                                        'rentalReject' => 'Your rental request has been rejected.',
                                        'swapAccept' => 'Your swap request has been accepted.',
                                        'swapReject' => 'Your swap request has been rejected.',
                                        'swapCounter' => 'You received a counter offer for your swap.',
                                        'rental' => 'You have a new rental request.',
                                        'swap' => 'You have a new swap request.',
                                        default => $notification->data['message'] ?? 'You have a new notification.',
                                    };

                                    $readUrl = match(true) {
                                        $type === 'rental' => route('rental.review', ['rentalRequest' => $notification->data['rental_request_id'] ?? 0]),
                                        $type === 'swap' => route('swap.request.incoming'),
                                        $type === 'swapCounter' => route('swap.request.show', $notification->data['swap_request_id'] ?? 0),
                                        $type === 'swapAccept' => route('swap.request.show', $notification->data['swap_request_id'] ?? 0),
                                            $type === 'rentalAccept' => route('rental.payment', ['rentalRequest' => $notification->data['rental_request_id'] ?? 0]),
                                        default => request()->url(),
                                    };
                                @endphp

                                <a href="{{ $readUrl }}" data-notification-id="{{ $id }}"
                                              data-id="{{ $id }}"
                                   class="notification-item flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:bg-slate-50"
                                   onclick="event.preventDefault(); markReadAndRedirect(this);">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-100">
                                            <svg class="h-5 w-5 text-cyan-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8V6a2 2 0 00-2-2H5a2 2 0 00-2 2v2m18 0l-9 6-9-6m18 0v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-900">{{ $message }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>

                                        @if ($key === 'accepted')
                                            <div class="mt-2">
                                                @if ($type === 'swapAccept')
                                                    <a href="{{ route('swap.request.show', $notification->data['swap_request_id'] ?? 0) }}" class="text-xs font-semibold text-cyan-700 hover:underline">
                                                        View Swap →
                                                    </a>
                                                    @elseif ($type === 'rentalAccept')
                                                        <a href="{{ route('rental.payment', ['rentalRequest' => $notification->data['rental_request_id'] ?? 0]) }}" class="text-xs font-semibold text-cyan-700 hover:underline">
                                                            Pay Rental →
                                                        </a>
                                                @else
                                                    <a href="{{ route('products.myPurchases') }}" class="text-xs font-semibold text-cyan-700 hover:underline">
                                                        View Rental →
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif ($key === 'requests')
                                            <div class="mt-2">
                                                <span class="text-xs font-semibold text-cyan-700 hover:underline">View Request →</span>
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if (count($grouped['requests']) + count($grouped['accepted']) + count($grouped['rejected']) === 0)
                <div id="dashboard-notification-empty" class="text-center py-8">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M3 8v8a2 2 0 002 2h14a2 2 0 002-2V8m-9 6v6" />
                        </svg>
                    </div>
                    <p class="text-slate-600">No new notifications</p>
                    <p class="mt-1 text-sm text-slate-400">You're all caught up.</p>
                </div>
            @endif
        </div>
            </section>

            <aside class="space-y-6">
                <div class="surface-card p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Seller Metrics</h3>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Units Listed</p>
                            <p class="text-base font-bold">{{ $sellerMetrics['total_units_listed'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Active Units</p>
                            <p class="text-base font-bold">{{ $sellerMetrics['active_units'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Units Sold</p>
                            <p class="text-base font-bold">{{ $sellerMetrics['units_sold'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Sales Revenue</p>
                            <p class="text-base font-bold">Rs. {{ number_format($sellerMetrics['sales_revenue'],2) }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Active Rentals</p>
                            <p class="text-base font-bold">{{ $sellerMetrics['active_rentals_owner'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Rental Revenue</p>
                            <p class="text-base font-bold">Rs. {{ number_format($sellerMetrics['rental_revenue_owner'],2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="surface-card p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Buyer Metrics</h3>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Purchases</p>
                            <p class="text-base font-bold">{{ $buyerMetrics['purchases_count'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Units Bought</p>
                            <p class="text-base font-bold">{{ $buyerMetrics['purchased_units'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Total Spent</p>
                            <p class="text-base font-bold">Rs. {{ number_format($buyerMetrics['total_spent'],2) }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Active Rentals</p>
                            <p class="text-base font-bold">{{ $buyerMetrics['active_rentals_renter'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Completed Swaps</p>
                            <p class="text-base font-bold">{{ $buyerMetrics['completed_swaps'] }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <section class="surface-card">
            <div class="flex items-center justify-between border-b border-slate-200 p-5">
            <h2 class="text-lg font-bold text-slate-900">Flagged Products</h2>
            <span class="text-sm text-slate-500">
                These items were flagged by moderators. Review or edit to resolve.
            </span>
        </div>
        <ul class="divide-y divide-slate-100">
            @php
                $flagged = auth()->user()->products()->where('flagged', true)->latest()->get();
            @endphp
            @forelse ($flagged as $product)
                <li class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-medium text-slate-900">{{ $product->title }}</div>
                            <div class="text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($product->description, 120) }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded text-xs bg-red-100 text-red-700">Flagged</span>
                            <a href="{{ route('products.edit', $product->id) }}"
                               class="btn-pill btn-pill-soft !px-3 !py-1.5 text-xs">Edit</a>
                        </div>
                    </div>
                </li>
            @empty
                <li class="p-5 text-sm text-slate-500">No flagged products.</li>
            @endforelse
        </ul>
    </section>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        async function markReadAndRedirect(el) {
    const id = el.dataset.notificationId;
    const url = el.getAttribute('href') || "{{ route('products.myPurchases') }}"; // fallback if href is missing

    if (!id) return window.location.href = url;

    try {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        await fetch("{{ route('notifications.markRead') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id })
        });
    } catch (e) {
        console.error(e);
    } finally {
        window.location.href = url;
    }
}

        function prependNotificationToDashboard(message, redirectUrl, id, type = 'general') {
            const list = document.getElementById('dashboard-notification-list');
            if (!list) return;

            const empty = document.getElementById('dashboard-notification-empty');
            if (empty) empty.remove();

            const badge = document.getElementById('notification-badge');
            if (badge) {
                const current = parseInt((badge.textContent || '0').replace(/\D/g, '')) || 0;
                badge.textContent = `${current + 1} New`;
            }

            const url = redirectUrl && redirectUrl !== '#'
                ? redirectUrl
                : "{{ route('notifications.index') }}";

            const safeMessage = escapeHtml(message || 'You have a new notification.');
            const item = document.createElement('a');
            item.href = url;
            item.dataset.notificationId = id;
            item.className = 'notification-item flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:bg-slate-50';
            item.setAttribute('onclick', 'event.preventDefault(); markReadAndRedirect(this);');

            item.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-100">
                        <svg class="h-5 w-5 text-cyan-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8V6a2 2 0 00-2-2H5a2 2 0 00-2 2v2m18 0l-9 6-9-6m18 0v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900">${safeMessage}</p>
                    <p class="mt-1 text-xs text-slate-500">just now</p>
                    <div class="mt-2">
                        <span class="text-xs font-semibold text-cyan-700 hover:underline">View →</span>
                    </div>
                </div>`;

            list.prepend(item);
        }

        function escapeHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str ?? ''));
            return d.innerHTML;
        }
    </script>
@endsection