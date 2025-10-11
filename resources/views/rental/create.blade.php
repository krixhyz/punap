<x-app-layout>
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

                @php
                    $rental = $product->rentals()->first();
                @endphp

                @if($rental)
                    <p class="text-xs mb-1"><strong>Rent Type:</strong> {{ ucfirst($rental->rent_type) }}</p>
                    <p class="text-xs mb-1"><strong>Rent Fare:</strong> Rs. {{ $rental->rent_fare }} per {{ $rental->rent_type }}</p>
                    <p class="text-xs mb-1"><strong>Deposit:</strong> Rs. {{ $rental->rent_deposit }}</p>
                    <p class="text-xs mb-1"><strong>Available Duration:</strong> {{ $rental->duration }} {{ $rental->rent_type }}(s)</p>
                @else
                    <p class="text-xs text-red-500 mb-1">Rental information unavailable</p>
                @endif

                <form action="{{ route('rental.store', $product->id) }}" method="POST" class="mt-2" id="rentalForm"
                      data-rent-fare="{{ $rental ? $rental->rent_fare : 0 }}"
                      data-rent-deposit="{{ $rental ? $rental->rent_deposit : 0 }}"
                      data-max-duration="{{ $rental ? $rental->duration : 100 }}"
                      data-rent-type="{{ $rental ? $rental->rent_type : 'daily' }}">
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

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs w-full">Proceed to Checkout</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    const form = document.getElementById('rentalForm');
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    const totalAmount = document.getElementById('totalAmount');

    if (form && startInput && endInput && totalAmount) {
        const rentFare = parseFloat(form.dataset.rentFare) || 0;
        const rentDeposit = parseFloat(form.dataset.rentDeposit) || 0;
        const maxDuration = parseInt(form.dataset.maxDuration) || 1;
        const rentType = form.dataset.rentType || 'daily';

        const today = new Date().toISOString().split('T')[0];
        startInput.setAttribute('min', today);

        function updateEndDateConstraints() {
            if (!startInput.value) return;

            const startDate = new Date(startInput.value);
            let maxEndDate = new Date(startDate);

            if (rentType === 'daily') {
                maxEndDate.setDate(startDate.getDate() + maxDuration - 1);
            } else {
                // For hourly, still keep day-level constraint for date picker
                maxEndDate.setDate(startDate.getDate() + Math.ceil(maxDuration / 24) - 1);
            }

            const yyyy = maxEndDate.getFullYear();
            const mm = String(maxEndDate.getMonth() + 1).padStart(2, '0');
            const dd = String(maxEndDate.getDate()).padStart(2, '0');

            endInput.setAttribute('min', startInput.value);
            endInput.setAttribute('max', `${yyyy}-${mm}-${dd}`);

            if (endInput.value) {
                const selected = new Date(endInput.value);
                if (selected > maxEndDate) {
                    endInput.value = `${yyyy}-${mm}-${dd}`;
                }
            }
        }

        function updateTotal() {
            if (!startInput.value || !endInput.value) {
                totalAmount.textContent = 'Rs. 0';
                return;
            }

            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            let diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

            if (diffDays > maxDuration) diffDays = maxDuration;

            totalAmount.textContent = `Rs. ${(rentFare * diffDays + rentDeposit).toFixed(2)}`;
        }

        startInput.addEventListener('change', () => {
            updateEndDateConstraints();
            updateTotal();
        });

        endInput.addEventListener('change', updateTotal);

        // initialize constraints on page load
        if (startInput.value) updateEndDateConstraints();
    }
</script>

</x-app-layout>