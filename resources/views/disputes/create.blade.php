@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <a href="{{ route('products.myPurchases') }}" class="inline-flex items-center text-sm font-semibold text-teal-700 hover:text-teal-800">Back to My Purchases</a>

    <div class="surface-card-strong p-6 sm:p-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Report a Dispute</h1>
        <p class="mt-2 text-sm text-slate-600">Transaction type: <span class="font-semibold">{{ ucfirst($type) }}</span> (ref #{{ $id }})</p>

        @if($existing)
            <div class="mt-5 rounded-xl border px-4 py-3 text-sm
                {{ $existing->status === 'open' ? 'border-amber-200 bg-amber-50 text-amber-800' : '' }}
                {{ $existing->status === 'in_review' ? 'border-blue-200 bg-blue-50 text-blue-800' : '' }}
                {{ in_array($existing->status, ['resolved','dismissed']) ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : '' }}">
                <p class="font-semibold">You already filed a dispute for this transaction.</p>
                <p class="mt-0.5">Status: <strong>{{ ucfirst(str_replace('_',' ', $existing->status)) }}</strong></p>
                @if($existing->admin_notes)
                    <p class="mt-1 text-xs">Admin note: {{ $existing->admin_notes }}</p>
                @endif
            </div>
        @endif

        @if($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('dispute.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="ref_id" value="{{ $id }}">

            <div>
                <label for="subject" class="field-label">Subject <span class="text-red-500">*</span></label>
                <input type="text" id="subject" name="subject" value="{{ old('subject', $existing?->subject) }}" placeholder="Brief description of the issue" class="field-input">
                @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="field-label">Description <span class="text-red-500">*</span></label>
                <textarea id="description" name="description" rows="5" placeholder="Provide details of what happened and the resolution you expect." class="field-input">{{ old('description', $existing?->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-pill btn-pill-dark flex-1 justify-center py-3">{{ $existing ? 'Update Dispute' : 'Submit Dispute' }}</button>
                <a href="{{ route('products.myPurchases') }}" class="btn-pill btn-pill-soft flex-1 justify-center py-3">Cancel</a>
            </div>
        </form>
    </div>

    <div class="text-center">
        <a href="{{ route('dispute.my') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800">View all my disputes</a>
    </div>
</div>
@endsection
