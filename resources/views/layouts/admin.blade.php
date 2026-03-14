<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f4f8fb] text-slate-900">
@php
    $adminUser = auth()->user();
    $isSuper = $adminUser->isSuperAdmin();

    $tabs = $isSuper
        ? [
            ['label' => 'Overview', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard']],
            ['label' => 'User Management', 'route' => 'admin.users', 'active' => ['admin.users*']],
            ['label' => 'Content Moderation', 'route' => 'admin.content', 'active' => ['admin.content*', 'admin.products*']],
            ['label' => 'Transactions', 'route' => 'admin.transactions', 'active' => ['admin.transactions*']],
            ['label' => 'Analytics', 'route' => 'admin.analytics', 'active' => ['admin.analytics*']],
            ['label' => 'System Config', 'route' => 'admin.system.config', 'active' => ['admin.system.config*']],
        ]
        : [
            ['label' => 'Overview', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard']],
            ['label' => 'User Management', 'route' => 'admin.users', 'active' => ['admin.users*']],
            ['label' => 'Content Moderation', 'route' => 'admin.content', 'active' => ['admin.content*', 'admin.products*']],
            ['label' => 'Transactions', 'route' => 'admin.transactions', 'active' => ['admin.transactions*']],
            ['label' => 'Disputes', 'route' => 'admin.disputes', 'active' => ['admin.disputes*']],
            ['label' => 'Reports', 'route' => 'admin.reports', 'active' => ['admin.reports*']],
        ];
@endphp

<div class="min-h-screen">
    <header class="max-w-7xl mx-auto px-6 pt-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold text-slate-900">{{ $isSuper ? 'Super Admin Dashboard' : 'Admin Dashboard' }}</h1>
                <p class="text-slate-600 mt-2">{{ $isSuper ? 'Full platform oversight and control' : 'Operational moderation and user management' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-6 py-3 rounded-xl text-white font-semibold {{ $isSuper ? 'bg-purple-600' : 'bg-blue-600' }}">
                    {{ $isSuper ? 'Super Admin' : 'Admin' }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">Logout</button>
                </form>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-2xl border border-slate-200">
            <nav class="px-4 py-1 flex flex-wrap gap-2">
                @foreach($tabs as $tab)
                    @php
                        $isActive = false;
                        foreach ($tab['active'] as $pattern) {
                            if (request()->routeIs($pattern)) {
                                $isActive = true;
                                break;
                            }
                        }
                    @endphp
                    <a href="{{ route($tab['route']) }}"
                       class="px-4 py-3 text-base border-b-2 {{ $isActive ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-800' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
