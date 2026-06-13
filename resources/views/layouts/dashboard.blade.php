<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
    <title>{{ $title ?? config('app.name', 'Punap') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @auth
    <script>
        window.Laravel = {
            userId: {{ auth()->id() }},
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    @endauth
</head>
<body class="bg-[#f9f9f9] font-manrope text-[#1a1c1c]">
    @include('layouts.navigation')

    <div class="flex gap-0 items-start">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-[rgba(189,202,189,0.2)] sticky top-0 self-start h-screen overflow-y-auto">
            <nav class="p-6 space-y-1">
                <div class="mb-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] px-4 mb-4">Main</p>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-2m-9-2l4 2m0-11L9 5m4 6l4-2m4 2l-4-2"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Overview</span>
                    </a>
                </div>

                <div class="mb-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] px-4 mb-4">Activity</p>
                    
                    <a href="{{ route('products.myListings') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('products.myListings') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">My Listings</span>
                    </a>

                    

                    <a href="{{ route('products.myPurchases') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('products.myPurchases') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">My Orders</span>
                    </a>

                    <a href="{{ route('rental.myRentals') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('rental.myRentals') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Rentals</span>
                    </a>

                    <a href="{{ route('swap.mySwaps') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('swap.mySwaps') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m0 0l4 4m10-4v12m0 0l4-4m0 0l-4-4"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Swaps</span>
                    </a>

                    <a href="{{ route('wallet.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('wallet.*') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Wallet</span>
                    </a>

                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('wishlist.index') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Wishlist</span>
                    </a>
                </div>

                <div class="mb-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] px-4 mb-4">Other</p>
                    
                    <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('notifications.index') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Notifications</span>
                    </a>

                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('profile.edit') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Profile Settings</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto">
                @yield('content')
            </div>
        </main>
    </div>

    @include('layouts.footer')

    @stack('scripts')
</body>

</html>
