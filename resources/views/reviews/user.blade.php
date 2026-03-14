@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="surface-card-strong p-6 sm:p-8">
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-2xl font-bold text-teal-700">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">{{ $user->name }}</h1>
                <div class="mt-1 flex items-center gap-2">
                    @if($avgRating)
                        @php $stars = round($avgRating); @endphp
                        <div class="flex">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="h-5 w-5 {{ $i <= $stars ? 'text-amber-400' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-slate-700">{{ number_format($avgRating, 1) }}</span>
                        <span class="text-sm text-slate-500">({{ $reviews->total() }} {{ Str::plural('review', $reviews->total()) }})</span>
                    @else
                        <span class="text-sm text-slate-500">No reviews yet</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($reviews as $review)
            <article class="surface-card p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">{{ strtoupper(substr($review->reviewer->name, 0, 1)) }}</div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $review->reviewer->name }}</p>
                            <p class="text-xs text-slate-400">{{ $review->created_at->diffForHumans() }} · {{ ucfirst($review->transaction_type) }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-4 w-4 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                </div>
                @if($review->body)
                    <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ $review->body }}</p>
                @endif
            </article>
        @empty
            <div class="surface-card p-10 text-center text-slate-500">No reviews yet for this user.</div>
        @endforelse
    </div>

    <div>{{ $reviews->links() }}</div>
</div>
@endsection
