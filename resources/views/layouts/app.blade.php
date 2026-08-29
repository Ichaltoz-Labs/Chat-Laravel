<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TempChat')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-neutral text-primary">
    <div class="min-h-full flex flex-col">
        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    <div id="toast-container"
         class="pointer-events-none fixed top-4 left-1/2 z-50 flex w-full max-w-sm -translate-x-1/2 flex-col items-center gap-2"></div>
</body>
</html>