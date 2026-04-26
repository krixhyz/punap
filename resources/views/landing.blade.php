<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
    <title>Reloop | Circular Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .intro-fade {
            animation: introFade 0.7s ease-out both;
        }

        .intro-rise {
            animation: introRise 0.7s ease-out both;
        }

        .intro-rise.delay-1 { animation-delay: 0.08s; }
        .intro-rise.delay-2 { animation-delay: 0.16s; }
        .intro-rise.delay-3 { animation-delay: 0.24s; }

        @keyframes introFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes introRise {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[#f2f6f1] font-manrope text-[#1a1c1c]">
    @include('layouts.navigation')

    <main class="relative overflow-hidden pb-10">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-20 top-20 h-72 w-72 rounded-full bg-[radial-gradient(circle,_rgba(0,106,56,0.18)_0%,_rgba(0,106,56,0)_70%)]"></div>
            <div class="absolute right-[-4rem] top-56 h-80 w-80 rounded-full bg-[radial-gradient(circle,_rgba(255,170,0,0.14)_0%,_rgba(255,170,0,0)_70%)]"></div>
            <div class="absolute bottom-[-9rem] left-1/2 h-96 w-96 -translate-x-1/2 rounded-full bg-[radial-gradient(circle,_rgba(11,141,80,0.14)_0%,_rgba(11,141,80,0)_72%)]"></div>
        </div>

        <section class="relative mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8 lg:pt-16">
            <div class="grid items-start gap-10 lg:grid-cols-12">
                <div class="intro-fade lg:col-span-7">
                    <p class="intro-rise inline-flex items-center gap-2 rounded-full border border-[#c4d6c8] bg-white/80 px-4 py-1.5 font-space text-[11px] font-bold uppercase tracking-[0.18em] text-[#006a38]">
                        Circular Marketplace Platform
                    </p>
                    <h1 class="intro-rise delay-1 mt-5 font-space text-4xl font-bold uppercase leading-tight tracking-[0.02em] text-[#133123] sm:text-5xl lg:text-6xl">
                        A Circular Marketplace
                        <span class="block text-[#006a38]">Built For Practical Reuse</span>
                    </h1>
                    <p class="intro-rise delay-2 mt-5 max-w-2xl text-base leading-relaxed text-[#30423a] sm:text-lg">
                        ReLoop supports buy, rent, and swap workflows in one place. The goal is simple:
                        extend product life cycles, reduce unnecessary consumption, and provide a safer
                        transaction flow for local users.
                    </p>

                    <div class="intro-rise delay-3 mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('products.index') }}" class="rounded-full bg-[#006a38] px-6 py-3 font-space text-xs font-bold uppercase tracking-[0.16em] text-white transition hover:bg-[#005a2f]">
                            Explore Marketplace
                        </a>
                        @auth
                            <a href="{{ auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="rounded-full border border-[#acc2b0] bg-white px-6 py-3 font-space text-xs font-bold uppercase tracking-[0.16em] text-[#1a1c1c] transition hover:border-[#006a38] hover:text-[#006a38]">
                                Continue To Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-full border border-[#acc2b0] bg-white px-6 py-3 font-space text-xs font-bold uppercase tracking-[0.16em] text-[#1a1c1c] transition hover:border-[#006a38] hover:text-[#006a38]">
                                Create Account
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="intro-fade lg:col-span-5">
                    <div class="rounded-[2rem] border border-[#d5e2d7] bg-white/85 p-6 shadow-[0_24px_48px_rgba(19,49,35,0.11)] backdrop-blur sm:p-7">
                        <h2 class="font-space text-sm font-bold uppercase tracking-[0.16em] text-[#006a38]">At A Glance</h2>
                        <div class="mt-5 grid gap-3">
                            <div class="rounded-xl border border-[#d7e2d9] bg-[#f8fbf8] p-4">
                                <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Core Modes</p>
                                <p class="mt-2 text-sm text-[#30423a]">Buy, rent, and swap support different ownership needs.</p>
                            </div>
                            <div class="rounded-xl border border-[#d7e2d9] bg-[#f8fbf8] p-4">
                                <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Safety Layer</p>
                                <p class="mt-2 text-sm text-[#30423a]">Approval checks, transparent order status, and dispute flow.</p>
                            </div>
                            <div class="rounded-xl border border-[#d7e2d9] bg-[#f8fbf8] p-4">
                                <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Project Focus</p>
                                <p class="mt-2 text-sm text-[#30423a]">Academic prototype demonstrating circular economy principles.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-[1.75rem] border border-[#d4e1d6] bg-white/80 p-6 shadow-[0_18px_35px_rgba(19,49,35,0.08)] backdrop-blur md:p-8">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-[#dce6de] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Listings</p>
                        <p class="mt-1 text-2xl font-semibold text-[#113121]">{{ $featuredProducts->count() }}</p>
                        <p class="mt-2 text-sm text-[#30423a]">Sample featured products currently visible on the platform.</p>
                    </div>
                    <div class="rounded-xl border border-[#dce6de] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Category Coverage</p>
                        <p class="mt-1 text-2xl font-semibold text-[#113121]">{{ $topCategories->count() }}</p>
                        <p class="mt-2 text-sm text-[#30423a]">Top parent categories by available approved items.</p>
                    </div>
                    <div class="rounded-xl border border-[#dce6de] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Transaction Modes</p>
                        <p class="mt-1 text-2xl font-semibold text-[#113121]">3</p>
                        <p class="mt-2 text-sm text-[#30423a]">Buy, rent, and swap for different access and ownership preferences.</p>
                    </div>
                    <div class="rounded-xl border border-[#dce6de] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Design Goal</p>
                        <p class="mt-1 text-2xl font-semibold text-[#113121]">Trust</p>
                        <p class="mt-2 text-sm text-[#30423a]">Clear process, recorded status updates, and dispute support.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="font-space text-[11px] font-bold uppercase tracking-[0.16em] text-[#006a38]">Featured Products</p>
                    <h2 class="mt-2 font-space text-2xl font-bold uppercase tracking-[0.02em] text-[#122d22] sm:text-3xl">Recently Added Listings</h2>
                </div>
                <a href="{{ route('products.index') }}" class="rounded-full border border-[#b5c8b9] bg-white px-5 py-2 font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#1a1c1c] transition hover:border-[#006a38] hover:text-[#006a38]">View All Listings</a>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($featuredProducts as $product)
                    <article class="group overflow-hidden rounded-2xl border border-[#d6e2d8] bg-white shadow-[0_12px_24px_rgba(19,49,35,0.08)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_34px_rgba(19,49,35,0.12)]">
                        <a href="{{ route('products.show', $product->id) }}" class="block">
                            <div class="aspect-[4/3] bg-[#f1f5f1]">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-[#5b6c63]">
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.5-4.5a2 2 0 0 1 2.8 0L16 16m-2-2 1.6-1.6a2 2 0 0 1 2.8 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </a>
                        <div class="space-y-3 p-4">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(($product->type ?? []) as $mode)
                                    <span class="rounded-full bg-[#edf4ee] px-2.5 py-1 font-space text-[10px] font-bold uppercase tracking-[0.12em] text-[#006a38]">{{ $mode }}</span>
                                @endforeach
                            </div>
                            <h3 class="font-space text-base font-bold text-[#1a1c1c] line-clamp-2">{{ $product->title }}</h3>
                            <p class="text-sm text-[#30423a]">{{ \Illuminate\Support\Str::limit($product->description ?? 'Circular listing ready for reuse.', 88) }}</p>
                            <div class="flex items-center justify-between pt-1">
                                <p class="font-space text-sm font-bold text-[#006a38]">Rs. {{ number_format((float) ($product->price ?? 0), 2) }}</p>
                                <a href="{{ route('products.show', $product->id) }}" class="font-space text-[11px] font-bold uppercase tracking-[0.12em] text-[#006a38] hover:text-[#004a29]">Details</a>
                            </div>
                        </div>
                    </article>
                @empty
                    @foreach([
                        ['title' => 'Laptop Sleeve', 'type' => 'swap', 'price' => '1,200'],
                        ['title' => 'Study Lamp', 'type' => 'rent', 'price' => '300'],
                        ['title' => 'Winter Jacket', 'type' => 'buy', 'price' => '2,500'],
                    ] as $sample)
                        <article class="rounded-2xl border border-[#d6e2d8] bg-white p-5 shadow-[0_10px_20px_rgba(19,49,35,0.06)]">
                            <span class="rounded-full bg-[#edf4ee] px-2.5 py-1 font-space text-[10px] font-bold uppercase tracking-[0.12em] text-[#006a38]">{{ $sample['type'] }}</span>
                            <h3 class="mt-3 font-space text-base font-bold text-[#1a1c1c]">{{ $sample['title'] }}</h3>
                            <p class="mt-2 text-sm text-[#30423a]">Sample featured card shown when listings are still growing.</p>
                            <p class="mt-4 font-space text-sm font-bold text-[#006a38]">Rs. {{ $sample['price'] }}</p>
                        </article>
                    @endforeach
                @endforelse
            </div>
        </section>

        <section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-[1.75rem] border border-[#d4e1d6] bg-white/85 p-6 shadow-[0_18px_32px_rgba(19,49,35,0.08)] md:p-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.16em] text-[#006a38]">Categories</p>
                        <h2 class="mt-2 font-space text-2xl font-bold uppercase tracking-[0.02em] text-[#122d22] sm:text-3xl">Browse By Product Area</h2>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($topCategories as $category)
                        <a href="{{ route('products.index', ['category' => $category->id]) }}" class="rounded-xl border border-[#d9e4db] bg-[#f8fbf8] p-4 transition hover:border-[#98b89f] hover:bg-white">
                            <p class="font-space text-sm font-bold uppercase tracking-[0.1em] text-[#1a1c1c]">{{ $category->name }}</p>
                            <p class="mt-1 text-sm text-[#486056]">{{ $category->products_count }} active listing{{ $category->products_count === 1 ? '' : 's' }}</p>
                        </a>
                    @empty
                        @foreach(['Electronics', 'Books', 'Clothing', 'Furniture', 'Sports', 'Home Appliances'] as $fallbackCategory)
                            <a href="{{ route('products.index') }}" class="rounded-xl border border-[#d9e4db] bg-[#f8fbf8] p-4 transition hover:border-[#98b89f] hover:bg-white">
                                <p class="font-space text-sm font-bold uppercase tracking-[0.1em] text-[#1a1c1c]">{{ $fallbackCategory }}</p>
                                <p class="mt-1 text-sm text-[#486056]">Explore available listings in this area.</p>
                            </a>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </section>

        <section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-[1.75rem] border border-[#d5e1d7] bg-white p-6 shadow-[0_16px_28px_rgba(19,49,35,0.08)] md:p-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-[0.16em] text-[#006a38]">How ReLoop Works</p>
                    <h2 class="mt-2 font-space text-2xl font-bold uppercase tracking-[0.02em] text-[#122d22]">Simple Four-Step Flow</h2>
                    <ol class="mt-5 space-y-3">
                        <li class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                            <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Step 1</p>
                            <p class="mt-1 text-sm text-[#30423a]">Create an account and verify your profile details.</p>
                        </li>
                        <li class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                            <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Step 2</p>
                            <p class="mt-1 text-sm text-[#30423a]">Browse listings or publish your own item for buy, rent, or swap.</p>
                        </li>
                        <li class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                            <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Step 3</p>
                            <p class="mt-1 text-sm text-[#30423a]">Complete the transaction with guided checkout and status updates.</p>
                        </li>
                        <li class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                            <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Step 4</p>
                            <p class="mt-1 text-sm text-[#30423a]">Close the loop by reusing items and building circular habits.</p>
                        </li>
                    </ol>
                </div>

                <div class="rounded-[1.75rem] border border-[#d5e1d7] bg-white p-6 shadow-[0_16px_28px_rgba(19,49,35,0.08)] md:p-8">
                    <p class="font-space text-[11px] font-bold uppercase tracking-[0.16em] text-[#006a38]">Circular Economy Message</p>
                    <h2 class="mt-2 font-space text-2xl font-bold uppercase tracking-[0.02em] text-[#122d22]">Why Sustainability Matters</h2>
                    <p class="mt-4 text-sm leading-relaxed text-[#30423a]">
                        ReLoop is designed around circular economy thinking: maintain product value for longer,
                        avoid early disposal, and reduce demand for unnecessary new production.
                    </p>
                    <div class="mt-5 rounded-xl border border-[#d9e4db] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Expected Impact</p>
                        <ul class="mt-2 space-y-2 text-sm text-[#30423a]">
                            <li>Lower resource waste through product reuse and sharing.</li>
                            <li>More affordable access to products for families and local communities.</li>
                            <li>Community behavior shift from ownership-first to utility-first thinking.</li>
                        </ul>
                    </div>

                    <div class="mt-6 rounded-xl bg-gradient-to-r from-[#006a38] to-[#0b8d50] p-5 text-white">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.16em] text-white/80">Call To Action</p>
                        <p class="mt-2 text-lg font-semibold">Start With One Circular Action Today</p>
                        <p class="mt-1 text-sm text-white/90">List one item, rent one item, or swap one item to begin.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('products.index') }}" class="rounded-full bg-white px-4 py-2 font-space text-[11px] font-bold uppercase tracking-[0.12em] text-[#006a38] transition hover:bg-[#e7f3ea]">Browse Items</a>
                            @auth
                                <a href="{{ route('products.create') }}" class="rounded-full border border-white/60 px-4 py-2 font-space text-[11px] font-bold uppercase tracking-[0.12em] text-white transition hover:bg-white/10">Create Listing</a>
                            @else
                                <a href="{{ route('register') }}" class="rounded-full border border-white/60 px-4 py-2 font-space text-[11px] font-bold uppercase tracking-[0.12em] text-white transition hover:bg-white/10">Join ReLoop</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-[1.75rem] border border-[#d6e2d8] bg-white/90 p-6 shadow-[0_16px_30px_rgba(19,49,35,0.08)] md:p-8">
                <p class="font-space text-[11px] font-bold uppercase tracking-[0.16em] text-[#006a38]">Trust And Safety</p>
                <h2 class="mt-2 font-space text-2xl font-bold uppercase tracking-[0.02em] text-[#122d22]">Built-In Safeguards</h2>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Transparent Status</p>
                        <p class="mt-2 text-sm text-[#30423a]">Order, rental, and swap states remain visible throughout each workflow.</p>
                    </div>
                    <div class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Moderation Support</p>
                        <p class="mt-2 text-sm text-[#30423a]">Listing review and admin intervention help limit unsafe or invalid activity.</p>
                    </div>
                    <div class="rounded-xl border border-[#dbe5dd] bg-[#f8fbf8] p-4">
                        <p class="font-space text-[11px] font-bold uppercase tracking-[0.14em] text-[#006a38]">Dispute Resolution</p>
                        <p class="mt-2 text-sm text-[#30423a]">Users can report issues and track responses within the platform.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')
</body>
</html>
