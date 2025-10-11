<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Add New Listing</h2>
    </x-slot>

    <div class="max-w-[500px] mx-auto mt-4">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf

            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" class="w-full border-gray-300 rounded-md text-sm p-1.5" required>
                @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" class="w-full border-gray-300 rounded-md text-sm p-1.5" required></textarea>
                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category" class="w-full border-gray-300 rounded-md text-sm p-1.5" required>
                    <option value="">Select Category</option>
                    <option value="electronics">Electronics</option>
                    <option value="clothing">Clothing</option>
                    <option value="furniture">Furniture</option>
                    <option value="general">General</option>
                </select>
                @error('category') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Price (for selling only) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Price (For Sell)</label>
                <input type="number" name="price" step="0.01" class="w-full border-gray-300 rounded-md text-sm p-1.5">
                @error('price') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Listing Type --}}
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Listing Type</label>
                <div class="flex flex-wrap gap-3">
                    <label class="flex items-center"><input type="checkbox" name="listing_type[]" value="sell" class="mr-1"> Sell</label>
                    <label class="flex items-center"><input type="checkbox" name="listing_type[]" value="rent" id="rentCheckbox" class="mr-1"> Rent</label>
                    <label class="flex items-center"><input type="checkbox" name="listing_type[]" value="swap" class="mr-1"> Swap</label>
                </div>
                @error('listing_type') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Rent Section (shown only if Rent is checked) --}}
            <div id="rentSection" class="hidden space-y-3 border p-3 rounded-md bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-800">Rental Details</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Rent Deposit</label>
                    <input type="number" step="0.01" name="rent_deposit" id="rentDeposit"
                           class="w-full border-gray-300 rounded-md text-sm p-1.5">
                    @error('rent_deposit') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Rent Fare</label>
                    <input type="number" step="0.01" name="rent_fare" id="rentFare"
                           class="w-full border-gray-300 rounded-md text-sm p-1.5">
                    @error('rent_fare') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" name="start_date" id="startDate"
                           class="w-full border-gray-300 rounded-md text-sm p-1.5">
                    @error('start_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" id="endDate"
                           class="w-full border-gray-300 rounded-md text-sm p-1.5">
                    @error('end_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Available Duration (<span id="durationUnit">days</span>)</label>
                    <input type="number" name="rent_duration" id="rentDuration"
                           class="w-full border-gray-300 rounded-md text-sm p-1.5" min="1" readonly>
                    @error('rent_duration') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Image --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Image</label>
                <input type="file" name="image" class="w-full text-sm">
                @error('image') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm w-full">Add Listing</button>
        </form>
    </div>

    <script>
        const rentCheckbox = document.getElementById('rentCheckbox');
        const rentSection = document.getElementById('rentSection');
        const rentType = document.getElementById('rentType');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const rentDuration = document.getElementById('rentDuration');
        const durationUnit = document.getElementById('durationUnit');

        const today = new Date();
        const formattedToday = today.toISOString().split('T')[0];
        startDate.setAttribute('min', formattedToday);
        endDate.setAttribute('min', formattedToday);

        startDate.addEventListener('change', function () {
            endDate.setAttribute('min', startDate.value);
            updateDuration();
        });

        // Show/hide rent section
        rentCheckbox.addEventListener('change', function() {
            rentSection.classList.toggle('hidden', !this.checked);
        });

        function updateDuration() {
            if (!startDate.value || !endDate.value) {
                rentDuration.value = '';
                return;
            }

            const start = new Date(startDate.value);
            const end = new Date(endDate.value);

            if (end < start) {
                alert('End date cannot be before start date');
                endDate.value = startDate.value;
            }

            const diffTime = end - start;
            const duration = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // inclusive count
            rentDuration.value = Math.max(1, duration);
        }

        endDate.addEventListener('change', updateDuration);
    </script>
</x-app-layout>
