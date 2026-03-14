@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-extrabold text-slate-900">My Disputes</h1>
        <a href="{{ route('products.myPurchases') }}" class="btn-pill btn-pill-soft">My Purchases</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="surface-card overflow-hidden">
        @forelse ($disputes as $dispute)
            <div class="border-b border-slate-100 p-6 last:border-b-0">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $dispute->subject }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ ucfirst($dispute->transaction_type) }} #{{ $dispute->{$dispute->transaction_type === 'order' ? 'order_id' : ($dispute->transaction_type === 'rental' ? 'rental_request_id' : 'swap_id')} }} · Filed {{ $dispute->created_at->diffForHumans() }}
                        </p>
                        <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ $dispute->description }}</p>
                        @if($dispute->admin_notes)
                            <div class="mt-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-700"><span class="font-medium">Admin note:</span> {{ $dispute->admin_notes }}</div>
                        @endif
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $dispute->statusBadge() }}">{{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</span>
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-slate-500">You have not filed any disputes.</div>
        @endforelse
    </div>

    <div>{{ $disputes->links() }}</div>
</div>
@endsection
