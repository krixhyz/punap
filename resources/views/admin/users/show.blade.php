@extends('layouts.admin')

@section('title', 'User Detail')

@section('content')
<div class="space-y-6">
    <div class="surface-card p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->role === 'super_admin' ? 'bg-cyan-100 text-cyan-700' : ($user->role === 'admin' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700') }}">{{ $user->role }}</span>
                <span class="px-2 py-1 text-xs rounded {{ ($user->account_status ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $user->account_status ?? 'active' }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="surface-card lg:col-span-2">
            <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Listings</h3></div>
            <ul class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <li class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ $product->title }}</p>
                            <p class="text-xs text-slate-500">{{ $product->category }} · {{ $product->status }}</p>
                        </div>
                        <span class="text-sm text-slate-700">Rs. {{ number_format((float)$product->price, 2) }}</span>
                    </li>
                @empty
                    <li class="p-4 text-sm text-slate-500">No listings.</li>
                @endforelse
            </ul>
        </div>

        <div class="surface-card">
            <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Recent Reviews</h3></div>
            <ul class="divide-y divide-slate-100">
                @forelse($reviews as $review)
                    <li class="p-4">
                        <p class="text-sm font-medium">Rating: {{ $review->rating }}/5</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $review->body ?: 'No comment' }}</p>
                    </li>
                @empty
                    <li class="p-4 text-sm text-slate-500">No reviews.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="surface-card">
        <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Transaction History</h3></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3 text-left">Order</th>
                        <th class="p-3 text-left">Item</th>
                        <th class="p-3 text-left">Qty</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr>
                            <td class="p-3">#{{ $order->id }}</td>
                            <td class="p-3">{{ $order->product?->title ?? 'N/A' }}</td>
                            <td class="p-3">{{ $order->quantity }}</td>
                            <td class="p-3">Rs. {{ number_format((float)($order->total_price ?? 0), 2) }}</td>
                            <td class="p-3">{{ $order->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-slate-500">No orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($admin->isSuperAdmin())
        <div class="surface-card">
            <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Sensitive Payment Data (Super Admin)</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Payment ID</th>
                            <th class="p-3 text-left">Provider</th>
                            <th class="p-3 text-left">Amount</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $payment)
                            <tr>
                                <td class="p-3">#{{ $payment->id }}</td>
                                <td class="p-3">{{ $payment->provider }}</td>
                                <td class="p-3">Rs. {{ number_format((float) $payment->total_amount, 2) }}</td>
                                <td class="p-3">{{ $payment->status }}</td>
                                <td class="p-3">{{ $payment->transaction_uuid }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-slate-500">No payments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
