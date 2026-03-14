@extends('layouts.admin')
@section('title', 'Dispute #' . $dispute->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.disputes') }}" class="text-sm font-semibold text-indigo-600 hover:underline">Back to Disputes</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Dispute Details --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $dispute->subject }}</h2>
                    <p class="text-sm text-slate-400 mt-0.5">
                        Dispute #{{ $dispute->id }} · {{ ucfirst($dispute->transaction_type) }} ·
                        Filed {{ $dispute->created_at->diffForHumans() }}
                    </p>
                </div>
                <span class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold {{ $dispute->statusBadge() }}">
                    {{ ucfirst(str_replace('_',' ',$dispute->status)) }}
                </span>
            </div>

            <div class="prose prose-sm max-w-none text-slate-700">
                <p>{{ $dispute->description }}</p>
            </div>
        </div>

        {{-- Transaction Context --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-3 text-slate-800">Transaction Reference</h3>
            @if($dispute->order)
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Order ID</dt><dd class="font-medium">#{{ $dispute->order->id }}</dd>
                    <dt class="text-slate-500">Product</dt><dd class="font-medium">{{ $dispute->order->product?->title ?? 'N/A' }}</dd>
                    <dt class="text-slate-500">Status</dt><dd><span class="capitalize">{{ $dispute->order->status }}</span></dd>
                </dl>
            @elseif($dispute->rentalRequest)
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Rental Request ID</dt><dd class="font-medium">#{{ $dispute->rentalRequest->id }}</dd>
                    <dt class="text-slate-500">Product</dt><dd class="font-medium">{{ $dispute->rentalRequest->product?->title ?? 'N/A' }}</dd>
                    <dt class="text-slate-500">Status</dt><dd><span class="capitalize">{{ $dispute->rentalRequest->status }}</span></dd>
                </dl>
            @elseif($dispute->swap)
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Swap ID</dt><dd class="font-medium">#{{ $dispute->swap->id }}</dd>
                    <dt class="text-slate-500">Status</dt><dd><span class="capitalize">{{ $dispute->swap->status }}</span></dd>
                </dl>
            @else
                <p class="text-sm text-slate-400">Transaction no longer exists.</p>
            @endif
        </div>

        @if($dispute->admin_notes)
            <div class="bg-blue-50 rounded-xl border border-blue-100 p-5">
                <p class="text-sm font-semibold text-blue-800 mb-1">Previous Admin Note</p>
                <p class="text-sm text-blue-700">{{ $dispute->admin_notes }}</p>
                @if($dispute->resolver)
                    <p class="text-xs text-blue-400 mt-2">by {{ $dispute->resolver->name }} · {{ $dispute->resolved_at?->diffForHumans() }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Resolution Panel --}}
    <div class="space-y-4">
        {{-- Reporter Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3 text-slate-800">Reporter</h3>
            <p class="font-medium text-slate-900">{{ $dispute->reporter?->name ?? 'N/A' }}</p>
            <p class="text-sm text-slate-500">{{ $dispute->reporter?->email }}</p>
            @if($dispute->reporter)
                <a href="{{ route('admin.users.show', $dispute->reporter->id) }}"
                   class="mt-2 inline-block text-xs text-indigo-600 hover:underline">View profile →</a>
            @endif
        </div>

        {{-- Resolution Form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-4 text-slate-800">Update Status</h3>

            @if($requiresEscalation)
                <div class="mb-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    This dispute involves privileged accounts. You must escalate to Super Admin.
                </div>

                <form method="POST" action="{{ route('admin.disputes.escalate', $dispute) }}" class="space-y-3 mb-4">
                    @csrf
                    @method('PATCH')
                    <textarea name="reason" rows="3" required
                              placeholder="Why this should be escalated..."
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                    <button type="submit"
                            class="w-full bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold py-2.5 rounded-lg transition">
                        Escalate to Super Admin
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Set Status</label>
                    <select name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @foreach(['in_review','resolved','dismissed'] as $s)
                            <option value="{{ $s }}" @selected($dispute->status === $s)>
                                {{ ucfirst(str_replace('_',' ',$s)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Admin Notes</label>
                    <textarea name="admin_notes" rows="4"
                              placeholder="Explain the resolution or next steps..."
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm resize-none">{{ old('admin_notes', $dispute->admin_notes) }}</textarea>
                </div>

                <button type="submit"
                        @disabled($requiresEscalation)
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-lg transition">
                    Save &amp; Notify Reporter
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
