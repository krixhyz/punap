<x-app-layout>
    <div class="container mt-5">
        <div class="card shadow-lg p-4 mx-auto" style="max-width: 600px;">
            <h2 class="text-center mb-4">Proceed to Payment</h2>

            <table class="table table-bordered">
                <tr><th>Product</th><td>{{ $rental->product->title }}</td></tr>
                <tr><th>Owner</th><td>{{ $rental->product->owner->name }}</td></tr>
                <tr><th>Fare</th><td>Rs. {{ $rental->rent_fare }}</td></tr>
                <tr><th>Deposit</th><td>Rs. {{ $rental->rent_deposit }}</td></tr>
                <tr class="table-info"><th>Total</th><td><strong>Rs. {{ $rental->total_amount + $rental->rent_deposit }}</strong></td></tr>
            </table>

            <div class="alert alert-success text-center mt-3">
                <strong>Request Approved!</strong> Please complete payment to start your rental.
            </div>

            <a href="{{ route('products.index') }}" class="btn btn-primary w-100 mt-3">Proceed to Payment (Coming Soon)</a>
        </div>
    </div>
</x-app-layout>
