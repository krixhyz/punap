@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg p-4 mx-auto" style="max-width: 600px;">
        <h2 class="text-center mb-4">Rental Checkout</h2>

        <table class="table table-bordered">
            <tr>
                <th>Product</th>
                <td>{{ $rentalRequest->product->title }}</td>
            </tr>
            <tr>
                <th>Rent Type</th>
                <td>{{ ucfirst($rental->rent_type) }}</td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>{{ $rentalRequest->duration }} {{ $rental->rent_type == 'hourly' ? 'hours' : 'days' }}</td>
            </tr>
            <tr>
                <th>Fare</th>
                <td>Rs. {{ $rental->rent_fare }}</td>
            </tr>
            <tr>
                <th>Deposit</th>
                <td>Rs. {{ $rental->rent_deposit }}</td>
            </tr>
            <tr class="table-info">
                <th>Total Amount</th>
                <td><strong>Rs. {{ $rentalRequest->total_amount + $rental->rent_deposit }}</strong></td>
            </tr>
        </table>

        <div class="alert alert-warning text-center mt-3">
            <strong>Note:</strong> Payment integration is coming soon. Your request has been sent to the owner for approval.
        </div>

        <a href="{{ route('products.index') }}" class="btn btn-success w-100 mt-3">Back to Products</a>
    </div>
</div>
@endsection
