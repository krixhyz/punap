@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-3xl font-bold">User Reports</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="type" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                <option value="">Filter by Type</option>
                <option value="order" @selected(request('type') === 'order')>order</option>
                <option value="rental" @selected(request('type') === 'rental')>rental</option>
                <option value="swap" @selected(request('type') === 'swap')>swap</option>
            </select>
            <button class="btn-pill btn-pill-soft !px-4 !py-2 text-sm">Filter</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($reportItems as $item)
            @if(!request('type') || request('type') === $item->transaction_type)
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4">
                    <div class="mb-2 flex items-center gap-2">
                        <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-800">{{ $item->transaction_type }}</h3>
                        <span class="rounded-full bg-amber-600 px-2 py-1 text-xs font-semibold text-white">{{ $item->status === 'open' ? 'New' : 'Investigating' }}</span>
                    </div>
                    <p class="text-slate-700">Reporter: {{ $item->reporter?->name ?? 'Unknown' }} · Subject: {{ $item->subject }}</p>
                    <p class="mt-2 text-slate-600 bg-white border border-slate-200 rounded px-3 py-2">{{ $item->description }}</p>
                    <p class="text-sm text-slate-500 mt-2">Reported on {{ $item->created_at->format('F j, Y') }}</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.disputes.show', $item) }}" class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Investigate</a>

                        <form method="POST" action="{{ route('admin.disputes.resolve', $item) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="in_review">
                            <input type="hidden" name="admin_notes" value="Escalated for action by report operations.">
                            <button class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Take Action</button>
                        </form>

                        <form method="POST" action="{{ route('admin.disputes.resolve', $item) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="dismissed">
                            <input type="hidden" name="admin_notes" value="Report dismissed after review.">
                            <button class="rounded-full bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Dismiss Report</button>
                        </form>
                    </div>
                </div>
            @endif
        @empty
            <p class="text-slate-500">No reports available.</p>
        @endforelse
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-slate-500">Open Disputes</p>
            <p class="text-2xl font-bold">{{ $base['open_disputes'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-slate-500">In Review</p>
            <p class="text-2xl font-bold">{{ $base['in_review_disputes'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-slate-500">Resolved</p>
            <p class="text-2xl font-bold">{{ $base['resolved_disputes'] }}</p>
        </div>
    </div>

    @if($isSuperAdmin)
        <div class="mt-4 rounded-xl border border-purple-200 bg-purple-50 p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-purple-800">Super Admin Export & Financial Insights</h3>
                <a href="{{ route('admin.reports', ['export' => 'csv']) }}" class="px-3 py-2 rounded-lg bg-purple-600 text-white text-sm">Export Full CSV</a>
            </div>
            <p class="text-purple-700 mt-2">Total Revenue: Rs. {{ number_format($full['total_revenue'] ?? 0, 2) }} · Active Users: {{ $full['active_users'] ?? 0 }}</p>
        </div>
    @endif
</div>
@endsection
