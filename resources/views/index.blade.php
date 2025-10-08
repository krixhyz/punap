<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-4 text-center">All Product Listings</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($products as $product)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" 
                             alt="{{ $product->title }}" 
                             class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-500">
                            No Image
                        </div>
                    @endif

                    <div class="p-4">
                        <h2 class="text-lg font-semibold">{{ $product->title }}</h2>
                        <p class="text-gray-600 text-sm">{{ Str::limit($product->description, 80) }}</p>
                        <p class="font-bold mt-2">Rs. {{ $product->price }}</p>
                        <p class="text-sm text-gray-500 capitalize mt-1">{{ $product->category }}</p>

                        @auth
                            <div class="mt-3 flex flex-wrap gap-2">
                                {{-- Buy Option --}}
                                @if(in_array('sell', $product->type))
                                    <form action="{{ route('order.store', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="buy">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                            Buy
                                        </button>
                                    </form>
                                @endif

                                {{-- Rent Option --}}
                                @if(in_array('rent', $product->type))
                                    <form action="{{ route('order.store', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="rent">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                            Rent
                                        </button>
                                    </form>
                                @endif

                                {{-- Swap Option --}}
                                @if(in_array('swap', $product->type))
                                    <form action="{{ route('order.store', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="swap">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                            Swap
                                        </button>
                                    </form>
                                @endif
                                
                            <form action="{{ route('cart.store', $product->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="buy">
                                <button class="bg-gray-800 hover:bg-gray-900 text-white px-3 py-1 rounded text-sm">
                                    Add to Cart 🛒
                                </button>
                            </form>

                        @else
                            <p class="text-sm text-red-500 mt-3">Login to Buy / Rent / Swap</p>
                        @endauth
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
