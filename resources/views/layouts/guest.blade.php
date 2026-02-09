<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow rounded-lg p-6">
            @yield('content')
        </div>
    </div>
</body>
</html>
