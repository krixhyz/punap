@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto mt-8 bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
    <h2 class="font-semibold text-2xl text-gray-800 text-center mb-6">Edit Listing</h2>

    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')
        @include('products.form', ['buttonText' => 'Update Listing'])
    </form>
</div>
@endsection
