@extends('layouts.admin')

@section('title', 'Content Moderation')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-3xl font-bold">Content Moderation</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="category" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                <option value="">Filter by Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </select>
            <button class="btn-pill btn-pill-soft !px-4 !py-2 text-sm">Filter</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($products as $product)
            <article class="rounded-xl border p-4 {{ $product->flagged ? 'border-red-300 bg-red-50' : 'border-amber-300 bg-amber-50' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-bold text-slate-900">{{ $product->title }}</h3>
                            <span class="rounded-full bg-red-600 px-2 py-1 text-xs font-semibold text-white {{ $product->flagged ? '' : 'opacity-0' }}">High Priority</span>
                        </div>
                        <p class="text-slate-600 mt-1">Seller: {{ $product->user?->name ?? 'N/A' }} · Category: {{ ucfirst($product->category ?? 'general') }}</p>
                    </div>
                </div>

                <div class="mt-3 bg-white rounded-md px-3 py-2 text-slate-600 text-sm border border-slate-200">
                    {{ $product->flagged ? 'Possible policy violation reported.' : 'Awaiting content verification.' }}
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.content.decision', $product) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="decision" value="approve">
                        <button class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('admin.content.decision', $product) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="decision" value="flag">
                        <button class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Flag Listing</button>
                    </form>

                    <form method="POST" action="{{ route('admin.content.decision', $product) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="decision" value="reject">
                        <button class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Reject</button>
                    </form>

                    <a href="{{ route('products.show', $product->id) }}" class="btn-pill btn-pill-soft !px-4 !py-2 text-sm">View Full Details</a>
                </div>
            </article>
        @empty
            <p class="text-slate-500">No listings pending moderation.</p>
        @endforelse
    </div>

    <div class="mt-5">{{ $products->links() }}</div>
</div>
@endsection
