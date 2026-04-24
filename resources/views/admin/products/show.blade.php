@extends('layouts.admin')

@section('title', 'Product Details - ' . $product->title)

@section('content')
<div class="space-y-6">
    <!-- Header with back button -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products') }}" class="btn-pill btn-pill-soft !px-3 !py-1">← Back</a>
            <h1 class="font-space text-3xl font-bold text-[#1a1c1c]">{{ $product->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="font-space text-xs font-bold uppercase tracking-widest {{ $product->flagged ? 'border-2 border-[#ba1a1a] bg-[#fee2e2] text-[#ba1a1a]' : 'border-2 border-[#10b981] bg-[#d1fae5] text-[#10b981]' }}">
                {{ $product->flagged ? 'Flagged' : 'Not Flagged' }}
            </span>
            @if($product->approval_status === 'APPROVED')
                <span class="font-space text-xs font-bold uppercase tracking-widest border-2 border-[#10b981] bg-[#d1fae5] text-[#10b981]">Approved</span>
            @elseif($product->approval_status === 'PENDING')
                <span class="font-space text-xs font-bold uppercase tracking-widest border-2 border-[#f59e0b] bg-[#fef3c7] text-[#b45309]">Pending</span>
            @elseif($product->approval_status === 'REJECTED')
                <span class="font-space text-xs font-bold uppercase tracking-widest border-2 border-[#ba1a1a] bg-[#fee2e2] text-[#ba1a1a]">Rejected</span>
            @endif
        </div>
    </div>

    <!-- Product Approval Actions (if PENDING) -->
    @if($product->approval_status === 'PENDING')
        <div class="surface-card p-6 border-l-4 border-[#f59e0b]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-extrabold text-[#b45309]">Awaiting Approval</p>
                    <p class="text-sm text-[#888888] mt-1">This product listing is pending approval. Review and approve or reject it.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-pill !px-6 !border-[#10b981] !text-[#10b981] hover:!bg-[#10b981] hover:!text-white" x-data x-on:click="$dispatch('open-modal', { id: 'approve-modal' })">Approve</button>
                    <button type="button" class="btn-pill !px-6 !border-[#ba1a1a] !text-[#ba1a1a] hover:!bg-[#ba1a1a] hover:!text-white" x-data x-on:click="$dispatch('open-modal', { id: 'reject-modal' })">Reject</button>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div x-cloak x-data @open-modal.window="open = $event.detail.id === 'approve-modal'" @keydown.escape.window="open = false" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div @click.outside="open = false" class="surface-card p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold mb-4">Approve Product?</h3>
                <p class="text-sm text-[#888888] mb-6">The seller will be notified that their product "{{ $product->title }}" has been approved.</p>
                <div class="flex gap-2 justify-end">
                    <button @click="open = false" class="btn-pill btn-pill-soft !px-6">Cancel</button>
                    <form action="{{ route('admin.products.approve', $product->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn-pill !px-6 !border-[#10b981] !text-[#10b981] hover:!bg-[#10b981] hover:!text-white">Confirm Approval</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div x-cloak x-data @open-modal.window="open = $event.detail.id === 'reject-modal'" @keydown.escape.window="open = false" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div @click.outside="open = false" class="surface-card p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold mb-4">Reject Product?</h3>
                <p class="text-sm text-[#888888] mb-4">Provide an optional reason for rejection. The seller will be notified.</p>
                <form action="{{ route('admin.products.reject', $product->id) }}" method="POST">
                    @csrf
                    <textarea name="reason" placeholder="Optional: Explain why this product is rejected..." class="w-full p-3 bg-[#f3f3f3] border-0 border-b-2 border-gray-400 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 mb-4" rows="3"></textarea>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="open = false" class="btn-pill btn-pill-soft !px-6">Cancel</button>
                        <button type="submit" class="btn-pill !px-6 !border-[#ba1a1a] !text-[#ba1a1a] hover:!bg-[#ba1a1a] hover:!text-white">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Product Details Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Product Info Card -->
            <div class="surface-card p-6">
                <h2 class="mb-4 text-xl font-bold">Product Information</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Product ID</p>
                            <p class="mt-1 font-monospace text-lg font-semibold">{{ $product->id }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Owner</p>
                            <p class="mt-1">
                                <a href="{{ route('admin.users.show', $product->user) }}" class="text-[#006a38] hover:underline font-semibold">
                                    {{ $product->user->name ?? 'Unknown User' }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Category</p>
                            <p class="mt-1 capitalize font-semibold">{{ $product->category?->name ?? 'General' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Status</p>
                            <p class="mt-1 capitalize font-semibold text-green-600">{{ $product->status }}</p>
                        </div>
                    </div>

                    <div class="bg-[#f3f3f3] p-4">
                        <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Description</p>
                        <p class="mt-2 font-manrope text-[#444746] leading-relaxed">{{ $product->description }}</p>
                    </div>

                    <div class="bg-[#f3f3f3] p-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Quantity</p>
                                <p class="mt-1 text-2xl font-bold">{{ $product->quantity ?? 1 }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Price</p>
                                <p class="mt-1 text-2xl font-bold text-[#006a38]">Rs. {{ number_format($product->price ?? 0, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Posted</p>
                                <p class="mt-1 font-manrope text-sm text-[#444746]">{{ $product->created_at->format('M j, Y \a\t H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#f3f3f3] p-4">
                        <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888]">Listing Type</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @if($product->type)
                                @foreach((array) $product->type as $type)
                                    <span class="font-space text-xs font-bold uppercase tracking-widest border-2 border-blue-300 bg-blue-50 text-blue-700">{{ ucfirst($type) }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Images -->
                    @if($product->images || $product->image)
                    <div class="bg-[#f3f3f3] p-4">
                        <p class="text-sm font-space font-bold uppercase tracking-wider text-[#888888] mb-3">Images</p>
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                            @if($product->image)
                            <div class="overflow-hidden border-2 border-[#bdbdbd]">
                                <img src="{{ asset('storage/' . $product->image) }}" alt="Cover" class="h-32 w-full object-cover">
                            </div>
                            @endif
                            @if($product->images)
                                @foreach((array) $product->images as $image)
                                <div class="overflow-hidden border-2 border-[#bdbdbd]">
                                    <img src="{{ asset('storage/' . $image) }}" alt="Product" class="h-32 w-full object-cover">
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Rental Information (if applicable) -->
            @php
                $rentalInfo = $product->rentals;
            @endphp
            @if(in_array('rent', (array)($product->type ?? [])) && $rentalInfo)
            <div class="surface-card p-6">
                <h2 class="mb-4 text-xl font-bold">Rental Information</h2>
                <div class="space-y-3 pb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-space text-sm font-bold uppercase tracking-wider text-[#888888]">Rent Fare</p>
                            <p class="mt-1 font-semibold">Rs. {{ number_format($rentalInfo->rent_fare ?? 0, 2) }}/{{ $rentalInfo->rent_type ?? 'day' }}</p>
                        </div>
                        <div>
                            <p class="font-space text-sm font-bold uppercase tracking-wider text-[#888888]">Security Deposit</p>
                            <p class="mt-1 font-semibold">Rs. {{ number_format($rentalInfo->rent_deposit ?? 0, 2) }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-space text-sm font-bold uppercase tracking-wider text-[#888888]">From</p>
                            <p class="mt-1">{{ $rentalInfo->available_from?->format('M j, Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="font-space text-sm font-bold uppercase tracking-wider text-[#888888]">Available Duration</p>
                            <p class="mt-1">{{ $rentalInfo->available_duration ? $rentalInfo->available_duration . ' day(s)' : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Orders -->
            @if($product->orders->count() > 0)
            <div class="surface-card p-6">
                <h2 class="mb-4 text-xl font-bold">Buy Orders ({{ $product->orders->count() }})</h2>
                <div class="overflow-x-auto">
                    <table class="editorial-table">
                        <thead>
                            <tr>
                                <th class="p-3 text-left">Buyer</th>
                                <th class="p-3 text-left">Quantity</th>
                                <th class="p-3 text-left">Amount</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->orders as $order)
                            <tr>
                                <td class="p-3 font-semibold">{{ $order->buyer?->name ?? 'Unknown' }}</td>
                                <td class="p-3">{{ $order->quantity ?? 1 }}</td>
                                <td class="p-3 font-semibold">Rs. {{ number_format($order->total_price ?? 0, 2) }}</td>
                                <td class="p-3">
                                    <span class="status-chip {{ $order->status === 'completed' ? 'status-success' : 'status-warning' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="p-3 font-manrope text-sm text-[#444746]">{{ $order->created_at?->format('M j, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Reviews -->
            @if($reviews->count() > 0)
            <div class="surface-card p-6">
                <h2 class="mb-4 text-xl font-bold">Reviews ({{ $reviews->count() }})</h2>
                <div class="editorial-list">
                    @foreach($reviews as $review)
                    <div class="bg-[#f3f3f3] px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $review->reviewer?->name ?? 'Anonymous' }}</p>
                                <p class="font-manrope text-sm text-[#444746]">{{ $review->created_at?->format('M j, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">★ {{ $review->rating }}/5</p>
                            </div>
                        </div>
                        <p class="mt-2 font-manrope text-[#1a1c1c]">{{ $review->body ?: 'No comment provided.' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Disputes -->
            @if($disputes->count() > 0)
            <div class="surface-card border-2 border-red-200 p-6">
                <h2 class="mb-4 text-xl font-bold text-red-700">Disputes ({{ $disputes->count() }})</h2>
                <div class="editorial-list">
                    @foreach($disputes as $dispute)
                    <div class="bg-red-50 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $dispute->reporter?->name ?? 'Unknown' }}</p>
                                <p class="font-manrope text-sm text-[#444746]">{{ $dispute->subject }}</p>
                                <p class="mt-1 font-manrope text-sm text-[#1a1c1c]">{{ $dispute->description }}</p>
                            </div>
                            <span class="badge bg-red-100 text-red-700">{{ ucfirst($dispute->status) }}</span>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.disputes.show', $dispute) }}" class="btn-pill btn-pill-soft text-xs">View</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Rental Requests -->
            @if($rentalRequests->count() > 0)
            <div class="surface-card p-6">
                <h2 class="mb-4 text-xl font-bold">Rental Requests ({{ $rentalRequests->count() }})</h2>
                <div class="overflow-x-auto">
                    <table class="editorial-table">
                        <thead>
                            <tr>
                                <th class="p-3 text-left">Requester</th>
                                <th class="p-3 text-left">Duration</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rentalRequests as $request)
                            <tr>
                                <td class="p-3 font-semibold">{{ $request->renter?->name ?? 'Unknown' }}</td>
                                <td class="p-3">{{ $request->rental_duration ?? 'N/A' }} days</td>
                                <td class="p-3">
                                    <span class="status-chip {{ $request->status === 'approved' ? 'status-success' : 'status-warning' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="p-3 font-manrope text-sm text-[#444746]">{{ $request->created_at?->format('M j, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Swap Requests -->
            @if($swapRequests->count() > 0)
            <div class="surface-card p-6">
                <h2 class="mb-4 text-xl font-bold">Swap Requests ({{ $swapRequests->count() }})</h2>
                <div class="overflow-x-auto">
                    <table class="editorial-table">
                        <thead>
                            <tr>
                                <th class="p-3 text-left">Requester</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($swapRequests as $swap)
                            <tr>
                                <td class="p-3 font-semibold">{{ $swap->requester?->name ?? 'Unknown' }}</td>
                                <td class="p-3">
                                    <span class="status-chip {{ $swap->status === 'accepted' ? 'status-success' : 'status-warning' }}">
                                        {{ ucfirst(str_replace('_', ' ', $swap->status)) }}
                                    </span>
                                </td>
                                <td class="p-3 font-manrope text-sm text-[#444746]">{{ $swap->created_at?->format('M j, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-4">
            <div class="surface-card p-4">
                <h3 class="mb-3 font-bold">Actions</h3>
                <div class="space-y-2">
                    @if(!$product->flagged)
                    <form method="POST" action="{{ route('admin.products.flag', $product) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-pill w-full justify-center !border-amber-600 !text-amber-600 hover:!bg-amber-600 hover:!text-white">
                            Flag Product
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.products.unflag', $product) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-pill btn-pill-soft w-full justify-center">
                            Unflag Product
                        </button>
                    </form>
                    @endif

                    @if($canDelete)
                        <form method="POST" action="{{ route('admin.products.delete', $product) }}" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-pill w-full justify-center !border-red-600 !text-red-600 hover:!bg-red-600 hover:!text-white">
                                Delete Product
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn-pill w-full justify-center !border-neutral-400 !text-neutral-400 cursor-not-allowed" title="{{ $deleteBlockerMessage }}" disabled>
                            Locked
                        </button>
                        <p class="text-xs text-red-600">{{ $deleteBlockerMessage }}</p>

                        @if($canForceDelete)
                            <form method="POST" action="{{ route('admin.products.forceDelete', $product) }}" onsubmit="return confirm('Force delete this product? This bypasses safety guards and cannot be undone.');" class="space-y-2 border border-red-300 bg-red-50 p-3">
                                @csrf
                                @method('DELETE')
                                <label for="force-delete-reason" class="block text-xs font-semibold text-red-700">Force-delete reason (required)</label>
                                <textarea id="force-delete-reason" name="reason" rows="3" minlength="10" maxlength="500" required class="w-full border border-red-300 bg-white px-2 py-1 text-sm" placeholder="Explain why this guarded product must be force-deleted."></textarea>
                                <button type="submit" class="btn-pill w-full justify-center !border-red-700 !text-red-700 hover:!bg-red-700 hover:!text-white">
                                    Force Delete (Super Admin)
                                </button>
                            </form>
                        @endif
                    @endif

                    <a href="{{ route('admin.users.show', $product->user) }}" class="btn-pill btn-pill-soft w-full justify-center text-center">
                        View Seller
                    </a>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="surface-card p-4">
                <h3 class="mb-3 font-bold">Summary</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Total Orders:</span>
                        <span class="font-semibold">{{ $product->orders->count() }}</span>
                    </div>
                    <div class="flex justify-between pt-2 bg-[var(--reloop-surface-low)] px-2">
                        <span class="text-neutral-600">Reviews:</span>
                        <span class="font-semibold">{{ $reviews->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Disputes:</span>
                        <span class="font-semibold text-red-600">{{ $disputes->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Rental Requests:</span>
                        <span class="font-semibold">{{ $rentalRequests->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Swap Requests:</span>
                        <span class="font-semibold">{{ $swapRequests->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
