@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="surface-card-strong">
    <div class="bg-[#f3f3f3] p-5 flex items-center justify-between">
        <h2 class="section-title">Manage Products</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="editorial-table">
            <thead>
                <tr>
                    <th class="p-3 text-left">Title</th>
                    <th class="p-3 text-left">Owner</th>
                    <th class="p-3 text-left">Flagged</th>
                    <th class="p-3 text-left">Approval</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td class="p-3 font-semibold">{{ $product->title }}</td>
                        <td class="p-3 text-[#1a1c1c]">{{ $product->user->name ?? 'N/A' }}</td>
                        <td class="p-3">
                            @if($product->flagged)
                                <span class="status-chip status-error">Yes</span>
                            @else
                                <span class="status-chip status-neutral">No</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if($product->approval_status === 'APPROVED')
                                <span class="status-chip status-success">Approved</span>
                            @elseif($product->approval_status === 'PENDING')
                                <span class="status-chip status-neutral">Pending</span>
                            @elseif($product->approval_status === 'REJECTED')
                                <span class="status-chip status-error">Rejected</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                @if(! $product->flagged)
                                <form method="POST" action="{{ route('admin.products.flag', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn-pill !px-3 !py-1 text-xs !border-amber-600 !text-amber-600 hover:!bg-amber-600 hover:!text-white">Flag</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('admin.products.unflag', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn-pill btn-pill-soft !px-3 !py-1 text-xs">Unflag</button>
                                </form>
                                @endif
                                <a href="{{ route('admin.products.show', $product) }}" class="btn-pill !px-3 !py-1 text-xs !border-[#0066cc] !text-[#0066cc] hover:!bg-[#0066cc] hover:!text-white">View</a>
                                @if(($canDeleteByProduct[$product->id] ?? true) === true)
                                    <form method="POST" action="{{ route('admin.products.delete', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-pill !px-3 !py-1 text-xs !border-[#ba1a1a] !text-[#ba1a1a] hover:!bg-[#ba1a1a] hover:!text-white"
                                                onclick="return confirm('Delete product?')">Delete</button>
                                    </form>
                                @else
                                    <button type="button"
                                            class="btn-pill !px-3 !py-1 text-xs !border-neutral-400 !text-neutral-400 cursor-not-allowed"
                                            title="{{ $deleteBlockersByProduct[$product->id] ?? 'Active obligations prevent deletion.' }}"
                                            disabled>Locked</button>
                                @endif
                            </div>
                            @if(($canDeleteByProduct[$product->id] ?? true) === false)
                                <p class="mt-2 text-xs text-[#ba1a1a]">{{ $deleteBlockersByProduct[$product->id] }}</p>
                            @endif
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