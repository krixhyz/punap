@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-4 sm:px-4 lg:px-6">
    <h2 class="text-xl font-bold mb-3 text-center">Rent: {{ $product->title }}</h2>

    <div class="bg-white shadow-sm rounded-md mx-auto flex flex-col md:flex-row overflow-hidden product-card max-w-[500px]">
        {{-- Product Image --}}
        <div class="md:w-1/3 p-2 text-center">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" 
                     class="w-full h-24 object-contain rounded product-image" 
                     alt="{{ $product->title }}">
            @else
                <div class="w-full h-24 bg-gray-200 flex items-center justify-center text-gray-500 text-xs rounded">
                    No Image
                </div>
            @endif
        </div>

        {{-- Product Info & Form --}}
        <div class="md:w-2/3 p-3">
            <h5 class="text-sm font-medium">{{ $product->title }}</h5>
            <p class="text-gray-600 text-xs mt-1">{{ Str::limit($product->description, 100) }}</p>
            <p class="text-xs mb-1"><strong>Category:</strong> {{ $product->category ?? 'General' }}</p>
            <p class="text-xs mb-1"><strong>Available Quantity:</strong> {{ $product->quantity }}</p>
            <p class="text-[11px] text-gray-500 mb-2">Note: Renting is limited to one unit per request.</p>

            @php
                $rental = $product->rentals()->first();
                $ownerEndDate = null;
                if ($rental && $rental->available_from && $rental->available_duration) {
                    $start = \Carbon\Carbon::parse($rental->available_from);
                    $ownerEndDate = $start->copy()->addDays($rental->available_duration - 1)->format('Y-m-d');
                }
            @endphp

            @if($rental)
                <p class="text-xs mb-1"><strong>Rent Fare:</strong> Rs. {{ $rental->rent_fare }} per day</p>
                <p class="text-xs mb-1"><strong>Deposit:</strong> Rs. {{ $rental->rent_deposit }}</p>
                <p class="text-xs mb-1"><strong>Available Duration:</strong> 
                    {{ $rental->available_duration ? $rental->available_duration . ' day(s)' : 'Not set' }}
                </p>
                <p class="text-xs mb-1"><strong>Available From:</strong> 
                    {{ $rental->available_from ? \Carbon\Carbon::parse($rental->available_from)->format('Y-m-d') : 'Not set' }}
                </p>
                <p class="text-xs mb-1"><strong>Available Until:</strong> 
                    {{ $ownerEndDate ?? 'Not set' }}
                </p>
            @else
                <p class="text-xs text-red-500 mb-1">Rental information unavailable</p>
            @endif

            <form action="{{ route('rental.store', $product->id) }}" method="POST" class="mt-2" id="rentalForm"
                  data-rent-fare="{{ $rental ? $rental->rent_fare : 0 }}"
                  data-rent-deposit="{{ $rental ? $rental->rent_deposit : 0 }}"
                  data-max-duration="{{ $rental ? $rental->duration : 100 }}"
                  data-owner-start-date="{{ $rental && $rental->available_from ? \Carbon\Carbon::parse($rental->available_from)->format('Y-m-d') : '' }}"
                  data-owner-end-date="{{ $ownerEndDate }}">
                @csrf

                <div class="mb-2">
                    <label class="form-label text-xs font-bold">Start Date</label>
                    <input type="date" name="start_date" id="startDate" class="form-control text-xs p-1.5 rounded" required>
                </div>

                <div class="mb-2">
                    <label class="form-label text-xs font-bold">End Date</label>
                    <input type="date" name="end_date" id="endDate" class="form-control text-xs p-1.5 rounded" required>
                </div>

                <div class="mb-2 p-2 bg-gray-100 rounded">
                    <p class="mb-0 text-xs font-bold">Estimated Total:</p>
                    <p id="totalAmount" class="text-sm text-blue-600">Rs. 0</p>
                </div>

                {{-- Hidden Inputs --}}
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="rent_fare" id="rentFare" value="{{ $rental ? $rental->rent_fare : 0 }}">
                <input type="hidden" name="rent_deposit" id="rentDeposit" value="{{ $rental ? $rental->rent_deposit : 0 }}">
                <input type="hidden" name="duration" id="duration" value="0">
                <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs w-full">
                    Request Rent
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const form = document.getElementById('rentalForm');
const startInput = document.getElementById('startDate');
const endInput = document.getElementById('endDate');
const totalAmountDisplay = document.getElementById('totalAmount');

const rentFareInput = document.getElementById('rentFare');
const rentDepositInput = document.getElementById('rentDeposit');
const durationInput = document.getElementById('duration');
const totalAmountInput = document.getElementById('totalAmountInput');

if (form && startInput && endInput && totalAmountDisplay) {
    const rentFare = parseFloat(form.dataset.rentFare) || 0;
    const rentDeposit = parseFloat(form.dataset.rentDeposit) || 0;
    const maxDuration = parseInt(form.dataset.maxDuration) || 100;
    const ownerStartDate = form.dataset.ownerStartDate || '';
    const ownerEndDate = form.dataset.ownerEndDate || '';

    const today = new Date().toISOString().split('T')[0];
    startInput.setAttribute('min', ownerStartDate || today);
    startInput.setAttribute('max', ownerEndDate);

    function updateTotal() {
        if (!startInput.value || !endInput.value) {
            totalAmountDisplay.textContent = 'Rs. 0';
            durationInput.value = 0;
            totalAmountInput.value = 0;
            return;
        }

        let start = new Date(startInput.value);
        let end = new Date(endInput.value);
        const maxEnd = new Date(ownerEndDate);

        if (end > maxEnd) end = maxEnd;

        let diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        if (diffDays > maxDuration) diffDays = maxDuration;

        const total = (rentFare * diffDays + rentDeposit).toFixed(2);

        totalAmountDisplay.textContent = `Rs. ${total}`;
        durationInput.value = diffDays;
        rentFareInput.value = rentFare;
        rentDepositInput.value = rentDeposit;
        totalAmountInput.value = total;
    }

    startInput.addEventListener('change', () => {
        const start = new Date(startInput.value);
        const maxEnd = new Date(start);
        maxEnd.setDate(maxEnd.getDate() + parseInt(maxDuration) - 1);

        const ownerEnd = new Date(ownerEndDate);
        const finalMax = maxEnd < ownerEnd ? maxEnd : ownerEnd;

        endInput.min = startInput.value;
        endInput.max = finalMax.toISOString().split('T')[0];

        const currentEnd = new Date(endInput.value);
        if (currentEnd > finalMax) {
            endInput.value = endInput.max;
        }

        updateTotal();
    });

    endInput.addEventListener('change', updateTotal);

    form.addEventListener('submit', () => {
        updateTotal();
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    });

    if (startInput.value && endInput.value) {
        updateTotal();
    }
}
</script>
@endsection
