@extends('layouts.admin')
@section('title', 'Reviews')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100">
        <h2 class="text-2xl font-bold">All Reviews</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3 text-left">#</th>
                    <th class="p-3 text-left">Reviewer</th>
                    <th class="p-3 text-left">Reviewee</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Rating</th>
                    <th class="p-3 text-left">Review</th>
                    <th class="p-3 text-left">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reviews as $review)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 text-slate-400">{{ $review->id }}</td>
                        <td class="p-3 font-medium">{{ $review->reviewer?->name ?? 'N/A' }}</td>
                        <td class="p-3">
                            <a href="{{ route('admin.users.show', $review->reviewee_id) }}"
                               class="text-indigo-600 hover:underline">{{ $review->reviewee?->name ?? 'N/A' }}</a>
                        </td>
                        <td class="p-3 capitalize">{{ $review->transaction_type }}</td>
                        <td class="p-3">
                            <div class="flex">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-slate-200' }}"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                                <span class="ml-1 text-slate-500">{{ $review->rating }}/5</span>
                            </div>
                        </td>
                        <td class="p-3 text-slate-600 max-w-xs truncate">{{ $review->body ?? '-' }}</td>
                        <td class="p-3 text-slate-400 text-xs">{{ $review->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-5 text-center text-slate-500">No reviews yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
