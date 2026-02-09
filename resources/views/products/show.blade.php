@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('products.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Products
        </a>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
                {{-- Product Image --}}
                <div class="space-y-4">
                    <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" 
                                 alt="{{ $product->title }}" 
                                 class="w-full h-full object-cover">
                        @else
                            <div class="flex items-center justify-center h-full text-gray-400">
                                <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 
                                          012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 
                                          00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Badges --}}
                    <div class="flex flex-wrap gap-2">
                        @if(in_array('sell', $product->type))
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">Available for Sale</span>
                        @endif
                        @if(in_array('rent', $product->type))
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">Available for Rent</span>
                        @endif
                        @if(in_array('swap', $product->type))
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Available for Swap</span>
                        @endif
                    </div>
                </div>

                {{-- Product Details --}}
                <div class="space-y-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $product->title }}</h1>
                        <p class="text-sm text-gray-500 capitalize">Category: {{ $product->category }}</p>
                    </div>

                    @if(in_array('sell', $product->type))
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-blue-700">Rs. {{ number_format($product->price, 2) }}</span>
                            <span class="text-gray-500">per unit</span>
                        </div>
                    @endif

                    <div class="border-t border-b py-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Available Quantity:</span>
                            <span class="font-semibold">{{ $product->quantity }} units</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Owner:</span>
                            <span class="font-semibold">{{ $product->user->name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Listed:</span>
                            <span class="font-semibold">{{ $product->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Description</h3>
                        <p class="text-gray-700 leading-relaxed">{{ $product->description }}</p>
                    </div>

                    @auth
                        @if(Auth::id() !== $product->user_id && $product->status === 'available')
                            <div class="space-y-4 pt-4">
                                {{-- Buy Section --}}
                                @if(in_array('sell', $product->type) && $product->quantity > 0)
                                    <div class="bg-blue-50 p-4 rounded-xl">
                                        <h4 class="font-semibold text-gray-900 mb-3">Purchase</h4>
                                        <form action="{{ route('order.store', $product->id) }}" method="POST" class="space-y-3">
                                            @csrf
                                            <div>
                                                <label class="block text-sm text-gray-700 mb-1">Quantity</label>
                                                <input type="number" name="quantity" min="1" max="{{ $product->quantity }}" value="1"
                                                       class="w-full border-gray-300 rounded-lg">
                                            </div>
                                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition">
                                                Buy Now
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                {{-- Rent --}}
                                @if(in_array('rent', $product->type))
                                    <a href="{{ route('rental.create', $product->id) }}" 
                                       class="block w-full bg-yellow-500 hover:bg-yellow-600 text-black font-semibold py-3 rounded-lg text-center transition">
                                        Request Rental
                                    </a>
                                @endif

                                {{-- Swap --}}
                                @if(in_array('swap', $product->type))
                                    <form action="{{ route('swap.request.form', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition">
                                            Propose Swap
                                        </button>
                                    </form>
                                @endif

                                {{-- Add to Cart --}}
                                @if(in_array('sell', $product->type) && $product->quantity > 0)
                                    <form action="{{ route('cart.store', $product->id) }}" method="POST" class="flex gap-2">
                                        @csrf
                                        <input type="number" name="quantity" min="1" max="{{ $product->quantity }}" value="1"
                                               class="flex-1 border-gray-300 rounded-lg">
                                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg transition">
                                            🛒 Add to Cart
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <p class="text-red-700 font-medium">Please login to purchase, rent, or swap this product</p>
                            <a href="{{ route('login') }}" class="text-red-800 underline mt-2 inline-block">Login here</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
