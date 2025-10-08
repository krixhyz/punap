<x-app-layout>
    <div class="max-w-5xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6 text-center">Your Cart 🛒</h1>

        @if($cartItems->isEmpty())
            <p class="text-center text-gray-500">Your cart is empty.</p>
        @else
            <div class="space-y-4">
                @foreach ($cartItems as $item)
                    <div class="bg-white shadow rounded-lg p-4 flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->title }}" class="w-16 h-16 object-cover rounded">
                            <div>
                                <h2 class="font-semibold">{{ $item->product->title }}</h2>
                                <p class="text-gray-500 text-sm">Rs. {{ $item->product->price }}</p>
                                <p class="text-xs text-gray-400 capitalize">Type: {{ $item->type }}</p>
                            </div>
                        </div>
                        <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Remove</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
