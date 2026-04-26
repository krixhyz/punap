<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @auth
    <script>
        window.Laravel = {
            userId: {{ auth()->id() }},
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    @endauth
</head>
<body class="bg-[#f9f9f9] font-manrope text-[#1a1c1c]">
    @include('layouts.navigation')

    <main class="mx-auto mt-8 w-full max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
        <div class="surface-card p-4 sm:p-6 lg:p-8">
            @yield('content')
        </div>
    </main>

    @include('layouts.footer')

    @php
        $flashToasts = [
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
        ];
    @endphp

    @if(collect($flashToasts)->filter()->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const flashToasts = @json($flashToasts);

                const renderToast = (type, message) => {
                    if (!message) return;

                    if (window.toastr && typeof window.toastr[type] === 'function') {
                        window.toastr[type](message);
                        return;
                    }

                    if (typeof window.showToast === 'function') {
                        window.showToast(message, null, null, type);
                    }
                };

                renderToast('success', flashToasts.success);
                renderToast('error', flashToasts.error);
                renderToast('warning', flashToasts.warning);
                renderToast('info', flashToasts.info);
            });
        </script>
    @endif

    @stack('scripts')
</body>

</html>
