<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
    <title>{{ $title ?? (auth()->user()?->isSuperAdmin() ? 'Super Admin Dashboard' : 'Admin Dashboard') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f9f9f9] font-manrope text-[#1a1c1c]">
@php
    $adminUser = auth()->user();
    $isSuper = $adminUser->isSuperAdmin();

    $mainNav = [
        [
            'label' => 'Overview',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard'],
            'icon' => 'M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-2m-9-2l4 2m0-11L9 5m4 6l4-2m4 2l-4-2',
        ],
        [
            'label' => 'User Management',
            'route' => 'admin.users',
            'active' => ['admin.users*'],
            'icon' => 'M17 20h5V10H2v10h5m10 0H7m10 0v-5a5 5 0 10-10 0v5m10 0H7',
        ],
        [
            'label' => 'Content Moderation',
            'route' => 'admin.content',
            'active' => ['admin.content*', 'admin.products*'],
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A2 2 0 0114 3.586L18.414 8A2 2 0 0119 9.414V19a2 2 0 01-2 2z',
        ],
    ];

    $operationsNav = $isSuper
        ? [
            [
                'label' => 'Transactions',
                'route' => 'admin.transactions',
                'active' => ['admin.transactions*'],
                'icon' => 'M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z',
            ],
            [
                'label' => 'Wallet Payouts',
                'route' => 'admin.wallet.payouts',
                'active' => ['admin.wallet.payouts*'],
                'icon' => 'M17 9V7a5 5 0 00-10 0v2m12 0H5m14 0v8a2 2 0 01-2 2H7a2 2 0 01-2-2V9m9 5h.01',
            ],
            [
                'label' => 'Analytics',
                'route' => 'admin.analytics',
                'active' => ['admin.analytics*'],
                'icon' => 'M3 3v18h18M9 17V9m4 8V5m4 12v-6',
            ],
            [
                'label' => 'System Config',
                'route' => 'admin.system.config',
                'active' => ['admin.system.config*'],
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ],
        ]
        : [
            [
                'label' => 'Transactions',
                'route' => 'admin.transactions',
                'active' => ['admin.transactions*'],
                'icon' => 'M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z',
            ],
            [
                'label' => 'Wallet Payouts',
                'route' => 'admin.wallet.payouts',
                'active' => ['admin.wallet.payouts*'],
                'icon' => 'M17 9V7a5 5 0 00-10 0v2m12 0H5m14 0v8a2 2 0 01-2 2H7a2 2 0 01-2-2V9m9 5h.01',
            ],
            [
                'label' => 'Disputes',
                'route' => 'admin.disputes',
                'active' => ['admin.disputes*'],
                'icon' => 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.857 12.19c.75 1.334-.213 2.99-1.742 2.99H3.142c-1.53 0-2.492-1.656-1.743-2.99l6.858-12.19z M12 9v4m0 4h.01',
            ],
            [
                'label' => 'Reports',
                'route' => 'admin.reports',
                'active' => ['admin.reports*'],
                'icon' => 'M9 17v-6m4 6V7m4 10V9M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z',
            ],
        ];
@endphp

    @include('layouts.navigation')

    <div class="flex gap-0 items-start">
        <aside class="w-72 bg-white border-r border-[rgba(189,202,189,0.2)] sticky top-0 self-start h-screen overflow-y-auto">
            <div class="p-6 border-b border-[rgba(189,202,189,0.3)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888]">Account</p>
                        <p class="mt-2 font-space font-bold text-lg text-[#1a1c1c]">{{ $adminUser->name }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-[#006a38] px-3 py-1 font-space text-[10px] font-bold uppercase tracking-[0.18em] text-white">
                        {{ $isSuper ? 'Super Admin' : 'Admin' }}
                    </span>
                </div>

            </div>

            <nav class="p-6 space-y-1">
                <div class="mb-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] px-4 mb-4">Main</p>
                    @foreach($mainNav as $tab)
                        @php
                            $isActive = false;
                            foreach ($tab['active'] as $pattern) {
                                if (request()->routeIs($pattern)) {
                                    $isActive = true;
                                    break;
                                }
                            }
                        @endphp
                        <a href="{{ route($tab['route']) }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ $isActive ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"></path>
                            </svg>
                            <span class="font-space font-bold text-sm">{{ $tab['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="mb-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] px-4 mb-4">Operations</p>
                    @foreach($operationsNav as $tab)
                        @php
                            $isActive = false;
                            foreach ($tab['active'] as $pattern) {
                                if (request()->routeIs($pattern)) {
                                    $isActive = true;
                                    break;
                                }
                            }
                        @endphp
                        <a href="{{ route($tab['route']) }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ $isActive ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"></path>
                            </svg>
                            <span class="font-space font-bold text-sm">{{ $tab['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="mb-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#888] px-4 mb-4">Other</p>
                    <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.profile.*') ? 'bg-[#f0f8f5] text-[#006a38]' : 'text-[#444746] hover:bg-[#f9f9f9]' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="font-space font-bold text-sm">Profile Settings</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-[#444746] hover:bg-[#f9f9f9] transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"></path>
                            </svg>
                            <span class="font-space font-bold text-sm">Logout</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @if (session('success'))
                    <div class="mb-4 border-2 border-[#10b981] bg-[#d1fae5] px-4 py-3 font-manrope text-sm text-[#065f46]">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 border-2 border-[#ba1a1a] bg-[#fee2e2] px-4 py-3 font-manrope text-sm text-[#7f1d1d]">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

</body>
</html>
