@extends('layouts.admin')

@section('title', 'Analytics')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-3xl font-bold">Platform Analytics</h2>
        <div class="flex items-center gap-2">
            <button class="btn-pill btn-pill-soft !px-4 !py-2 text-sm">Date Range</button>
            <a href="{{ route('admin.reports', ['export' => 'csv']) }}" class="rounded-full bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">Generate Custom Report</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500">User Growth</p>
            <p class="text-4xl font-bold mt-2">{{ $userGrowthThisMonth }}</p>
            <p class="text-sm text-slate-500 mt-1">vs last month {{ $userGrowthLastMonth }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500">Revenue Growth</p>
            <p class="text-4xl font-bold mt-2">Rs. {{ number_format($revenueThisMonth, 2) }}</p>
            <p class="text-sm text-slate-500 mt-1">vs last month Rs. {{ number_format($revenueLastMonth, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500">Listing Growth</p>
            <p class="text-4xl font-bold mt-2">{{ $listingGrowthThisMonth }}</p>
            <p class="text-sm text-slate-500 mt-1">vs last month {{ $listingGrowthLastMonth }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-5 mb-6">
        <h3 class="text-2xl font-bold mb-4">Sustainability Impact</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <p class="text-4xl font-bold text-green-600">{{ number_format($totalProducts * 0.0043, 1) }}</p>
                <p class="text-slate-600">tons CO₂ Saved</p>
            </div>
            <div>
                <p class="text-4xl font-bold text-green-600">{{ number_format($totalProducts) }}</p>
                <p class="text-slate-600">Items Reused</p>
            </div>
            <div>
                <p class="text-4xl font-bold text-green-600">89%</p>
                <p class="text-slate-600">Waste Reduction</p>
            </div>
            <div>
                <p class="text-4xl font-bold text-green-600">{{ number_format($activeUsers) }}</p>
                <p class="text-slate-600">Eco Champions</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-10 text-center text-slate-500">
        <p class="text-xl">Advanced charts and visualizations coming soon</p>
        <button class="mt-4 rounded-full bg-purple-600 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-700">Configure Analytics Tools</button>
    </div>
</div>
@endsection
