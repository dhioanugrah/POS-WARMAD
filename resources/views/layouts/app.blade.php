@php
    $storeName = setting('store.name', config('app.name'));

    $navGroups = [
        [
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'dashboard.view', 'icon' => 'chart-bar'],
                ['label' => 'Kasir', 'route' => 'pos.index', 'permission' => 'pos.access', 'icon' => 'shopping-cart'],
            ],
        ],
        [
            'label' => 'Transaksi',
            'items' => [
                ['label' => 'Penjualan', 'route' => 'sales.index', 'permission' => 'sales.view', 'icon' => 'receipt'],
                ['label' => 'Pembelian', 'route' => 'purchases.index', 'permission' => 'purchases.manage', 'icon' => 'truck'],
            ],
        ],
        [
            'label' => 'Barang',
            'items' => [
                ['label' => 'Barang', 'route' => 'products.index', 'permission' => 'products.view', 'icon' => 'cube'],
                ['label' => 'Kategori', 'route' => 'categories.index', 'permission' => 'products.manage', 'icon' => 'tag'],
                ['label' => 'Cetak Label', 'route' => 'products.labels', 'permission' => 'products.manage', 'icon' => 'qrcode'],
            ],
        ],
        [
            'label' => 'Stok',
            'items' => [
                ['label' => 'Posisi Stok', 'route' => 'stock.index', 'permission' => 'stock.view', 'icon' => 'archive-box'],
                ['label' => 'Saldo Awal', 'route' => 'stock.opening', 'permission' => 'stock.opname', 'icon' => 'inbox'],
                ['label' => 'Stok Minimum', 'route' => 'stock.minimum', 'permission' => 'stock.view', 'icon' => 'exclamation-triangle'],
                ['label' => 'Stok Opname', 'route' => 'stock-opnames.index', 'permission' => 'stock.opname', 'icon' => 'clipboard-document-check'],
                ['label' => 'Penyesuaian', 'route' => 'stock-adjustments.index', 'permission' => 'stock.adjust', 'icon' => 'wrench'],
                ['label' => 'Transfer Lokasi', 'route' => 'stock-transfers.index', 'permission' => 'stock.transfer', 'icon' => 'arrows-right-left'],
            ],
        ],
        [
            'label' => 'Kontak',
            'items' => [
                ['label' => 'Pelanggan', 'route' => 'customers.index', 'permission' => 'customers.manage', 'icon' => 'users'],
                ['label' => 'Supplier', 'route' => 'suppliers.index', 'permission' => 'suppliers.manage', 'icon' => 'building-storefront'],
            ],
        ],
        [
            'label' => 'Keuangan',
            'items' => [
                ['label' => 'Kas Masuk/Keluar', 'route' => 'cash.index', 'permission' => 'cash.manage', 'icon' => 'banknotes'],
                ['label' => 'Piutang', 'route' => 'receivables.index', 'permission' => 'receivables.manage', 'icon' => 'arrow-down-circle'],
                ['label' => 'Hutang', 'route' => 'payables.index', 'permission' => 'payables.manage', 'icon' => 'arrow-up-circle'],
            ],
        ],
        [
            'items' => [
                ['label' => 'Laporan', 'route' => 'reports.index', 'permission' => 'reports.view', 'icon' => 'document-chart-bar'],
            ],
        ],
        [
            'label' => 'Pengaturan',
            'items' => [
                ['label' => 'Toko & Sistem', 'route' => 'settings.index', 'permission' => 'settings.manage', 'icon' => 'cog-6-tooth'],
                ['label' => 'Lokasi', 'route' => 'settings.locations', 'permission' => 'settings.manage', 'icon' => 'map-pin'],
                ['label' => 'Pengguna', 'route' => 'settings.users.index', 'permission' => 'users.manage', 'icon' => 'user-group'],
                ['label' => 'Hak Akses', 'route' => 'settings.roles', 'permission' => 'users.manage', 'icon' => 'lock-closed'],
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' - '.$storeName : $storeName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900 bg-slate-50" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Sidebar (desktop: static, mobile: drawer) --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
            x-on:click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-slate-900 transition-transform duration-200 lg:static lg:translate-x-0"
            x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 flex-shrink-0 items-center gap-2 px-6">
                <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-500 font-bold text-white">{{ Str::of($storeName)->substr(0, 1) }}</span>
                <span class="truncate text-base font-semibold text-white">{{ $storeName }}</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 pb-4">
                @foreach($navGroups as $group)
                    @php
                        $visibleItems = collect($group['items'])->filter(fn ($item) => Route::has($item['route']) && auth()->user()->can($item['permission']));
                    @endphp
                    @if($visibleItems->isNotEmpty())
                        <div class="mt-5 first:mt-2">
                            @if(!empty($group['label']))
                                <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $group['label'] }}</p>
                            @endif
                            <div class="space-y-0.5">
                                @foreach($visibleItems as $item)
                                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                                    <a
                                        href="{{ route($item['route']) }}"
                                        wire:navigate
                                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                                    >
                                        <x-icon :name="$item['icon']" class="size-5 flex-shrink-0" />
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="flex-shrink-0 border-t border-slate-800 p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-full bg-slate-700 text-sm font-semibold text-white">{{ Str::of(auth()->user()->name)->substr(0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white" title="Keluar">
                            <x-icon name="logout" class="size-5" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-16 flex-shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
                <button type="button" class="text-slate-500 lg:hidden" x-on:click="sidebarOpen = true">
                    <x-icon name="bars" class="size-6" />
                </button>

                <div class="flex-1"></div>

                @if(auth()->user()->defaultLocation)
                    <span class="hidden items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 sm:flex">
                        <x-icon name="map-pin" class="size-3.5" />
                        {{ auth()->user()->defaultLocation->name }}
                    </span>
                @endif
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />
</body>
</html>
