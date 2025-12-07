@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-semibold mb-4">Dashboard</h1>

<div class="grid gap-6 md:grid-cols-4">
    <div class="rounded-xl bg-white shadow-sm border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Total Users</div>
        <div class="mt-2 text-3xl font-bold">{{ $totalUsers }}</div>
    </div>
    <div class="rounded-xl bg-white shadow-sm border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Total Products</div>
        <div class="mt-2 text-3xl font-bold">{{ $totalProducts }}</div>
    </div>
    <div class="rounded-xl bg-white shadow-sm border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Admins</div>
        <div class="mt-2 text-3xl font-bold">{{ $totalAdmins }}</div>
    </div>
    <div class="rounded-xl bg-white shadow-sm border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Flagged Products</div>
        <div class="mt-2 text-3xl font-bold text-red-600">{{ $flaggedProducts }}</div>
    </div>
</div>

<div class="mt-8 grid gap-6 md:grid-cols-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-100">
            <h2 class="font-semibold">Recent Users</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse ($users as $user)
                <li class="p-5 flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                    </div>
                    <span class="px-2.5 py-1 rounded text-xs {{ $user->role === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $user->role }}
                    </span>
                </li>
            @empty
                <li class="p-5 text-sm text-gray-500">No users</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-100">
            <h2 class="font-semibold">Recent Products</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse ($products as $product)
                <li class="p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $product->title }}</div>
                            <div class="text-sm text-gray-500">by {{ $product->user->name ?? 'N/A' }}</div>
                        </div>
                        @if($product->flagged)
                            <span class="px-2.5 py-1 rounded text-xs bg-red-100 text-red-700">Flagged</span>
                        @endif
                    </div>
                </li>
            @empty
                <li class="p-5 text-sm text-gray-500">No products</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection