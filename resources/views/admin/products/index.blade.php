@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold">Manage Products</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="p-3 text-left">Title</th>
                    <th class="p-3 text-left">Owner</th>
                    <th class="p-3 text-left">Flagged</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($products as $product)
                    <tr>
                        <td class="p-3">{{ $product->title }}</td>
                        <td class="p-3">{{ $product->user->name ?? 'N/A' }}</td>
                        <td class="p-3">
                            @if($product->flagged)
                                <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Yes</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">No</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                @if(! $product->flagged)
                                <form method="POST" action="{{ route('admin.products.flag', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-3 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600">Flag</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('admin.products.unflag', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-3 py-1 rounded bg-gray-600 text-white text-xs hover:bg-gray-700">Unflag</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('admin.products.delete', $product) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700"
                                            onclick="return confirm('Delete product?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $products->links() }}
    </div>
</div>
@endsection