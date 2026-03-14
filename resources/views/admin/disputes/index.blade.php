@extends('layouts.admin')
@section('title', 'Disputes')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-3xl font-bold">Dispute Resolution</h2>
        <form method="GET" class="flex gap-2">
            <select name="status" onchange="this.form.submit()" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                <option value="">Filter by Status</option>
                @foreach(['open','in_review','resolved','dismissed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($disputes as $dispute)
            <div class="rounded-xl border p-4 {{ in_array($dispute->status, ['open','in_review']) ? 'border-slate-200' : 'border-red-200 bg-red-50' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-3xl font-bold">{{ $dispute->subject }}</h3>
                            <span class="px-2 py-1 rounded text-xs {{ $dispute->status === 'in_review' ? 'bg-blue-100 text-blue-700' : ($dispute->status === 'open' ? 'bg-amber-100 text-amber-700' : 'bg-red-600 text-white') }}">
                                {{ str_replace('_',' ', $dispute->status) }}
                            </span>
                        </div>
                        <p class="text-slate-600 mt-1">Reporter: {{ $dispute->reporter?->name ?? 'Unknown user' }}</p>
                        <p class="text-sm text-slate-500 mt-1">Type: {{ $dispute->transaction_type }} · Date Opened {{ $dispute->created_at->format('M j') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mt-4">
                    <a href="{{ route('admin.disputes.show', $dispute) }}" class="text-center bg-blue-600 text-white rounded-lg py-2">Contact Parties</a>

                    <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="resolved">
                        <input type="hidden" name="admin_notes" value="Resolved in favor of buyer by operations.">
                        <button class="w-full bg-green-600 text-white rounded-lg py-2">Resolve in Favor of Buyer</button>
                    </form>

                    <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="dismissed">
                        <input type="hidden" name="admin_notes" value="Resolved in favor of seller by operations.">
                        <button class="w-full bg-purple-600 text-white rounded-lg py-2">Resolve in Favor of Seller</button>
                    </form>

                    <form method="POST" action="{{ route('admin.disputes.escalate', $dispute) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="reason" value="Requires super admin review due to risk profile.">
                        <button class="w-full bg-red-600 text-white rounded-lg py-2">Escalate to Super Admin</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-slate-500">No disputes found.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $disputes->links() }}</div>
</div>
@endsection
