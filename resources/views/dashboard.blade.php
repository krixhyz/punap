@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 rounded-xl p-8 mb-8 text-white shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-1">Welcome back, {{ auth()->user()->name ?? 'User' }}</h2>
                    <p class="text-green-100 text-lg">You're ready to manage your listings and rentals.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="{{ route('products.create') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 transition-all">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Listing</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Create a new rental listing</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('products.myListings') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 transition-all">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Listings</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Manage your active listings</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('products.myPurchases') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 transition-all">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition-colors">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18l-2 13H5L3 3zm5 16h8a2 2 0 104 0H8a2 2 0 10-4 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Purchases</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">View your rental history</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
           <!-- Notifications -->
<div class="lg:col-span-2">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Notifications</h3>
            <span id="notification-badge" class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 text-xs font-medium px-2.5 py-0.5 rounded-full">
                {{ auth()->user()->unreadNotifications->count() }} New
            </span>
        </div>

        <div class="p-6 space-y-6 min-h-[200px]">
            @php
                $grouped = [
                    'requests' => [],
                    'accepted' => [],
                    'rejected' => [],
                ];

                foreach (auth()->user()->unreadNotifications as $notification) {
                    $type = $notification->data['type'] ?? 'unknown';
                    $group = match(true) {
                        in_array($type, ['rental', 'swap']) => 'requests',
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
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ $label }}</h4>
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
                                        'rental' => 'You have a new rental request.',
                                        'swap' => 'You have a new swap request.',
                                        default => $notification->data['message'] ?? 'You have a new notification.',
                                    };

                                    $readUrl = match(true) {
                                        $type === 'rental' => route('rental.review', ['request' => $notification->data['rental_request_id'] ?? 0]),
                                        $type === 'swap' => route('swap.request.incoming', $notification->data['swap_request_id'] ?? 0),
                                        in_array($type, ['rentalAccept', 'swapAccept']) => route('products.myPurchases'),
                                        default => request()->url(),
                                    };
                                @endphp

                                <a href="{{ $readUrl }}" data-notification-id="{{ $id }}"
                                   class="notification-item flex items-start space-x-4 p-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors block"
                                   onclick="event.preventDefault(); markReadAndRedirect(this);">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8V6a2 2 0 00-2-2H5a2 2 0 00-2 2v2m18 0l-9 6-9-6m18 0v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $message }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>

                                        @if ($key === 'accepted')
                                            <div class="mt-2">
                                                <a href="{{ route('products.myPurchases') }}" class="text-blue-600 dark:text-blue-400 text-xs font-medium hover:underline">
                                                    {{ $type === 'rentalAccept' ? 'View Rental →' : 'View Swap →' }}
                                                </a>
                                            </div>
                                        @elseif ($key === 'requests')
                                            <div class="mt-2">
                                                <span class="text-blue-600 dark:text-blue-400 text-xs font-medium hover:underline">View Request →</span>
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
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M3 8v8a2 2 0 002 2h14a2 2 0 002-2V8m-9 6v6" />
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">No new notifications</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">You're all caught up.</p>
                </div>
            @endif
        </div>
    </div>
</div>


            <!-- Quick Stats -->
            <aside class="space-y-6">
                {{-- Seller Metrics --}}
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Seller Metrics</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Units Listed</p>
                            <p class="font-bold">{{ $sellerMetrics['total_units_listed'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Active Units</p>
                            <p class="font-bold">{{ $sellerMetrics['active_units'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Units Sold</p>
                            <p class="font-bold">{{ $sellerMetrics['units_sold'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Sales Revenue</p>
                            <p class="font-bold">Rs. {{ number_format($sellerMetrics['sales_revenue'],2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Active Rentals (Owner)</p>
                            <p class="font-bold">{{ $sellerMetrics['active_rentals_owner'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Rental Revenue</p>
                            <p class="font-bold">Rs. {{ number_format($sellerMetrics['rental_revenue_owner'],2) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Buyer Metrics --}}
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Buyer Metrics</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Purchases</p>
                            <p class="font-bold">{{ $buyerMetrics['purchases_count'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Units Bought</p>
                            <p class="font-bold">{{ $buyerMetrics['purchased_units'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Spent</p>
                            <p class="font-bold">Rs. {{ number_format($buyerMetrics['total_spent'],2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Active Rentals (Renter)</p>
                            <p class="font-bold">{{ $buyerMetrics['active_rentals_renter'] }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Completed Swaps</p>
                            <p class="font-bold">{{ $buyerMetrics['completed_swaps'] }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="mt-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold">Flagged Products</h2>
            <span class="text-sm text-gray-500">
                These items were flagged by moderators. Review or edit to resolve.
            </span>
        </div>
        <ul class="divide-y divide-gray-100">
            @php
                $flagged = auth()->user()->products()->where('flagged', true)->latest()->get();
            @endphp
            @forelse ($flagged as $product)
                <li class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-medium">{{ $product->title }}</div>
                            <div class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($product->description, 120) }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded text-xs bg-red-100 text-red-700">Flagged</span>
                            <a href="{{ route('products.edit', $product->id) }}"
                               class="px-3 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Edit</a>
                        </div>
                    </div>
                </li>
            @empty
                <li class="p-5 text-sm text-gray-500">No flagged products.</li>
            @endforelse
        </ul>
    </div>
</div>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        // Dark mode toggle persistence
        (function() {
            if (localStorage.getItem('darkMode') === 'true') {
                document.documentElement.classList.add('dark');
            }
            const toggle = document.getElementById('darkToggle');
            toggle?.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                const isDark = document.documentElement.classList.contains('dark');
                localStorage.setItem('darkMode', isDark);
            });
        })();

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
    </script>
@endsection