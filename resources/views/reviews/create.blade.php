@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <a href="{{ route('products.myPurchases') }}" class="inline-flex items-center text-sm font-semibold text-teal-700 hover:text-teal-800">Back to My Purchases</a>

    <div class="surface-card-strong p-6 sm:p-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Leave a Review</h1>
        <p class="mt-2 text-sm text-slate-600">Reviewing <span class="font-semibold">{{ $reviewee?->name ?? 'Unknown' }}</span> for {{ ucfirst($type) }} transaction.</p>

        @if($existingReview)
            <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">You already submitted a review for this transaction. Submitting again will update it.</div>
        @endif

        @if($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('review.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="ref_id" value="{{ $id }}">

            <div>
                <label class="field-label">Rating <span class="text-red-500">*</span></label>
                <div class="flex gap-2" x-data="{ rating: {{ old('rating', $existingReview?->rating ?? 0) }} }">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="{{ $i }}" class="sr-only" x-on:change="rating = {{ $i }}" {{ old('rating', $existingReview?->rating) == $i ? 'checked' : '' }}>
                            <svg x-bind:class="rating >= {{ $i }} ? 'text-amber-400' : 'text-slate-300'" class="h-9 w-9 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </label>
                    @endfor
                </div>
                @error('rating')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="body" class="field-label">Review (optional)</label>
                <textarea id="body" name="body" rows="4" placeholder="Share your experience..." class="field-input">{{ old('body', $existingReview?->body) }}</textarea>
                @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-pill btn-pill-dark w-full justify-center py-3">{{ $existingReview ? 'Update Review' : 'Submit Review' }}</button>
        </form>
    </div>
</div>
@endsection
