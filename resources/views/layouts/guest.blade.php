@php($storeName = setting('store.name', config('app.name')))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $storeName }} - Sistem Kasir</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-gradient-to-br from-emerald-50 via-slate-50 to-sky-50 min-h-screen flex flex-col justify-center items-center p-4">
        <div class="mb-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-600 text-white rounded-2xl shadow-xl font-extrabold text-2xl mb-2">
                {{ Str::of($storeName)->substr(0, 1) }}
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $storeName }}</h1>
            <p class="text-sm text-slate-600 font-medium">Sistem Kasir & Manajemen Toko</p>
        </div>

        <div class="w-full max-w-md bg-white/90 backdrop-blur-md shadow-2xl rounded-3xl border border-white/40 p-8">
            {{ $slot }}
        </div>

        <footer class="mt-8 text-center text-xs text-slate-500 font-medium">
            &copy; {{ now()->year }} {{ $storeName }}
        </footer>
    </body>
</html>
