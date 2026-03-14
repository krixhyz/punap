@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
@if($isSuperAdmin)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Total Users</p>
            <p class="text-4xl font-bold mt-2">{{ number_format($totalUsers) }}</p>
            <p class="text-green-600 text-sm mt-1">{{ number_format($activeUsers) }} active</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Total Listings</p>
            <p class="text-4xl font-bold mt-2">{{ number_format($totalProducts) }}</p>
            <p class="text-green-600 text-sm mt-1">{{ number_format($flaggedProducts) }} flagged</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Monthly Revenue</p>
            <p class="text-4xl font-bold mt-2">Rs. {{ number_format($monthlyRevenue, 1) }}</p>
            <p class="text-green-600 text-sm mt-1">{{ number_format($completedTransactions) }} completed transactions</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Pending Disputes</p>
            <p class="text-4xl font-bold mt-2">{{ number_format($openDisputes) }}</p>
            <p class="text-red-600 text-sm mt-1">{{ number_format($reportedItems) }} reported items</p>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-3xl font-bold mb-5">System Health</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">Server Uptime</p>
                <p class="text-4xl font-bold mt-2">99.9%</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">API Response Time</p>
                <p class="text-4xl font-bold mt-2">120ms</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">Database Load</p>
                <p class="text-4xl font-bold mt-2">45%</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">Storage Used</p>
                <p class="text-4xl font-bold mt-2">67%</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-3xl font-bold mb-4">Pending Moderation</h3>
            <div class="space-y-4">
                @forelse($products->take(3) as $product)
                    <div class="rounded-xl border {{ $product->flagged ? 'border-red-200 bg-red-50' : 'border-slate-200' }} p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold text-3xl">{{ $product->title }}</p>
                                <p class="text-slate-600">by {{ $product->user?->name ?? 'N/A' }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-lg text-sm {{ $product->flagged ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-700' }}">
                                {{ $product->flagged ? 'flagged' : 'pending' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <form method="POST" action="{{ route('admin.content.decision', $product) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="approve">
                                <button class="w-full bg-green-600 text-white rounded-lg py-2">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.content.decision', $product) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="reject">
                                <button class="w-full bg-red-600 text-white rounded-lg py-2">Reject</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500">No pending moderation items.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-3xl font-bold mb-4">Recent Disputes</h3>
            <div class="space-y-4">
                @forelse($recentDisputes as $dispute)
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-3xl">{{ $dispute->subject }}</p>
                                <p class="text-slate-600">{{ $dispute->reporter?->name ?? 'Unknown user' }}</p>
                                <p class="text-slate-700 mt-1">Amount: {{ $dispute->order?->total_price ? 'Rs. ' . number_format((float)$dispute->order->total_price,2) : 'N/A' }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-lg bg-red-600 text-white text-sm">Disputed</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <a href="{{ route('admin.disputes.show', $dispute) }}" class="text-center bg-blue-600 text-white rounded-lg py-2">Review</a>
                            <a href="{{ route('admin.disputes.show', $dispute) }}" class="text-center border border-slate-300 rounded-lg py-2">Details</a>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500">No disputes available.</p>
                @endforelse
            </div>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Pending Users</p>
            <p class="text-4xl font-bold mt-2">{{ $pendingUsers }}</p>
            <p class="text-green-600 text-sm mt-1">verification queue</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Flagged Listings</p>
            <p class="text-4xl font-bold mt-2">{{ $flaggedProducts }}</p>
            <p class="text-green-600 text-sm mt-1">needs moderation</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Active Disputes</p>
            <p class="text-4xl font-bold mt-2">{{ $openDisputes }}</p>
            <p class="text-green-600 text-sm mt-1">in operations queue</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-slate-500">Reports to Review</p>
            <p class="text-4xl font-bold mt-2">{{ $reportedItems }}</p>
            <p class="text-slate-500 text-sm mt-1">content/dispute reports</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-3xl font-bold mb-4">Pending Verifications</h3>
            <div class="space-y-4">
                @forelse($pendingVerifications as $candidate)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold text-3xl">{{ $candidate->name }}</p>
                                <p class="text-slate-600">{{ $candidate->email }}</p>
                                <p class="text-sm text-slate-500 mt-1">Email verification pending</p>
                            </div>
                            <span class="px-3 py-1 rounded-lg bg-amber-100 text-amber-700 text-sm">pending</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <form method="POST" action="{{ route('admin.users.status', $candidate) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="account_status" value="active">
                                <button class="w-full bg-green-600 text-white rounded-lg py-2">Verify</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.status', $candidate) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="account_status" value="suspended">
                                <button class="w-full bg-red-600 text-white rounded-lg py-2">Reject</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500">No pending user verifications.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-3xl font-bold mb-4">Priority Disputes</h3>
            <div class="space-y-4">
                @forelse($recentDisputes as $dispute)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold text-3xl">{{ $dispute->subject }}</p>
                                <p class="text-slate-600">Reporter: {{ $dispute->reporter?->name ?? 'Unknown' }}</p>
                                <p class="text-sm text-slate-500 mt-1">Type: {{ $dispute->transaction_type }} • Opened {{ $dispute->created_at->format('M j') }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-lg {{ $dispute->status === 'in_review' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} text-sm">
                                {{ str_replace('_',' ', $dispute->status) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <a href="{{ route('admin.disputes.show', $dispute) }}" class="text-center bg-blue-600 text-white rounded-lg py-2">Review</a>
                            <a href="{{ route('admin.disputes.show', $dispute) }}" class="text-center border border-slate-300 rounded-lg py-2">Contact Parties</a>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500">No disputes in queue.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif
@endsection
