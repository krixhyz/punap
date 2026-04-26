<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('guest-body-class', 'min-h-screen bg-[#f9f9f9] flex items-center justify-center py-12 px-4 font-manrope text-[#1a1c1c]')">
    <div class="@yield('guest-card-class', 'bg-white shadow-[0_20px_40px_rgba(26,28,28,0.06)] p-10 w-full max-w-sm')">
        @yield('content')
    </div>
</body>
</html>
