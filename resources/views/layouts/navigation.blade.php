<nav class="sticky top-0 z-50 border-b border-[rgba(189,202,189,0.28)] bg-white/80 backdrop-blur-[18px]">
    @auth
        @php
            $navUnreadCount = auth()->user()->unreadNotifications()->count();
            $navDropdownNotifs = auth()->user()->notifications()->latest()->take(10)->get();
            $cartCount = auth()->user()->cartItems()->sum('quantity');
            $isAdminUser = auth()->user()->isAdmin() || auth()->user()->isSuperAdmin();
            $displayName = trim((string) auth()->user()->name);
            $displayName = $displayName !== '' ? explode(' ', $displayName)[0] : 'Account';
        @endphp
    @endauth

    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ route('landing') }}" class="font-space text-xs font-extrabold uppercase tracking-[0.22em] text-[#006a38]">Punap</a>
            <a href="{{ route('products.index') }}" class="hidden font-space text-xs font-semibold uppercase tracking-[0.2em] text-[#444746] transition-colors hover:text-[#006a38] md:inline">Marketplace</a>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            @auth
                @if(!$isAdminUser)
                    <a href="{{ route('cart.index') }}" class="relative rounded-full p-2 text-[#444746] transition-colors hover:bg-[#ecf3ee] hover:text-[#006a38] {{ request()->routeIs('cart.*') ? 'bg-[#ecf3ee] text-[#006a38]' : '' }}" title="Cart" aria-label="Cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a1.5 1.5 0 0 1 1.464 1.175L5.383 6m0 0h13.84a1.5 1.5 0 0 1 1.464 1.825l-1.12 4.5A1.5 1.5 0 0 1 18.102 13.5H7.07a1.5 1.5 0 0 1-1.464-1.175L5.382 6Zm1.492 10.5a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Zm11.25 0a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Z" />
                        </svg>
                        <span id="cart-count" class="{{ $cartCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 rounded-full bg-[#006a38] px-1.5 py-0.5 font-space text-[10px] font-bold leading-none text-white">
                            {{ $cartCount > 0 ? ($cartCount > 99 ? '99+' : $cartCount) : '' }}
                        </span>
                    </a>
                @endif

                <div class="relative" data-navbar-dropdown="user">
                    <button type="button" data-navbar-dropdown-toggle="user" class="inline-flex items-center gap-2 rounded-full border border-[rgba(189,202,189,0.6)] px-3 py-1.5 font-space text-xs font-semibold uppercase tracking-wider text-[#1a1c1c] transition-colors hover:border-[#006a38] hover:text-[#006a38]" aria-haspopup="true" aria-expanded="false">
                        <span>{{ $displayName }}</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div data-navbar-dropdown-panel="user"
                         class="hidden absolute right-0 top-full z-50 mt-2 w-56 rounded-xl border border-[rgba(189,202,189,0.45)] bg-white p-2 shadow-[0_22px_40px_rgba(26,28,28,0.1)]">
                        <a href="{{ $isAdminUser ? route('admin.dashboard') : route('dashboard') }}" class="block rounded-lg px-3 py-2 font-space text-xs font-semibold uppercase tracking-wider text-[#1a1c1c] transition-colors hover:bg-[#eff5f0] hover:text-[#006a38]">Dashboard</a>
                        <a href="{{ $isAdminUser ? route('admin.profile.edit') : route('profile.edit') }}" class="block rounded-lg px-3 py-2 font-space text-xs font-semibold uppercase tracking-wider text-[#1a1c1c] transition-colors hover:bg-[#eff5f0] hover:text-[#006a38]">Profile Settings</a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left font-space text-xs font-semibold uppercase tracking-wider text-[#ba1a1a] transition-colors hover:bg-[#fdeeed] hover:text-[#8a1515]">Logout</button>
                        </form>
                    </div>
                </div>

                @if(!$isAdminUser)
                    <a href="{{ route('wishlist.index') }}" class="relative rounded-full p-2 text-[#444746] transition-colors hover:bg-[#ecf3ee] hover:text-[#006a38] {{ request()->routeIs('wishlist.*') ? 'bg-[#ecf3ee] text-[#006a38]' : '' }}" title="Wishlist" aria-label="Wishlist">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25c0-2.485-2.03-4.5-4.5-4.5-1.74 0-3.246.994-3.99 2.446C11.766 4.744 10.26 3.75 8.52 3.75 6.03 3.75 4 5.765 4 8.25c0 7.22 8.51 11.97 8.51 11.97S21 15.47 21 8.25Z" />
                        </svg>
                    </a>
                @endif

                <div class="relative" data-navbar-dropdown="notifs">
                    <button type="button" data-navbar-dropdown-toggle="notifs" class="relative rounded-full p-2 text-[#444746] transition-colors hover:bg-[#ecf3ee] hover:text-[#006a38]" title="Notifications" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                        </svg>
                        <span id="notification-count" class="{{ $navUnreadCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 rounded-full bg-[#006a38] px-1.5 py-0.5 font-space text-[10px] font-bold leading-none text-white">
                            {{ $navUnreadCount > 0 ? ($navUnreadCount > 99 ? '99+' : $navUnreadCount) : '' }}
                        </span>
                    </button>

                    <div data-navbar-dropdown-panel="notifs"
                         class="hidden absolute right-0 top-full z-50 mt-2 w-[21rem] overflow-hidden rounded-xl border border-[rgba(189,202,189,0.45)] bg-white shadow-[0_22px_40px_rgba(26,28,28,0.1)]">
                        <div class="border-b border-[rgba(189,202,189,0.25)] bg-[#f6faf7] p-3">
                            <p class="font-space text-[11px] font-bold uppercase tracking-[0.18em] text-[#444746]">Notifications</p>
                        </div>

                        <div id="notification-dropdown-list" class="max-h-80 overflow-y-auto">
                            @forelse($navDropdownNotifs as $notif)
                                @php
                                    $isUnread = is_null($notif->read_at);
                                    $msg = $notif->data['message'] ?? 'Notification';
                                    $url = $notif->data['redirect_url'] ?? route('notifications.index');
                                    $type = $notif->data['type'] ?? 'general';
                                    $canClick = $url !== '#';

                                    if (in_array($type, ['swap', 'swapAccept', 'swapCounter', 'swapReject'], true) && !empty($notif->data['swap_request_id'])) {
                                        $swapReq = \App\Models\SwapRequest::find($notif->data['swap_request_id']);

                                        if (!$swapReq) {
                                            $url = route('notifications.index');
                                            $canClick = true;
                                        } elseif ($type === 'swapAccept') {
                                            $payerId = match ($swapReq->money_direction) {
                                                'requester_offers_cash' => $swapReq->requester_id,
                                                'owner_asks_cash' => $swapReq->owner_id,
                                                default => null,
                                            };

                                            if ($payerId && (int) auth()->id() === (int) $payerId && $swapReq->status === 'awaiting_payment') {
                                                $url = route('swap.checkout', $swapReq->id);
                                            } else {
                                                $url = route('swap.request.show', $swapReq->id);
                                            }

                                            $canClick = true;
                                        } else {
                                            $url = route('swap.request.show', $swapReq->id);
                                            $canClick = true;
                                        }
                                    }

                                    if ($type === 'rental' && !empty($notif->data['rental_request_id'])) {
                                        $rentalReq = \App\Models\RentalRequest::find($notif->data['rental_request_id']);

                                        if (!$rentalReq || $rentalReq->status === 'rejected') {
                                            $url = '#';
                                            $canClick = false;
                                        } elseif ($rentalReq->status === 'approved') {
                                            $url = route('products.myListings');
                                            $canClick = true;
                                        } else {
                                            $url = route('rental.myRentals', ['tab' => 'incoming', 'request' => $rentalReq->id]);
                                            $canClick = true;
                                        }
                                    }
                                @endphp
                                <div class="notification-dropdown-item {{ $canClick ? 'cursor-pointer' : 'cursor-not-allowed opacity-70' }} border-b border-[rgba(189,202,189,0.2)] px-4 py-3 {{ $isUnread ? 'bg-white' : 'bg-[#f9f9f9]' }} hover:bg-[#f3f3f3]"
                                     data-id="{{ $notif->id }}"
                                     data-url="{{ $url }}"
                                     @if($canClick) onclick="handleDropdownNotifClick(this)" @endif>
                                    <div class="flex items-start gap-2">
                                        @if($isUnread)
                                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#006a38]"></span>
                                        @else
                                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full border border-[#bdcabd] bg-transparent"></span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-2 font-manrope text-sm text-[#1a1c1c] {{ $isUnread ? 'font-medium' : '' }}">{{ $msg }}</p>
                                            <p class="mt-0.5 font-manrope text-xs text-[#444746]">{{ $notif->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-[#f3f3f3] px-4 py-6 text-center font-manrope text-sm text-[#444746]">No notifications yet</div>
                            @endforelse
                        </div>

                        <div class="flex items-center justify-between border-t border-[rgba(189,202,189,0.2)] bg-[#f6faf7] px-4 py-2.5 text-xs">
                            <button id="mark-all-read-btn" class="font-space font-bold uppercase tracking-wider text-[#006a38] hover:text-[#004a29]" onclick="markAllNotificationsRead(event)">Mark all read</button>
                            <a href="{{ route('notifications.index') }}" class="font-space font-bold uppercase tracking-wider text-[#444746] hover:text-[#006a38]">See all</a>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="font-space text-xs font-medium uppercase tracking-wider text-[#444746] transition-colors hover:text-[#006a38]">Log In</a>
                <a href="{{ route('register') }}" class="rounded-full bg-gradient-to-r from-[#006a38] to-[#0b8d50] px-4 py-2 font-space text-xs font-bold uppercase tracking-wider text-white transition hover:brightness-110">Register</a>
            @endauth
        </div>

        <div class="flex items-center gap-2 lg:hidden">
            @auth
                @if(!$isAdminUser)
                    <a href="{{ route('wishlist.index') }}" class="relative rounded-full p-2 text-[#444746] transition-colors hover:bg-[#ecf3ee] hover:text-[#006a38] {{ request()->routeIs('wishlist.*') ? 'bg-[#ecf3ee] text-[#006a38]' : '' }}" title="Wishlist" aria-label="Wishlist">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25c0-2.485-2.03-4.5-4.5-4.5-1.74 0-3.246.994-3.99 2.446C11.766 4.744 10.26 3.75 8.52 3.75 6.03 3.75 4 5.765 4 8.25c0 7.22 8.51 11.97 8.51 11.97S21 15.47 21 8.25Z" />
                        </svg>
                    </a>

                    <a href="{{ route('cart.index') }}" class="relative rounded-full p-2 text-[#444746] transition-colors hover:bg-[#ecf3ee] hover:text-[#006a38]" title="Cart" aria-label="Cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a1.5 1.5 0 0 1 1.464 1.175L5.383 6m0 0h13.84a1.5 1.5 0 0 1 1.464 1.825l-1.12 4.5A1.5 1.5 0 0 1 18.102 13.5H7.07a1.5 1.5 0 0 1-1.464-1.175L5.382 6Zm1.492 10.5a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Zm11.25 0a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Z" />
                        </svg>
                        @if($cartCount > 0)
                            <span class="absolute -right-1 -top-1 rounded-full bg-[#006a38] px-1.5 py-0.5 font-space text-[10px] font-bold leading-none text-white">
                                {{ $cartCount > 99 ? '99+' : $cartCount }}
                            </span>
                        @endif
                    </a>
                @endif

                <a href="{{ route('notifications.index') }}" class="relative rounded-full p-2 text-[#444746] transition-colors hover:bg-[#ecf3ee] hover:text-[#006a38]" title="Notifications" aria-label="Notifications">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                    </svg>
                    <span class="{{ $navUnreadCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 rounded-full bg-[#006a38] px-1.5 py-0.5 font-space text-[10px] font-bold leading-none text-white">
                        {{ $navUnreadCount > 0 ? ($navUnreadCount > 99 ? '99+' : $navUnreadCount) : '' }}
                    </span>
                </a>
            @endauth

        </div>
    </div>

    <script>
        (function setupNavbarDropdowns() {
            const dropdowns = Array.from(document.querySelectorAll('[data-navbar-dropdown]'));

            if (!dropdowns.length) {
                return;
            }

            const closeAll = () => {
                dropdowns.forEach((dropdown) => {
                    const toggle = dropdown.querySelector('[data-navbar-dropdown-toggle]');
                    const panel = dropdown.querySelector('[data-navbar-dropdown-panel]');

                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }

                    if (panel) {
                        panel.classList.add('hidden');
                    }
                });
            };

            const toggleDropdown = (name) => {
                const dropdown = document.querySelector(`[data-navbar-dropdown="${name}"]`);
                if (!dropdown) return;

                const toggle = dropdown.querySelector('[data-navbar-dropdown-toggle]');
                const panel = dropdown.querySelector('[data-navbar-dropdown-panel]');
                if (!toggle || !panel) return;

                const willOpen = panel.classList.contains('hidden');
                closeAll();

                if (willOpen) {
                    panel.classList.remove('hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            };

            dropdowns.forEach((dropdown) => {
                const name = dropdown.getAttribute('data-navbar-dropdown');
                const toggle = dropdown.querySelector('[data-navbar-dropdown-toggle]');

                if (!name || !toggle) return;

                toggle.addEventListener('click', (event) => {
                    event.stopPropagation();
                    toggleDropdown(name);
                });
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-navbar-dropdown]')) {
                    closeAll();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAll();
                }
            });
        })();

        function handleDropdownNotifClick(el) {
            const id = el.dataset.id;
            const url = el.dataset.url;
            if (!id || !url) return;

            fetch('{{ route('notifications.markRead') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (window.Laravel && window.Laravel.csrfToken)
                        ? window.Laravel.csrfToken
                        : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ id }),
            }).then(() => {
                const txt = el.querySelector('p');
                if (txt) txt.classList.remove('font-medium');
                const dot = el.querySelector('span');
                if (dot) {
                    dot.classList.remove('bg-[#006a38]');
                    dot.classList.add('border', 'border-[#bdcabd]', 'bg-transparent');
                }

                decrementBadge();
                window.location.href = url;
            });
        }

        function markAllNotificationsRead(e) {
            e.stopPropagation();
            fetch('{{ route('notifications.markAllRead') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (window.Laravel && window.Laravel.csrfToken)
                        ? window.Laravel.csrfToken
                        : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            }).then(() => {
                const badge = document.getElementById('notification-count');
                if (badge) {
                    badge.textContent = '';
                    badge.classList.add('hidden');
                }

                document.querySelectorAll('#notification-dropdown-list .notification-dropdown-item').forEach(item => {
                    const txt = item.querySelector('p');
                    if (txt) txt.classList.remove('font-medium');
                    const dot = item.querySelector('span');
                    if (dot) {
                        dot.classList.remove('bg-[#006a38]');
                        dot.classList.add('border', 'border-[#bdcabd]', 'bg-transparent');
                    }
                });
            });
        }

        function decrementBadge() {
            const badge = document.getElementById('notification-count');
            if (!badge) return;

            const current = parseInt(badge.textContent, 10) || 0;
            if (current <= 1) {
                badge.textContent = '';
                badge.classList.add('hidden');
            } else {
                badge.textContent = current - 1;
            }
        }

        function prependNotificationToDropdown(msg, url, id) {
            const list = document.getElementById('notification-dropdown-list');
            if (!list) {
                const badgeOnly = document.getElementById('notification-count');
                if (badgeOnly) {
                    const count = parseInt(badgeOnly.textContent || '0', 10);
                    const updated = Number.isNaN(count) ? 1 : count + 1;
                    badgeOnly.textContent = updated > 99 ? '99+' : String(updated);
                    badgeOnly.classList.remove('hidden');
                }
                return;
            }

            const emptyState = list.querySelector('div.text-center');
            if (emptyState) emptyState.remove();

            const el = document.createElement('div');
            el.className = 'notification-dropdown-item cursor-pointer border-b border-[rgba(189,202,189,0.2)] bg-white px-4 py-3 hover:bg-[#f3f3f3]';
            el.dataset.id = id;
            el.dataset.url = url;
            el.setAttribute('onclick', 'handleDropdownNotifClick(this)');
            el.innerHTML = `
                <div class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#006a38]"></span>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 font-manrope text-sm font-medium text-[#1a1c1c]">${escapeHtml(msg)}</p>
                        <p class="mt-0.5 font-manrope text-xs text-[#444746]">just now</p>
                    </div>
                </div>`;

            list.prepend(el);

            const items = list.querySelectorAll('.notification-dropdown-item');
            if (items.length > 10) items[items.length - 1].remove();

            const badge = document.getElementById('notification-count');
            if (badge) {
                const count = parseInt(badge.textContent || '0', 10);
                const updated = Number.isNaN(count) ? 1 : count + 1;
                badge.textContent = updated > 99 ? '99+' : String(updated);
                badge.classList.remove('hidden');
            }
        }

        function escapeHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }
    </script>
</nav>
