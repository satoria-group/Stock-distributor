<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#f8fafc] text-slate-900 font-sans antialiased selection:bg-[#0d6d5f] selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="min-h-screen flex">
        <!-- Modern Sidebar (Fixed) -->
        <aside class="w-64 fixed inset-y-0 left-0 bg-white border-r border-slate-200/80 flex flex-col justify-between shadow-xs z-30 overflow-y-auto">
            <div>
                <!-- Brand Header -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 bg-white">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white font-bold text-base shadow-sm"
                         style="background: linear-gradient(135deg, #07352d 0%, #0d6d5f 100%);">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold text-slate-900 tracking-tight leading-tight">Satoria Group</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider mt-1" style="color: #0d6d5f;">Distributor Stock Hub</div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="p-3.5 space-y-1.5 text-xs">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('dashboard') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('dashboard') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('dashboard') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Master Distributor -->
                    @can('viewAny', \App\Models\Distributor::class)
                    <a href="{{ route('distributors.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('distributors.*') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('distributors.*') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('distributors.*') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Master Distributor</span>
                    </a>
                    @endcan

                    <!-- Master Netsuite -->
                    @can('viewAny', \App\Models\NetsuiteItem::class)
                    <a href="{{ route('netsuite-items.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('netsuite-items.*') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('netsuite-items.*') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('netsuite-items.*') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Master Produk (Netsuite)</span>
                    </a>
                    @endcan

                    <!-- Mapping Item -->
                    @can('viewAny', \App\Models\DistributorItem::class)
                    <a href="{{ route('distributor-items.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('distributor-items.*') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('distributor-items.*') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('distributor-items.*') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <span>Mapping Item Distributor</span>
                    </a>
                    @endcan

                    <!-- Upload Stock -->
                    @can('create', \App\Models\StockEntry::class)
                    <a href="{{ route('stock.upload') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('stock.upload') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('stock.upload') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('stock.upload') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <span>Upload Stock Harian</span>
                    </a>
                    @endcan

                    <!-- Riwayat Stok -->
                    @can('viewAny', \App\Models\StockEntry::class)
                    <a href="{{ route('stock.history') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('stock.history') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('stock.history') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('stock.history') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Riwayat Stok</span>
                    </a>
                    @endcan

                    <!-- User Management -->
                    @can('users.manage')
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('users.*') ? 'font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }}"
                       style="{{ request()->routeIs('users.*') ? 'background: #eaf4f2; color: #07352d;' : '' }}">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="{{ request()->routeIs('users.*') ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Manajemen User</span>
                    </a>
                    @endcan
                </nav>
            </div>

            <!-- User Profile Card -->
            <div class="p-4 border-t border-slate-100">
                <div class="rounded-2xl p-3.5 border border-slate-200/80 bg-slate-50/70 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs"
                             style="background: linear-gradient(135deg, #07352d 0%, #0d6d5f 100%);">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-extrabold text-slate-900 truncate leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] font-mono font-bold truncate mt-0.5" style="color: #0d6d5f;">{{ auth()->user()->getRoleNames()->first() ?? 'Staff' }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3 pt-2.5 border-t border-slate-200/60 flex justify-end">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-600 hover:text-rose-700 cursor-pointer transition">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 min-w-0 flex flex-col pl-64">
            <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-8 py-4 sticky top-0 z-20 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">{{ $title ?? 'Dashboard Stock Distributor' }}</h1>
                        @isset($subtitle)
                            <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                        @endisset
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-mono font-bold border shadow-2xs"
                              style="background: #ecfdf5; color: #065f46; border-color: #a7f3d0;">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Online</span>
                        </span>
                    </div>
                </div>
            </header>

            <main class="p-8 flex-1">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border px-4 py-3.5 flex items-center gap-3 text-xs font-semibold shadow-2xs"
                         style="background: #ecfdf5; color: #065f46; border-color: #a7f3d0;">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
