@php
    $footerUser = auth()->user();
    $footerIsAdminUser = $footerUser && ($footerUser->isAdmin() || $footerUser->isSuperAdmin());
@endphp

<footer class="mt-16 border-t border-[rgba(189,202,189,0.35)] bg-[#e9ecef] text-[#1a1c1c]">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-8 sm:grid-cols-2 md:grid-cols-5">
            <div>
                <h3 class="font-space text-sm font-bold uppercase tracking-[0.12em] text-[#1a1c1c]">Quick Links</h3>
                <ul class="mt-4 space-y-3 text-sm text-[#2f3234]">
                    <li><a href="{{ route('landing') }}" class="transition-colors hover:text-[#006a38]">Home</a></li>
                    <li><a href="{{ route('products.index') }}" class="transition-colors hover:text-[#006a38]">Marketplace</a></li>
                    <li><a href="{{ route('cart.index') }}" class="transition-colors hover:text-[#006a38]">Cart</a></li>
                    <li><a href="{{ route('wishlist.index') }}" class="transition-colors hover:text-[#006a38]">Wishlist</a></li>
                    <li><a href="{{ $footerIsAdminUser ? route('admin.dashboard') : route('dashboard') }}" class="transition-colors hover:text-[#006a38]">Dashboard</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-space text-sm font-bold uppercase tracking-[0.12em] text-[#1a1c1c]">Explore</h3>
                <ul class="mt-4 space-y-3 text-sm text-[#2f3234]">
                    @auth
                        <li><a href="{{ route('products.create') }}" class="transition-colors hover:text-[#006a38]">Create Listing</a></li>
                        <li><a href="{{ route('products.myListings') }}" class="transition-colors hover:text-[#006a38]">My Listings</a></li>
                        <li><a href="{{ route('products.myPurchases') }}" class="transition-colors hover:text-[#006a38]">My Orders</a></li>
                        <li><a href="{{ route('rental.myRentals') }}" class="transition-colors hover:text-[#006a38]">My Rentals</a></li>
                        <li><a href="{{ route('swap.mySwaps') }}" class="transition-colors hover:text-[#006a38]">My Swaps</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="transition-colors hover:text-[#006a38]">Log In</a></li>
                        <li><a href="{{ route('register') }}" class="transition-colors hover:text-[#006a38]">Create Account</a></li>
                        <li><a href="{{ route('products.index') }}" class="transition-colors hover:text-[#006a38]">Browse Listings</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h3 class="font-space text-sm font-bold uppercase tracking-[0.12em] text-[#1a1c1c]">Categories</h3>
                <ul class="mt-4 space-y-3 text-sm text-[#2f3234]">
                    <li><a href="{{ route('products.index', ['category' => 'Electronics']) }}" class="transition-colors hover:text-[#006a38]">Electronics</a></li>
                    <li><a href="{{ route('products.index', ['category' => 'Books']) }}" class="transition-colors hover:text-[#006a38]">Books</a></li>
                    <li><a href="{{ route('products.index', ['category' => 'Clothing']) }}" class="transition-colors hover:text-[#006a38]">Clothing</a></li>
                    <li><a href="{{ route('products.index', ['mode' => 'buy']) }}" class="transition-colors hover:text-[#006a38]">Buy</a></li>
                    <li><a href="{{ route('products.index', ['mode' => 'rent']) }}" class="transition-colors hover:text-[#006a38]">Rent</a></li>
                    <li><a href="{{ route('products.index', ['mode' => 'swap']) }}" class="transition-colors hover:text-[#006a38]">Swap</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-space text-sm font-bold uppercase tracking-[0.12em] text-[#1a1c1c]">Legal</h3>
                <ul class="mt-4 space-y-3 text-sm text-[#2f3234]">
                    <li><a href="{{ $footerIsAdminUser ? route('admin.profile.edit') : route('profile.edit') }}" class="transition-colors hover:text-[#006a38]">Account Settings</a></li>
                    <li><a href="{{ route('dispute.my') }}" class="transition-colors hover:text-[#006a38]">My Disputes</a></li>
                    <li><a href="{{ route('notifications.index') }}" class="transition-colors hover:text-[#006a38]">Notifications</a></li>
                    <li><a href="{{ route('review.create') }}" class="transition-colors hover:text-[#006a38]">Submit Review</a></li>
                    <li><a href="{{ route('dispute.create') }}" class="transition-colors hover:text-[#006a38]">Report Issue</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-space text-sm font-bold uppercase tracking-[0.12em] text-[#1a1c1c]">Contact</h3>
                <div class="mt-4 space-y-3 text-sm leading-relaxed text-[#2f3234]">
                    <p>Reloop Marketplace</p>
                    <p>Pokhara, Nepal</p>
                    <p><a href="tel:+9779864426265" class="transition-colors hover:text-[#006a38]">9864426265</a></p>
                    <p><a href="https://reloop-np.me" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-[#006a38]">reloop-np.me</a></p>
                </div>
            </div>

        </div>

        <div class="mt-12 flex flex-col items-start justify-between gap-3 border-t border-[rgba(189,202,189,0.45)] pt-6 text-sm text-[#2f3234] md:flex-row md:items-center">
            <p>© {{ date('Y') }} reloop-np.me | All Rights Reserved.</p>
        </div>
    </div>
</footer>
