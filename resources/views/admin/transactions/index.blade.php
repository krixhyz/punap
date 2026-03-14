@extends('layouts.admin')

@section('title', 'Transactions')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-3xl font-bold">Monitor Transactions</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="type" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                <option value="">Type</option>
                <option value="buy" @selected(request('type') === 'buy')>Buy</option>
                <option value="rent" @selected(request('type') === 'rent')>Rent</option>
                <option value="swap" @selected(request('type') === 'swap')>Swap</option>
            </select>
            <select name="status" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                <option value="">Status</option>
                <option value="pending" @selected(request('status') === 'pending')>pending</option>
                <option value="active" @selected(request('status') === 'active')>active</option>
                <option value="completed" @selected(request('status') === 'completed')>completed</option>
                <option value="failed" @selected(request('status') === 'failed')>failed</option>
            </select>
            <button class="btn-pill btn-pill-soft !px-4 !py-2 text-sm">Filter</button>
        </form>
    </div>

    @if(!auth()->user()->isSuperAdmin())
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-blue-700">
            <p class="font-semibold">Limited Access</p>
            <p class="text-sm mt-1">You can monitor transactions and resolve disputes. Cannot configure payment gateways or access raw financial data.</p>
        </div>
    @endif

    @if($financialSummary)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">Payments Total</p>
                <p class="text-3xl font-bold mt-2">Rs. {{ number_format($financialSummary['payments_total'], 2) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">Successful Revenue</p>
                <p class="text-3xl font-bold mt-2">Rs. {{ number_format($financialSummary['payments_successful'], 2) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-slate-500">Completed Buy Orders</p>
                <p class="text-3xl font-bold mt-2">{{ $financialSummary['orders_completed'] }}</p>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Buyer</th>
                    <th class="p-3 text-left">Seller</th>
                    <th class="p-3 text-left">Item</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $txn)
                    <tr>
                        <td class="p-3">{{ $txn['ref'] }}</td>
                        <td class="p-3">{{ $txn['buyer'] }}</td>
                        <td class="p-3">{{ $txn['seller'] }}</td>
                        <td class="p-3">{{ $txn['item'] }}</td>
                        <td class="p-3">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $txn['type'] === 'buy' ? 'bg-blue-100 text-blue-700' : ($txn['type'] === 'rent' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700') }}">{{ $txn['type'] }}</span>
                        </td>
                        <td class="p-3">Rs. {{ number_format((float)$txn['amount'], 2) }}</td>
                        <td class="p-3">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ in_array($txn['status'], ['completed','resolved']) ? 'bg-green-100 text-green-700' : (in_array($txn['status'], ['failed','cancelled']) ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') }}">{{ $txn['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-5 text-center text-slate-500">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
@endsection
