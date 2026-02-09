<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
<div class="min-h-screen flex">
    <aside class="w-64 bg-white/80 backdrop-blur border-r border-gray-200">
        <div class="p-4">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded bg-indigo-600 text-white font-bold">A</span>
                <span class="font-semibold text-lg">Admin Panel</span>
            </div>
        </div>
        <nav class="px-3 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 font-medium' : '' }}">
                <span class="i"></span> <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.users') }}"
               class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.users*') ? 'bg-gray-100 font-medium' : '' }}">
                <span class="i"></span> <span>Users</span>
            </a>
            <a href="{{ route('admin.products') }}"
               class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.products*') ? 'bg-gray-100 font-medium' : '' }}">
                <span class="i"></span> <span>Products</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <h1 class="text-xl font-semibold">@yield('title', 'Admin')</h1>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm px-3 py-1 rounded bg-gray-900 text-white hover:bg-gray-800">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <section class="max-w-7xl mx-auto px-6 py-6">
            @if (session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-800 px-4 py-2">{{ session('success') }}</div>
            @endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>