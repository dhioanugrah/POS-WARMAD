<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tidak punya akses</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 font-sans antialiased text-slate-900">
    <div class="mx-auto max-w-md px-6 text-center">
        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-rose-100">
            <x-icon name="lock-closed" class="size-8 text-rose-600" />
        </div>
        <h1 class="text-lg font-semibold text-slate-900">Tidak punya akses</h1>
        <p class="mt-2 text-sm text-slate-500">Anda tidak memiliki izin untuk membuka halaman ini. Hubungi pemilik toko atau admin jika Anda merasa ini keliru.</p>
        <div class="mt-6">
            @auth
                @php
                    $home = auth()->user()->hasRole('Kasir') ? route('pos.index') : route('dashboard');
                @endphp
                <a href="{{ $home }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Kembali
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Masuk
                </a>
            @endauth
        </div>
    </div>
</body>
</html>
