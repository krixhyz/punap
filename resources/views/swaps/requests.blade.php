@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto py-10 text-gray-200">
        <h2 class="text-2xl font-semibold mb-8 text-center">Incoming Swap Requests</h2>

        @if($requests->isEmpty())
            <p class="text-gray-400 text-center">No pending swap requests at the moment.</p>
        @else
            <div class="space-y-6">
                @foreach ($requests as $req)
                    <div class="bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-700 hover:border-gray-600 transition">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6">
                            <!-- Left side: Product cards -->
                            <div class="flex flex-col md:flex-row gap-6 w-full">
                                <!-- Offered Product -->
                                <div class="bg-gray-900 rounded-xl p-4 w-full md:w-1/2 shadow-inner">
                                    <h3 class="text-lg font-semibold text-blue-400 mb-2">
                                        {{ $req->offeredProduct ? $req->offeredProduct->title : 'Cash Offer' }}
                                    </h3>

                                    @if($req->offeredProduct && $req->offeredProduct->image)
                                        <img src="{{ asset('storage/' . $req->offeredProduct->image) }}" alt="Offered Product" class="rounded-lg mb-3 w-full h-48 object-cover">
                                    @endif

                                    @if($req->offeredProduct)
                                        <p class="text-sm text-gray-300 line-clamp-3">
                                            {{ Str::limit($req->offeredProduct->description, 100) }}
                                        </p>
                                    @else
                                        <p class="italic text-gray-400">User offers money instead of an item.</p>
                                    @endif

                                    @if($req->offered_amount)
                                        <p class="text-gray-300 mt-2">
                                            <span class="font-semibold text-green-400">+ ${{ $req->offered_amount }}</span> offered
                                        </p>
                                    @endif
                                </div>

                                <!-- Target Product -->
                                <div class="bg-gray-900 rounded-xl p-4 w-full md:w-1/2 shadow-inner">
                                    <h3 class="text-lg font-semibold text-green-400 mb-2">
                                        {{ $req->product->title }}
                                    </h3>

                                    @if($req->product->image)
                                        <img src="{{ asset('storage/' . $req->product->image) }}" alt="Target Product" class="rounded-lg mb-3 w-full h-48 object-cover">
                                    @endif

                                    <p class="text-sm text-gray-300 line-clamp-3">
                                        {{ Str::limit($req->product->description, 100) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right side: Details & Buttons -->
                            <div class="flex flex-col justify-between gap-4 w-full md:w-1/3">
                                <div>
                                    <p class="text-gray-100 font-medium">
                                        <span class="text-blue-400">{{ $req->requester->name }}</span> wants to swap
                                    </p>

                                    @if($req->message)
                                        <p class="text-gray-400 mt-2 italic border-l-2 border-gray-600 pl-3">
                                            "{{ $req->message }}"
                                        </p>
                                    @endif
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <form action="{{ route('swap.request.accept', $req) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-500 hover:to-emerald-400 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition transform hover:scale-105 shadow">
                                             Accept
                                        </button>
                                    </form>
                                    <form action="{{ route('swap.request.reject', $req) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="bg-gradient-to-r from-red-600 to-pink-500 hover:from-red-500 hover:to-pink-400 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition transform hover:scale-105 shadow">
                                             Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
