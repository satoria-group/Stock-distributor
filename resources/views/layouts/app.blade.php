<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#f6f5f1] text-[#17211f] font-sans antialiased">
    <div class="min-h-screen flex">
        <aside class="w-64 shrink-0 bg-white border-r border-[#e7e9e3] flex flex-col">
            <div class="px-5 py-5 border-b border-[#e7e9e3]">
                <div class="text-[11px] font-mono uppercase tracking-wider text-brand">Satoria Group</div>
                <div class="text-sm font-semibold mt-0.5">Stock Distributor</div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <a href="{{ route('dashboard') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Dashboard
                </a>

                @can('viewAny', \App\Models\Distributor::class)
                <a href="{{ route('distributors.index') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('distributors.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Master Distributor
                </a>
                @endcan

                @can('viewAny', \App\Models\NetsuiteItem::class)
                <a href="{{ route('netsuite-items.index') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('netsuite-items.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Master Produk (Netsuite)
                </a>
                @endcan

                @can('viewAny', \App\Models\DistributorItem::class)
                <a href="{{ route('distributor-items.index') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('distributor-items.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Mapping Item Distributor
                </a>
                @endcan

                @can('create', \App\Models\StockEntry::class)
                <a href="{{ route('stock.upload') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('stock.upload') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Upload Stock Harian
                </a>
                @endcan

                @can('viewAny', \App\Models\StockEntry::class)
                <a href="{{ route('stock.history') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('stock.history') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Riwayat Stok
                </a>
                @endcan

                @can('users.manage')
                <a href="{{ route('users.index') }}"
                   class="block px-3 py-2 rounded-lg {{ request()->routeIs('users.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    Manajemen User
                </a>
                @endcan
            </nav>

            <div class="px-4 py-4 border-t border-[#e7e9e3]">
                <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-gray-500 truncate">{{ auth()->user()->getRoleNames()->first() ?? '—' }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-xs font-mono uppercase tracking-wide text-red-600 hover:underline">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            <header class="bg-white border-b border-[#e7e9e3] px-8 py-4">
                <h1 class="text-lg font-semibold">{{ $title ?? 'Dashboard' }}</h1>
                @isset($subtitle)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
                @endisset
            </header>

            <main class="p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-brand-soft text-brand-dark text-sm px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
