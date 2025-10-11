<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 sm:px-4 lg:px-6">
        <h1 class="text-xl font-bold mb-3 text-center">All Product Listings</h1>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 product-grid">
            @foreach ($products as $product)
                <div class="bg-white shadow-sm rounded-md overflow-hidden flex flex-col max-w-[140px] min-w-0 product-card">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" 
                             alt="{{ $product->title }}" 
                             class="w-full h-20 object-contain product-image">
                    @else
                        <div class="w-full h-20 bg-gray-200 flex items-center justify-center text-gray-500 text-xs">
                            No Image
                        </div>
                    @endif

                    <div class="p-2 flex flex-col flex-grow">
                        <h2 class="text-xs font-medium truncate">{{ $product->title }}</h2>
                        <p class="text-gray-600 text-xs mt-1 truncate">{{ Str::limit($product->description, 30) }}</p>
                        <p class="font-bold text-xs mt-1">Rs. {{ $product->price }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ $product->category }}</p>

                        @auth
                            <div class="mt-2 flex flex-wrap gap-1">
                                @if(in_array('sell', $product->type))
                                    <form action="{{ route('order.store', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="buy">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-0.5 rounded text-xs">
                                            Buy
                                        </button>
                                    </form>
                                @endif

                                @if(in_array('rent', $product->type))
    <a href="{{ route('rental.create', $product->id) }}" 
       class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-0.5 rounded text-xs inline-block text-center">
       Rent
    </a>
@endif

                                @if(in_array('swap', $product->type))
                                    <form action="{{ route('order.store', $product->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="swap">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-0.5 rounded text-xs">
                                            Swap
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('cart.store', $product->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="buy">
                                    <button class="bg-gray-800 hover:bg-gray-900 text-white px-2 py-0.5 rounded text-xs">
                                        🛒
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-xs text-red-500 mt-1">Login to Buy/Rent/Swap</p>
                        @endauth
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>