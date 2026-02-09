<nav class="sticky top-0 z-50 bg-transparent"> 
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mt-4">
            <div class="rounded-full p-[1.5px] bg-gradient-to-r from-blue-500/40 via-rose-500/40 to-amber-500/40">
                <div class="rounded-full bg-white/80 backdrop-blur shadow-sm">
                
                    <div class="flex h-14 items-center justify-between px-4">
                    
                        <!-- LEFT SIDE -->
                        <div class="flex items-center gap-8">
                            <a href="{{ route('products.index') }}" 
                               class="text-lg font-semibold tracking-tight text-gray-900 hover:text-gray-700 whitespace-nowrap">
                               Reloop
                            </a>

                            <div class="hidden lg:flex items-center gap-6">
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex items-center whitespace-nowrap text-sm font-medium 
                                   {{ request()->routeIs('dashboard') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                                    Dashboard
                                </a>
                            </div>
                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="hidden lg:flex items-center gap-3 shrink-0 ml-auto">
                            <a href="{{ route('cart.index') }}" 
                               class="text-gray-600 hover:text-gray-900 inline-flex items-center" 
                               aria-label="Cart">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.293 2.293a1 1 0 
                                      00.707 1.707H19m-7 0a2 2 0 100 4 2 2 0 000-4zm6 0a2 2 0 
                                      100 4 2 2 0 000-4z"/>
                                </svg>
                            </a>

                            @auth
                                <a href="{{ route('profile.edit') }}"
                                   class="inline-flex items-center whitespace-nowrap text-sm font-medium text-gray-700 hover:text-gray-900">
                                    Profile
                                </a>

                                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center whitespace-nowrap rounded-full bg-gray-900 text-white text-sm px-4 py-2 hover:bg-gray-800">
                                        Logout
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center whitespace-nowrap rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:border-gray-400 hover:text-gray-900">
                                    Log in
                                </a>
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center whitespace-nowrap rounded-full bg-gray-900 text-white text-sm px-4 py-2 hover:bg-gray-800">
                                    Register
                                </a>
                            @endauth
                        </div>

                        <!-- MOBILE RIGHT -->
                        <div class="flex lg:hidden items-center gap-3">
                            <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.293 2.293a1 1 0 
                                          00.707 1.707H19m-7 0a2 2 0 100 4 2 2 0 000-4zm6 0a2 2 0 
                                          100 4 2 2 0 000-4z" />
                                </svg>
                            </a>
                            <button id="menu-toggle" type="button"
                                    class="inline-flex items-center justify-center rounded-full p-2 text-gray-700 hover:bg-gray-100">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- MOBILE MENU -->
                    <div id="mobile-menu" class="hidden lg:hidden border-t border-gray-100">
                        <div class="p-3 flex flex-col gap-2">
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">
                                Dashboard
                            </a>

                            @auth
                                <a href="{{ route('profile.edit') }}"
                                   class="inline-flex items-center rounded-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900">
                                    Profile
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center rounded-full bg-gray-900 text-white px-3 py-2 text-sm hover:bg-gray-800">
                                        Logout
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center rounded-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900">
                                    Log in
                                </a>
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center rounded-full bg-gray-900 text-white px-3 py-2 text-sm hover:bg-gray-800">
                                    Register
                                </a>
                            @endauth
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const btn = document.getElementById("menu-toggle");
            const menu = document.getElementById("mobile-menu");
            btn.addEventListener("click", () => menu.classList.toggle("hidden"));
        });
    </script>
</nav>
