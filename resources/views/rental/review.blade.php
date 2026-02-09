@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg p-4 mx-auto" style="max-width: 700px;">
        <h2 class="text-center mb-4">Rental Request Review</h2>

        <table class="table table-bordered">
            <tr><th>Product</th><td>{{ $rental->product->title }}</td></tr>
            <tr><th>Renter</th><td>{{ $rental->renter->name }}</td></tr>
            <tr><th>Duration</th><td>{{ $rental->duration }} days</td></tr>
            <tr><th>Fare</th><td>Rs. {{ $rental->rent_fare }}</td></tr>
            <tr><th>Deposit</th><td>Rs. {{ $rental->rent_deposit }}</td></tr>
            <tr class="table-info">
                <th>Total Amount</th>
                <td><strong>Rs. {{ $rental->total_amount + $rental->rent_deposit }}</strong></td>
            </tr>
            <tr><th>Start Date</th><td>{{ $rental->start_date }}</td></tr>
            <tr><th>End Date</th><td>{{ $rental->end_date }}</td></tr>
        </table>

        <div class="text-center mt-4 d-flex justify-content-center gap-3">
            <!-- Approve Form -->
            <form action="{{ route('rental.approve', $rental->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success">Approve</button>
            </form>

            <!-- Reject Form -->
            <form action="{{ route('rental.reject', $rental->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-danger">Reject</button>
            </form>
        </div>
    </div>
</div>
@endsection
