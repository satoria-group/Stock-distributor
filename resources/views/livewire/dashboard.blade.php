<div>
    <!-- Top Header Banner (Modern Hero Banner ala Satoria Logistics) -->
    <div class="relative overflow-hidden rounded-3xl p-7 md:p-8 mb-7 shadow-xl border border-emerald-950/20 text-white satoria-gradient-banner"
         style="background: linear-gradient(135deg, #062c25 0%, #09483e 50%, #0d6d5f 100%); color: #ffffff !important;">
        <!-- Subtle Glow Effect in Background -->
        <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full blur-3xl pointer-events-none opacity-40"
             style="background: radial-gradient(circle, #34d399 0%, transparent 70%);"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="max-w-3xl">
                <!-- Glowing Live Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold mb-3.5 shadow-xs"
                     style="background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); color: #a7f3d0; backdrop-filter: blur(8px);">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                    </span>
                    <span class="tracking-wide">Satoria Distributor Stock Hub</span>
                </div>

                <!-- Main Hero Title -->
                <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold tracking-tight text-white leading-tight">
                    @if ($selectedGroup === 'ALL')
                        Stock On Hand Harian Distributor
                    @else
                        Stock Harian {{ $selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup }}
                        @if ($selectedBranchId)
                            <span class="text-emerald-200 font-normal text-xl md:text-2xl">· {{ $availableBranches->firstWhere('id', $selectedBranchId)?->name }}</span>
                        @endif
                    @endif
                </h1>

                <!-- Subtitle Description -->
                <p class="text-xs md:text-sm text-emerald-100/90 mt-2 leading-relaxed max-w-2xl">
                    Monitoring posisi stok fisik terdistribusi secara nasional, pengawasan kedaluwarsa berbasis FEFO, dan audit kepatuhan pengunggahan cabang harian.
                </p>

                <!-- Metadata Row -->
                <div class="flex items-center gap-3 mt-4 text-xs text-emerald-100 flex-wrap">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs shadow-xs"
                         style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.15);">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Update Terakhir: <b class="text-white">{{ $latestDate ? \Illuminate\Support\Carbon::parse($latestDate)->translatedFormat('d M Y') : 'Belum ada data' }}</b></span>
                    </div>
                    <span class="text-emerald-400/50">•</span>
                    <span class="text-emerald-200 text-xs font-medium">Snapshot Stok Terintegrasi</span>
                </div>
            </div>

            <!-- Frosted Distributor Group Selector Tabs -->
            <div class="shrink-0">
                <div class="text-[11px] font-mono uppercase tracking-wider text-emerald-200 font-bold mb-2 lg:text-right">
                    Filter Grup Distributor
                </div>
                <div class="p-1.5 rounded-2xl flex items-center gap-1 flex-wrap shadow-sm"
                     style="background: rgba(0, 0, 0, 0.35); border: 1px solid rgba(255, 255, 255, 0.18); backdrop-filter: blur(10px);">
                    @php
                        $groups = [
                            'ALL' => 'Ringkasan',
                            'GMP' => 'GMP',
                            'KFTD' => 'KFTD',
                            'MAM' => 'MAM',
                            'SDL' => 'SDL',
                            'UDC' => 'UDC',
                            'OTHER' => 'Lainnya',
                        ];
                    @endphp
                    @foreach ($groups as $gKey => $gLabel)
                        <button type="button" wire:click="$set('selectedGroup', '{{ $gKey }}')"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition duration-150 cursor-pointer"
                                style="{{ $selectedGroup === $gKey ? 'background: #ffffff; color: #07352d; box-shadow: 0 2px 8px rgba(0,0,0,0.18); font-weight: 800;' : 'color: #d1fae5; background: transparent;' }}">
                            {{ $gLabel }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 6 KPI Cards (Clean Modern Metric Grid with Icon Badges - Static & Sans-serif) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-7">
        <!-- Card 1: Total Btl (Infus) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">TOTAL STOCK (BTL)</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border border-teal-200/60"
                     style="background: #e6f7f5; color: #0d6d5f;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2.5">
                    {{ number_format($kpi['total_btl'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Card 2: Total Amp (Injeksi) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">TOTAL STOCK (AMP)</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border border-emerald-200/60"
                     style="background: #ecfdf5; color: #059669;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2.5">
                    {{ number_format($kpi['total_amp'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Card 3: Total Pcs/Box (Alkes) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">TOTAL STOCK (PCS)</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border border-blue-200/60"
                     style="background: #eff6ff; color: #2563eb;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2.5">
                    {{ number_format($kpi['total_pcs'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Card 4: Total SKU -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">TOTAL SKU AKTIF</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border border-indigo-200/60"
                     style="background: #eef2ff; color: #4f46e5;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2.5">
                    {{ $kpi['total_sku'] }}
                </div>
            </div>
        </div>

        <!-- Card 5: Total Cabang (Static) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">TOTAL CABANG</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border border-purple-200/60"
                     style="background: #faf5ff; color: #7c3aed;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2.5">
                    {{ $kpi['total_branches'] }}
                </div>
            </div>
        </div>

        <!-- Card 6: Alert ED Kritis (Static) -->
        <div class="bg-white rounded-2xl border {{ $kpi['expiring_soon'] > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200/80' }} p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase {{ $kpi['expiring_soon'] > 0 ? 'text-rose-700' : 'text-slate-500' }}">DEKAT/LEWAT ED</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                     style="{{ $kpi['expiring_soon'] > 0 ? 'background: #fff1f2; color: #e11d48; border-color: #fecdd3;' : 'background: #f1f5f9; color: #64748b; border-color: #e2e8f0;' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold tabular-nums tracking-tight mt-2.5 {{ $kpi['expiring_soon'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ $kpi['expiring_soon'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Data Holder for Safe Morph Updates -->
    <div id="charts-data-holder"
         class="hidden"
         data-top='@json($chartTopProducts)'
         data-donut='@json($chartDonut)'
         data-trend='@json($chartTrend)'
         data-fefo='@json($chartFefoHorizon)'>
    </div>

    <!-- Grafik Trend Stock On Hand (Snapshot Historis per Satuan) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs mb-7">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-xs shrink-0" style="background: #0d6d5f;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Trend Stock On Hand</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pergerakan posisi fisik harian (<span class="font-medium text-slate-700">{{ $chartTrend['unit_label'] }}</span>) selama {{ $chartTrend['period'] }} hari terakhir ({{ $chartTrend['start_date_formatted'] }} &ndash; {{ $chartTrend['end_date_formatted'] }})
                            @if ($selectedBranchId)
                                &bull; <span class="text-emerald-700 font-medium">{{ $availableBranches->firstWhere('id', $selectedBranchId)?->name }}</span>
                            @elseif ($selectedGroup !== 'ALL')
                                &bull; <span class="text-emerald-700 font-medium">{{ $selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Controls: Pilihan Satuan & Pilihan Periode -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Unit Selector (BTL, AMP, PCS) -->
                <div class="flex items-center bg-slate-100/90 p-1 rounded-xl border border-slate-200/70 text-xs">
                    <button type="button"
                            wire:click="setTrendUnit('BTL')"
                            wire:loading.attr="disabled"
                            wire:target="setTrendUnit,setTrendPeriod"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $trendUnit === 'BTL' ? 'bg-white font-bold text-[#0d6d5f] shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        Botol (BTL)
                    </button>
                    <button type="button"
                            wire:click="setTrendUnit('AMP')"
                            wire:loading.attr="disabled"
                            wire:target="setTrendUnit,setTrendPeriod"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $trendUnit === 'AMP' ? 'bg-white font-bold text-[#0d6d5f] shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        Ampul (AMP)
                    </button>
                    <button type="button"
                            wire:click="setTrendUnit('PCS')"
                            wire:loading.attr="disabled"
                            wire:target="setTrendUnit,setTrendPeriod"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $trendUnit === 'PCS' ? 'bg-white font-bold text-[#0d6d5f] shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        Pcs/Box (PCS)
                    </button>
                </div>

                <!-- Period Selector (7, 30, 90 Hari) -->
                <div class="flex items-center bg-slate-100/90 p-1 rounded-xl border border-slate-200/70 text-xs">
                    <button type="button"
                            wire:click="setTrendPeriod(7)"
                            wire:loading.attr="disabled"
                            wire:target="setTrendUnit,setTrendPeriod"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $trendPeriod === 7 ? 'bg-slate-900 font-bold text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        7 Hari
                    </button>
                    <button type="button"
                            wire:click="setTrendPeriod(30)"
                            wire:loading.attr="disabled"
                            wire:target="setTrendUnit,setTrendPeriod"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $trendPeriod === 30 ? 'bg-slate-900 font-bold text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        30 Hari
                    </button>
                    <button type="button"
                            wire:click="setTrendPeriod(90)"
                            wire:loading.attr="disabled"
                            wire:target="setTrendUnit,setTrendPeriod"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $trendPeriod === 90 ? 'bg-slate-900 font-bold text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        90 Hari
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 py-3 px-4 my-4 bg-slate-50/70 rounded-xl border border-slate-100">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Stok Posisi Terkini</span>
                <div class="text-base font-extrabold text-slate-900 tabular-nums font-mono mt-0.5">
                    {{ $chartTrend['latest_qty'] !== null ? number_format($chartTrend['latest_qty'], 0, ',', '.') : '-' }}
                    <span class="text-xs font-normal text-slate-500">{{ $chartTrend['unit'] }}</span>
                </div>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Stok Awal Periode</span>
                <div class="text-base font-extrabold text-slate-900 tabular-nums font-mono mt-0.5">
                    {{ $chartTrend['first_qty'] !== null ? number_format($chartTrend['first_qty'], 0, ',', '.') : '-' }}
                    <span class="text-xs font-normal text-slate-500">{{ $chartTrend['unit'] }}</span>
                </div>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Perubahan (&Delta; Delta)</span>
                <div class="text-base font-extrabold tabular-nums font-mono mt-0.5 flex items-center gap-1.5">
                    @if ($chartTrend['delta'] !== null)
                        @if ($chartTrend['delta'] > 0)
                            <span class="text-emerald-700">
                                +{{ number_format($chartTrend['delta'], 0, ',', '.') }}
                                <span class="text-xs font-semibold">({{ $chartTrend['delta_pct'] > 0 ? '+' : '' }}{{ $chartTrend['delta_pct'] }}%)</span>
                            </span>
                        @elseif ($chartTrend['delta'] < 0)
                            <span class="text-rose-600">
                                {{ number_format($chartTrend['delta'], 0, ',', '.') }}
                                <span class="text-xs font-semibold">({{ $chartTrend['delta_pct'] }}%)</span>
                            </span>
                        @else
                            <span class="text-slate-600">0 <span class="text-xs font-semibold">(0%)</span></span>
                        @endif
                    @else
                        <span class="text-slate-400">-</span>
                    @endif
                </div>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kelengkapan Snapshot</span>
                <div class="text-base font-extrabold text-slate-700 tabular-nums font-mono mt-0.5">
                    {{ $chartTrend['active_days'] }} <span class="text-xs font-normal text-slate-500">/ {{ $chartTrend['total_period_days'] }} Hari Terisi</span>
                </div>
            </div>
        </div>

        <!-- Canvas Grafik Line -->
        <div class="relative" style="height: 300px;">
            <!-- Loading Indicator Overlay saat ganti unit/periode grafik -->
            <div wire:loading wire:target="setTrendUnit,setTrendPeriod"
                 class="absolute inset-0 bg-white/75 backdrop-blur-[1.5px] flex items-center justify-center z-20 rounded-xl transition-all">
                <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-slate-900/90 text-white text-xs font-semibold shadow-lg backdrop-blur-md">
                    <svg class="animate-spin h-3.5 w-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memperbarui grafik...</span>
                </div>
            </div>

            <div wire:ignore class="w-full h-full">
                <canvas id="chart-stock-trend"></canvas>
                <div id="no-data-trend" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 text-xs hidden">
                    <div class="text-3xl mb-1">📈</div>
                    <span class="font-medium text-slate-500">Belum ada data riwayat stok untuk satuan dan filter ini.</span>
                    <span class="text-[11px] text-slate-400">Silakan pilih satuan lain atau grup distributor berbeda.</span>
                </div>
            </div>
        </div>

        <!-- Catatan Interpretasi -->
        <div class="flex items-center gap-1.5 mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-400">
            <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Grafik mencerminkan snapshot stok fisik harian. Garis terputus menandakan tidak adanya data unggahan (libur/tidak upload), bukan stok kosong (0).</span>
        </div>
    </div>

    <!-- Dua Grafik Utama: Top 10 Produk & Distribusi Stok -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-7">
        <!-- Grafik Kiri (Col-span-2): Top 10 Produk Berdasarkan Kuantitas -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Top 10 Produk Berdasarkan Kuantitas</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if ($selectedGroup === 'ALL' && ! $selectedBranchId)
                            Akumulasi volume kuantitas produk secara nasional dengan breakdown grup distributor
                        @else
                            Akumulasi kuantitas produk pada {{ $selectedBranchId ? $availableBranches->firstWhere('id', $selectedBranchId)?->name : ($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup) }}
                        @endif
                    </p>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 320px;">
                <canvas id="chart-top-products"></canvas>
                <div id="no-data-top" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 text-xs hidden">
                    <div class="text-3xl mb-1">📊</div>
                    <span class="font-medium text-slate-500">Belum ada data stok produk untuk grup ini.</span>
                    <span class="text-[11px] text-slate-400">Silakan pilih distributor lain atau unggah data stok harian.</span>
                </div>
            </div>
        </div>

        <!-- Grafik Kanan (Col-span-1): Distribusi Stok per Distributor (Donut) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">
                            @if ($selectedGroup === 'ALL' && ! $selectedBranchId)
                                Distribusi Stok per Distributor
                            @else
                                Komposisi Sediaan ({{ $selectedBranchId ? $availableBranches->firstWhere('id', $selectedBranchId)?->name : ($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup) }})
                            @endif
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Porsi persentase volume fisik stok</p>
                    </div>
                </div>
                <div wire:ignore class="relative flex items-center justify-center" style="height: 250px;">
                    <canvas id="chart-donut-dist"></canvas>
                    <div id="no-data-donut" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 text-xs hidden">
                        <div class="text-3xl mb-1">🍩</div>
                        <span class="font-medium text-slate-500">Belum ada data stok.</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-mono">
                <span>Total Volume Fisik</span>
                <span class="font-bold text-slate-900">{{ number_format($kpi['total_btl'] + $kpi['total_amp'] + $kpi['total_pcs'], 0, ',', '.') }} unit</span>
            </div>
        </div>
    </div>

    <!-- Modern Segmented Control Navigation Tabs (Full Width) -->
    <div class="w-full p-1.5 rounded-2xl border border-slate-200 shadow-2xs mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-1.5 transition-opacity duration-150"
         wire:loading.class="opacity-70 pointer-events-none cursor-wait"
         wire:target="switchTab"
         style="background: #edf2f1;">
        <!-- Tab 1: Stok On-Hand -->
        <button type="button" wire:click="switchTab('stock')"
                class="inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer w-full text-center"
                style="{{ $activeTab === 'stock' ? 'background: #ffffff; color: #07352d; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;' }}">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="{{ $activeTab === 'stock' ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            <span>Posisi Stok On-Hand</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold"
                  style="{{ $activeTab === 'stock' ? 'background: #0d6d5f; color: #ffffff;' : 'background: #e2e8f0; color: #475569;' }}">
                {{ number_format($totalDisplayRows, 0, ',', '.') }}
            </span>
        </button>

        <!-- Tab 2: Monitoring Kedaluwarsa (FEFO) -->
        <button type="button" wire:click="switchTab('expiry')"
                class="inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer w-full text-center"
                style="{{ $activeTab === 'expiry' ? 'background: #ffffff; color: #be123c; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;' }}">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="{{ $activeTab === 'expiry' ? 'text-rose-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Monitoring Kedaluwarsa (FEFO)</span>
            @if ($kpi['expiring_soon'] > 0)
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono bg-rose-600 text-white font-bold animate-pulse shadow-2xs">
                    {{ $kpi['expiring_soon'] }} Kritis
                </span>
            @else
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold"
                      style="{{ $activeTab === 'expiry' ? 'background: #ffe4e6; color: #9f1239;' : 'background: #e2e8f0; color: #475569;' }}">
                    {{ $fefoSummary['total'] ?? 0 }}
                </span>
            @endif
        </button>

        <!-- Tab 3: Kepatuhan Upload Cabang -->
        <button type="button" wire:click="switchTab('compliance')"
                class="inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer w-full text-center"
                style="{{ $activeTab === 'compliance' ? 'background: #ffffff; color: #0d6d5f; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;' }}">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="{{ $activeTab === 'compliance' ? 'text-[#0d6d5f]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Kepatuhan Upload Cabang</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold shadow-2xs text-white"
                  style="background: {{ $complianceSummary['compliance_rate'] >= 80 ? '#059669' : '#d97706' }};">
                {{ $complianceSummary['compliance_rate'] }}%
            </span>
        </button>

        <!-- Tab 4: Stok Macet & Slow-Moving (Dead Stock Alert) -->
        <button type="button" wire:click="switchTab('stagnant')"
                class="inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer w-full text-center"
                style="{{ $activeTab === 'stagnant' ? 'background: #ffffff; color: #b45309; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;' }}">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="{{ $activeTab === 'stagnant' ? 'text-amber-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>Stok Macet & Slow-Moving</span>
            @if (($stagnantSummary['dead'] ?? 0) > 0)
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono bg-amber-500 text-white font-bold shadow-2xs">
                    {{ $stagnantSummary['dead'] }} Macet
                </span>
            @else
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold"
                      style="{{ $activeTab === 'stagnant' ? 'background: #fef3c7; color: #92400e;' : 'background: #e2e8f0; color: #475569;' }}">
                    {{ $stagnantSummary['total'] ?? 0 }}
                </span>
            @endif
        </button>
    </div>

    <!-- TAB 1: POSISI STOK ON-HAND -->
    @if ($activeTab === 'stock')
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs mb-7">
        <!-- Toolbar Filter Tabel -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Detail Stock On Hand</h3>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-xs text-slate-500">Daftar stok per cabang distributor dan mutasi kuantitas snapshot</p>
                    @if ($sortBy !== 'item_name' || $sortDir !== 'asc')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span>Sortir: <b>{{ match($sortBy) {
                                'quantity' => 'Kuantitas',
                                'distributor' => 'Distributor',
                                'satuan' => 'Satuan',
                                'delta' => 'Δ vs Kemarin',
                                'expired_date' => 'ED / Batch',
                                default => 'Nama Produk',
                            } }}</b> ({{ $sortDir === 'asc' ? 'A→Z / Terkecil' : 'Z→A / Terbanyak' }})</span>
                            <button type="button" wire:click="$set('sortBy', 'item_name'); $set('sortDir', 'asc');" class="text-emerald-600 hover:text-emerald-900 ml-0.5 font-bold cursor-pointer" title="Kembalikan sortir default">×</button>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Filter Cabang -->
                <div class="w-56">
                    <select wire:model.live="selectedBranchId"
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        <option value="">— Semua Cabang ({{ count($availableBranches) }}) —</option>
                        @foreach ($availableBranches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Satuan -->
                <div class="w-36">
                    <select wire:model.live="satuanFilter"
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        <option value="">Semua Satuan</option>
                        <option value="BTL">Botol (Btl)</option>
                        <option value="AMP">Ampul (Amp)</option>
                        <option value="PCS">Pcs / Box (Alkes)</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative w-48 sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Cari produk / batch..."
                           class="w-full pl-9 pr-3 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                </div>

                <!-- Reset Filter Button -->
                @if ($selectedBranchId || $satuanFilter || $search || $sortBy !== 'item_name' || $sortDir !== 'asc')
                    <button type="button" wire:click="resetFilters"
                            class="px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer inline-flex items-center gap-1.5"
                            title="Reset semua filter dan sortir ke kondisi awal">
                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Reset</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="border border-slate-200/80 rounded-2xl overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead style="background: #f8faf9;" class="text-slate-500 font-bold uppercase text-[11px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="text-center py-3.5 px-4 w-12">No</th>

                        <!-- Item Produk (Sortable) -->
                        <th wire:click="setSort('item_name')"
                            class="text-left py-3.5 px-4 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Nama Produk">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="{{ $sortBy === 'item_name' ? 'font-bold' : '' }}" style="{{ $sortBy === 'item_name' ? 'color: #0d6d5f;' : '' }}">Item Produk</span>
                                @if ($sortBy === 'item_name')
                                    @if ($sortDir === 'asc')
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @else
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <!-- Satuan (Sortable) -->
                        <th wire:click="setSort('satuan')"
                            class="text-center py-3.5 px-4 w-24 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Satuan">
                            <div class="inline-flex items-center justify-center gap-1.5 w-full">
                                <span class="{{ $sortBy === 'satuan' ? 'font-bold' : '' }}" style="{{ $sortBy === 'satuan' ? 'color: #0d6d5f;' : '' }}">Satuan</span>
                                @if ($sortBy === 'satuan')
                                    @if ($sortDir === 'asc')
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @else
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <!-- Cabang Distributor (Sortable) -->
                        <th wire:click="setSort('distributor')"
                            class="text-left py-3.5 px-4 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Cabang Distributor">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="{{ $sortBy === 'distributor' ? 'font-bold' : '' }}" style="{{ $sortBy === 'distributor' ? 'color: #0d6d5f;' : '' }}">Cabang Distributor</span>
                                @if ($sortBy === 'distributor')
                                    @if ($sortDir === 'asc')
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @else
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <!-- Kuantitas (Sortable) -->
                        <th wire:click="setSort('quantity')"
                            class="text-right py-3.5 px-4 w-32 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Kuantitas Stok">
                            <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                <span class="{{ $sortBy === 'quantity' ? 'font-bold' : '' }}" style="{{ $sortBy === 'quantity' ? 'color: #0d6d5f;' : '' }}">Kuantitas</span>
                                @if ($sortBy === 'quantity')
                                    @if ($sortDir === 'asc')
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @else
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <!-- Delta vs Sebelumnya (Sortable) -->
                        <th wire:click="setSort('delta')"
                            class="text-right py-3.5 px-4 w-36 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Perubahan Delta">
                            <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                <span class="{{ $sortBy === 'delta' ? 'font-bold' : '' }}" style="{{ $sortBy === 'delta' ? 'color: #0d6d5f;' : '' }}">Δ vs Kemarin</span>
                                @if ($sortBy === 'delta')
                                    @if ($sortDir === 'asc')
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @else
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <!-- ED / Batch (Sortable) -->
                        <th wire:click="setSort('expired_date')"
                            class="text-left py-3.5 px-4 w-44 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Tanggal Kedaluwarsa">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="{{ $sortBy === 'expired_date' ? 'font-bold' : '' }}" style="{{ $sortBy === 'expired_date' ? 'color: #0d6d5f;' : '' }}">ED / Batch</span>
                                @if ($sortBy === 'expired_date')
                                    @if ($sortDir === 'asc')
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @else
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($stockTable as $idx => $r)
                        <tr class="hover:bg-[#f8faf9] transition duration-150">
                            <td class="text-center py-3.5 px-4 text-slate-400 font-mono">
                                {{ ($stockTable->currentPage() - 1) * $perPage + $idx + 1 }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <div class="font-bold text-slate-900">
                                    {{ $r->entry->distributorItem?->item_name ?? '—' }}
                                    @unless ($r->entry->distributorItem?->isMapped())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono uppercase bg-amber-50 text-amber-800 border border-amber-200 font-semibold ml-1.5">
                                            Belum Mapping
                                        </span>
                                    @endunless
                                </div>
                                @if ($r->entry->tanggal)
                                    <span class="block text-[10px] font-mono text-slate-400 mt-0.5">
                                        Snapshot: {{ $r->entry->tanggal->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-600">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                    {{ $r->entry->satuan ?: '—' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700">
                                <span class="font-bold text-slate-900">{{ $r->entry->distributor?->name ?? '—' }}</span>
                                <span class="block text-[10px] font-mono text-slate-400 mt-0.5">{{ $r->entry->distributor?->distributor_code }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-extrabold text-slate-900 text-sm">
                                {{ number_format($r->entry->quantity, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono">
                                @if ($r->delta_pct === null)
                                    <span class="text-slate-400">—</span>
                                @elseif ($r->delta_pct > 0)
                                    <span class="inline-flex items-center gap-0.5 text-emerald-800 font-bold bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full text-[11px]">
                                        ▲ +{{ $r->delta_pct }}%
                                    </span>
                                @elseif ($r->delta_pct < 0)
                                    <span class="inline-flex items-center gap-0.5 text-rose-800 font-bold bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full text-[11px]">
                                        ▼ {{ $r->delta_pct }}%
                                    </span>
                                @else
                                    <span class="text-slate-400 font-medium">0%</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px]">
                                @php
                                    $expStatus = $r->entry->expiryStatus();
                                    $days = $r->entry->daysToExpiry();
                                @endphp
                                @if ($r->entry->expired_date)
                                    <span class="font-bold {{ $expStatus === 'critical' ? 'text-rose-600' : ($expStatus === 'warning' ? 'text-amber-700' : 'text-slate-700') }}">
                                        {{ $r->entry->expired_date->translatedFormat('d M Y') }}
                                    </span>
                                    @if ($days !== null)
                                        <span class="text-[10px] text-slate-400 block mt-0.5">
                                            ({{ $days < 0 ? 'Lewat '.abs($days).'h' : $days.' hari lagi' }})
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if ($r->entry->batch_no)
                                    <span class="text-[10px] text-slate-500 font-mono block mt-0.5">B: {{ $r->entry->batch_no }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center text-slate-400">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="font-bold text-slate-600 text-sm">Tidak ada data stok yang cocok dengan filter</div>
                                <div class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau cabang distributor</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination & Summary -->
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan <b class="text-slate-800">{{ $stockTable->firstItem() ?? 0 }}</b> - <b class="text-slate-800">{{ $stockTable->lastItem() ?? 0 }}</b> dari <b class="text-slate-800">{{ $stockTable->total() }}</b> baris stok
            </div>
            <div>
                {{ $stockTable->links('livewire::tailwind') }}
            </div>
        </div>
    </div>

    <!-- Section Early Warning: Batch Kadaluarsa Kritis -->
    @if ($expiryAlerts->isNotEmpty())
        <div class="border border-rose-200/80 rounded-2xl p-6 shadow-xs mb-7"
             style="background: #fff8f8;">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Perhatian Logistik: Batch Mendekati / Lewat Kadaluarsa</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Prioritaskan pengeluaran stok (FEFO) atau koordinasikan penarikan sebelum retur kedaluwarsa</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                @foreach ($expiryAlerts as $alert)
                    @php
                        $days = $alert->daysToExpiry();
                        $status = $alert->expiryStatus();
                    @endphp
                    <div class="p-4 rounded-xl border {{ $status === 'critical' ? 'border-rose-200 bg-white' : 'border-amber-200 bg-white' }} text-xs shadow-2xs hover:shadow-sm transition">
                        <div class="font-bold text-slate-900 truncate text-xs">{{ $alert->distributorItem?->item_name }}</div>
                        <div class="text-slate-500 truncate text-[11px] mt-0.5">{{ $alert->distributor?->name }}</div>
                        <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100 font-mono">
                            <span class="text-slate-600">Qty: <b class="text-slate-900">{{ number_format($alert->quantity, 0, ',', '.') }}</b></span>
                            <span class="font-bold {{ $status === 'critical' ? 'text-rose-700' : 'text-amber-800' }}">
                                {{ $days < 0 ? 'Lewat '.abs($days).'h' : $days.' hari lagi' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    @elseif ($activeTab === 'expiry')
    <!-- TAB 2: MONITORING KEDALUWARSA (FEFO WATCHLIST) -->
    <div class="space-y-6 mb-7">
        <!-- 4 Summary Cards FEFO (Static Sans-serif without jumpy hover) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div wire:click="$set('expiryRiskFilter', 'critical')" class="bg-white border {{ $expiryRiskFilter === 'critical' ? 'border-rose-500 ring-2 ring-rose-200 shadow-sm' : 'border-slate-200/80' }} rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-rose-700 font-bold">🔴 Kritis (&lt; 3 Bulan)</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">FEFO Prioritas</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-rose-700 tabular-nums tracking-tight">
                    {{ $fefoSummary['critical'] }} <span class="text-xs font-normal text-slate-500">batch</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Berisiko tinggi ditolak RS / Apotek</div>
            </div>

            <div wire:click="$set('expiryRiskFilter', 'warning')" class="bg-white border {{ $expiryRiskFilter === 'warning' ? 'border-amber-500 ring-2 ring-amber-200 shadow-sm' : 'border-slate-200/80' }} rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-amber-700 font-bold">🟡 Waspada (3 - 6 Bulan)</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold">Near-ED</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-amber-700 tabular-nums tracking-tight">
                    {{ $fefoSummary['warning'] }} <span class="text-xs font-normal text-slate-500">batch</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Akselerasi penjualan ke cabang</div>
            </div>

            <div wire:click="$set('expiryRiskFilter', 'expired')" class="bg-white border {{ $expiryRiskFilter === 'expired' ? 'border-red-600 ring-2 ring-red-200 shadow-sm' : 'border-slate-200/80' }} rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-red-700 font-bold">⛔ Sudah Expired</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 font-bold">Karantina</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-red-700 tabular-nums tracking-tight">
                    {{ $fefoSummary['expired'] }} <span class="text-xs font-normal text-slate-500">batch</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Wajib ditarik & isolasi retur</div>
            </div>

            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-slate-600 font-bold">📦 Total Qty Berisiko ED</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold">Fisik</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-slate-900 tabular-nums tracking-tight">
                    {{ number_format($fefoSummary['total_qty_at_risk'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-100">Total unit batch expired &lt;6 bulan</div>
            </div>
        </div>

        <!-- Grafik Horizon Kedaluwarsa Makro (Macro Expiry Horizon Breakdown) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-xs shrink-0 bg-rose-600">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight">Distribusi Horizon Kedaluwarsa Makro</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Sebaran umur simpan stok fisik (<span class="font-medium text-slate-700">{{ $chartFefoHorizon['unit_label'] }}</span>) lintas 5 zona kedaluwarsa & distributor
                                @if ($fefoBranchId)
                                    &bull; <span class="text-rose-700 font-medium">{{ $availableBranches->firstWhere('id', $fefoBranchId)?->name }}</span>
                                @elseif ($selectedBranchId)
                                    &bull; <span class="text-rose-700 font-medium">{{ $availableBranches->firstWhere('id', $selectedBranchId)?->name }}</span>
                                @elseif ($selectedGroup !== 'ALL')
                                    &bull; <span class="text-rose-700 font-medium">{{ $selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Unit Selector (BTL, AMP, PCS) -->
                <div class="flex items-center bg-slate-100/90 p-1 rounded-xl border border-slate-200/70 text-xs shrink-0">
                    <button type="button"
                            wire:click="setFefoChartUnit('BTL')"
                            wire:loading.attr="disabled"
                            wire:target="setFefoChartUnit"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $fefoChartUnit === 'BTL' ? 'bg-white font-bold text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        Botol (BTL)
                    </button>
                    <button type="button"
                            wire:click="setFefoChartUnit('AMP')"
                            wire:loading.attr="disabled"
                            wire:target="setFefoChartUnit"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $fefoChartUnit === 'AMP' ? 'bg-white font-bold text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        Ampul (AMP)
                    </button>
                    <button type="button"
                            wire:click="setFefoChartUnit('PCS')"
                            wire:loading.attr="disabled"
                            wire:target="setFefoChartUnit"
                            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $fefoChartUnit === 'PCS' ? 'bg-white font-bold text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        Pcs / Box (PCS)
                    </button>
                </div>
            </div>

            <!-- Mini Agregat Horizon Bar (Top Progress Bar) & Legend Chips -->
            <div class="my-5 p-4 rounded-xl bg-slate-50 border border-slate-200/60 relative">
                <!-- Loading Indicator Overlay saat ganti unit FEFO Horizon -->
                <div wire:loading wire:target="setFefoChartUnit"
                     class="absolute inset-0 bg-white/75 backdrop-blur-[1.5px] flex items-center justify-center z-20 rounded-xl transition-all">
                    <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-slate-900/90 text-white text-xs font-semibold shadow-lg backdrop-blur-md">
                        <svg class="animate-spin h-3.5 w-3.5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Memperbarui horizon...</span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                    <span class="text-xs font-bold text-slate-700">Komposisi Umur Simpan Stok Agregat:</span>
                    <span class="text-xs text-slate-500 font-mono font-medium">
                        Total Volume: <b class="text-slate-900">{{ number_format($chartFefoHorizon['total_qty'], 0, ',', '.') }}</b> {{ $chartFefoHorizon['unit'] }} ({{ number_format($chartFefoHorizon['total_batches'], 0, ',', '.') }} batch)
                    </span>
                </div>

                <!-- Multi-segment horizontal bar -->
                <div class="h-4 w-full flex overflow-hidden rounded-lg bg-slate-200/80 p-0.5 gap-0.5 shadow-inner">
                    @if ($chartFefoHorizon['total_qty'] > 0)
                        @foreach ($chartFefoHorizon['national'] as $tierKey => $tier)
                            @if ($tier['pct'] > 0)
                                <div style="width: {{ $tier['pct'] }}%; background-color: {{ $tier['color'] }};"
                                     class="h-full first:rounded-l-md last:rounded-r-md transition-all duration-300 relative group cursor-pointer"
                                     title="{{ $tier['label'] }}: {{ number_format($tier['qty'], 0, ',', '.') }} {{ $chartFefoHorizon['unit'] }} ({{ $tier['pct'] }}%)">
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="w-full h-full rounded-md bg-slate-300 flex items-center justify-center text-[10px] text-slate-500 font-medium">
                            Tidak ada stok fisik berkedaluwarsa
                        </div>
                    @endif
                </div>

                <!-- 5 Legend Chips -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 mt-3 pt-3 border-t border-slate-200/60 text-xs">
                    @foreach ($chartFefoHorizon['national'] as $tierKey => $tier)
                        <div class="flex items-center gap-2 p-1.5 rounded-lg bg-white border border-slate-200/60">
                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $tier['color'] }};"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-semibold text-slate-700 truncate" title="{{ $tier['label'] }}">{{ $tier['label'] }}</div>
                                <div class="font-mono text-[11px] text-slate-500 flex items-center justify-between mt-0.5">
                                    <span>{{ number_format($tier['qty'], 0, ',', '.') }}</span>
                                    <span class="font-bold text-slate-800 ml-1">({{ $tier['pct'] }}%)</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Tabel FEFO & Filter Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="pb-5 border-b border-slate-100 mb-5 space-y-3.5">
                <!-- Baris 1: Judul di kiri & Tombol Ekspor CSV di kanan -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Daftar Batch Terurut Kedaluwarsa (FEFO Order)</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <p class="text-xs text-slate-500">Urutan teratas adalah batch yang paling mendekati tanggal kedaluwarsa</p>
                            @if ($fefoSortBy !== 'days' || $fefoSortDir !== 'asc')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <span>Sortir: <b>{{ match($fefoSortBy) {
                                        'item_name' => 'Nama Produk',
                                        'distributor' => 'Distributor',
                                        'batch_no' => 'No. Batch',
                                        'expired_date' => 'Expired Date',
                                        'quantity' => 'Kuantitas',
                                        'tier' => 'Status & Rekomendasi',
                                        default => 'Sisa Waktu',
                                    } }}</b> ({{ $fefoSortDir === 'asc' ? ($fefoSortBy === 'days' || $fefoSortBy === 'quantity' || $fefoSortBy === 'expired_date' ? 'Terkecil / Terdekat' : 'A→Z') : ($fefoSortBy === 'days' || $fefoSortBy === 'quantity' || $fefoSortBy === 'expired_date' ? 'Terbesar / Terjauh' : 'Z→A') }})</span>
                                    <button type="button" wire:click="$set('fefoSortBy', 'days'); $set('fefoSortDir', 'asc');" class="text-emerald-600 hover:text-emerald-900 ml-0.5 font-bold cursor-pointer" title="Kembalikan sortir default (FEFO)">×</button>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0">
                        <button type="button" wire:click="exportNearEdCsv"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                                title="Unduh rekapitulasi batch near-ED dalam format CSV Excel">
                            <svg width="15" height="15" style="width: 15px; height: 15px; min-width: 15px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span>Ekspor CSV</span>
                        </button>
                    </div>
                </div>

                <!-- Baris 2: Filter Risiko, Filter Cabang, Filter Satuan, Search & Reset -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 pt-1">
                    <!-- Sub-Filter Buttons as Segmented Control -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs overflow-x-auto shrink-0">
                        <button type="button" wire:click="$set('expiryRiskFilter', 'all')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $expiryRiskFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Semua ({{ $fefoSummary['total'] }})
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'critical')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $expiryRiskFilter === 'critical' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 hover:bg-rose-50' }}">
                            Kritis ({{ $fefoSummary['critical'] }})
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'warning')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $expiryRiskFilter === 'warning' ? 'bg-amber-500 text-white shadow-2xs' : 'text-amber-700 hover:bg-amber-50' }}">
                            Waspada ({{ $fefoSummary['warning'] }})
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'safe')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $expiryRiskFilter === 'safe' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-emerald-700 hover:bg-emerald-50' }}">
                            Aman ({{ $fefoSummary['safe'] }})
                        </button>
                    </div>

                    <!-- Filter Controls FEFO: Cabang, Satuan, Search & Reset -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Filter Cabang FEFO -->
                        <div class="w-48 sm:w-56">
                            <select wire:model.live="fefoBranchId"
                                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                                <option value="">— Semua Cabang ({{ count($availableBranches) }}) —</option>
                                @foreach ($availableBranches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Satuan FEFO -->
                        <div class="w-32 sm:w-36">
                            <select wire:model.live="fefoSatuanFilter"
                                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                                <option value="">Semua Satuan</option>
                                <option value="BTL">Botol (Btl)</option>
                                <option value="AMP">Ampul (Amp)</option>
                                <option value="PCS">Pcs / Box (Alkes)</option>
                            </select>
                        </div>

                        <!-- Search Input FEFO -->
                        <div class="relative w-44 sm:w-60">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="expirySearch"
                                   placeholder="Cari produk / batch..."
                                   class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        </div>

                        <!-- Reset Filter Button FEFO -->
                        @if ($expiryRiskFilter !== 'all' || $expirySearch || $fefoBranchId || $fefoSatuanFilter || $fefoSortBy !== 'days' || $fefoSortDir !== 'asc')
                            <button type="button" wire:click="resetFefoFilters"
                                    class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer inline-flex items-center gap-1.5 shrink-0"
                                    title="Reset semua filter dan sortir FEFO ke kondisi awal">
                                <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Reset</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tabel Data FEFO -->
            <div class="border border-slate-200/80 rounded-2xl overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead style="background: #f8faf9;" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">#</th>

                            <!-- Produk Satoria & Distributor (Sortable) -->
                            <th wire:click="setFefoSort('item_name')"
                                class="text-left py-3.5 px-4 cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Nama Produk">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="{{ $fefoSortBy === 'item_name' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'item_name' ? 'color: #0d6d5f;' : '' }}">Produk Satoria & Distributor</span>
                                    @if ($fefoSortBy === 'item_name')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>

                            <!-- Distributor / Cabang (Sortable) -->
                            <th wire:click="setFefoSort('distributor')"
                                class="text-left py-3.5 px-4 min-w-[190px] max-w-[240px] cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Cabang Distributor">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="{{ $fefoSortBy === 'distributor' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'distributor' ? 'color: #0d6d5f;' : '' }}">Distributor / Cabang</span>
                                    @if ($fefoSortBy === 'distributor')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>

                            <!-- No. Batch (Sortable) -->
                            <th wire:click="setFefoSort('batch_no')"
                                class="text-left py-3.5 px-4 w-28 cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Nomor Batch">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="{{ $fefoSortBy === 'batch_no' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'batch_no' ? 'color: #0d6d5f;' : '' }}">No. Batch</span>
                                    @if ($fefoSortBy === 'batch_no')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>

                            <!-- Expired Date (Sortable) -->
                            <th wire:click="setFefoSort('expired_date')"
                                class="text-center py-3.5 px-4 w-32 cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Tanggal Kedaluwarsa">
                                <div class="inline-flex items-center justify-center gap-1.5 w-full">
                                    <span class="{{ $fefoSortBy === 'expired_date' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'expired_date' ? 'color: #0d6d5f;' : '' }}">Expired Date</span>
                                    @if ($fefoSortBy === 'expired_date')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>

                            <!-- Sisa Waktu (Sortable) -->
                            <th wire:click="setFefoSort('days')"
                                class="text-center py-3.5 px-4 w-36 cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Sisa Waktu (FEFO)">
                                <div class="inline-flex items-center justify-center gap-1.5 w-full">
                                    <span class="{{ $fefoSortBy === 'days' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'days' ? 'color: #0d6d5f;' : '' }}">Sisa Waktu</span>
                                    @if ($fefoSortBy === 'days')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>

                            <!-- Kuantitas (Sortable) -->
                            <th wire:click="setFefoSort('quantity')"
                                class="text-right py-3.5 px-4 w-28 cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Kuantitas">
                                <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                    <span class="{{ $fefoSortBy === 'quantity' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'quantity' ? 'color: #0d6d5f;' : '' }}">Kuantitas</span>
                                    @if ($fefoSortBy === 'quantity')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>

                            <!-- Status & Rekomendasi (Sortable) -->
                            <th wire:click="setFefoSort('tier')"
                                class="text-left py-3.5 px-4 w-52 cursor-pointer select-none hover:bg-slate-100 transition group"
                                title="Klik untuk mengurutkan berdasarkan Status & Rekomendasi">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="{{ $fefoSortBy === 'tier' ? 'font-bold' : '' }}" style="{{ $fefoSortBy === 'tier' ? 'color: #0d6d5f;' : '' }}">Status & Rekomendasi</span>
                                    @if ($fefoSortBy === 'tier')
                                        @if ($fefoSortDir === 'asc')
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($fefoTable as $idx => $r)
                            @php
                                $e = $r->entry;
                                $ns = $e->distributorItem?->netsuiteItem;
                            @endphp
                            <tr class="hover:bg-[#f8faf9] transition duration-150">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono">
                                    {{ ($fefoTable->currentPage() - 1) * $perPage + $idx + 1 }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-xs">{{ $e->distributorItem?->item_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-500 mt-0.5">
                                        {{ $ns ? "[{$ns->netsuite_id}] {$ns->netsuite_name}" : 'Belum Ter-mapping' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900">{{ $e->distributor?->name }}</span>
                                    <span class="block text-[10px] font-mono text-slate-400 mt-0.5">{{ $e->distributor?->distributor_code }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    {{ $e->batch_no ?: '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">
                                    <span class="font-bold {{ $r->tier === 'expired' || $r->tier === 'critical' ? 'text-rose-700' : ($r->tier === 'warning' ? 'text-amber-700' : 'text-slate-700') }}">
                                        {{ $e->expired_date ? $e->expired_date->translatedFormat('d M Y') : '—' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">
                                    @if ($r->days < 0)
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 font-bold text-[10px]">
                                            Lewat {{ abs($r->days) }} hari
                                        </span>
                                    @elseif ($r->days <= 90)
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px]">
                                            {{ $r->days }} hari (~{{ round($r->days/30, 1) }} bln)
                                        </span>
                                    @elseif ($r->days <= 180)
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold text-[10px]">
                                            {{ $r->days }} hari (~{{ round($r->days/30, 1) }} bln)
                                        </span>
                                    @else
                                        <span class="text-emerald-700 font-bold text-[11px]">
                                            {{ $r->days }} hari
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-slate-900">
                                    {{ number_format($e->quantity, 0, ',', '.') }}
                                    <span class="block text-[10px] text-slate-400 font-normal">{{ $e->satuan }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-mono uppercase font-bold border {{ $r->badgeClass }}">
                                        {{ $r->label }}
                                    </span>
                                    <span class="block text-[10px] text-slate-500 mt-1 font-medium leading-tight">
                                        {{ $r->action }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="text-3xl mb-2">🗓️</div>
                                    <div class="font-bold text-slate-600 text-sm">Tidak ada data batch yang sesuai dengan filter kedaluwarsa</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination FEFO -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <b class="text-slate-800">{{ $fefoTable->firstItem() ?? 0 }}</b> - <b class="text-slate-800">{{ $fefoTable->lastItem() ?? 0 }}</b> dari <b class="text-slate-800">{{ $fefoTable->total() }}</b> batch
                </div>
                <div>
                    {{ $fefoTable->links('livewire::tailwind') }}
                </div>
            </div>
        </div>
    </div>
    @elseif ($activeTab === 'compliance')
    <!-- TAB 3: KEPATUHAN UPLOAD CABANG (COMPLIANCE TRACKER) -->
    <div class="space-y-6 mb-7">
        <!-- 4 Summary Cards Kepatuhan (Static Sans-serif without jumpy hover) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div class="text-[11px] uppercase text-slate-500 font-bold">TOTAL CABANG AKTIF</div>
                <div class="text-3xl font-extrabold mt-3 text-slate-900 tabular-nums tracking-tight">
                    {{ $complianceSummary['total_branches'] }} <span class="text-xs font-normal text-slate-500">gudang</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-100">Seluruh cabang terdaftar</div>
            </div>

            <div wire:click="$set('complianceStatus', 'submitted')" class="bg-white border {{ $complianceStatus === 'submitted' ? 'border-emerald-500 ring-2 ring-emerald-200 shadow-sm' : 'border-slate-200/80' }} rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-emerald-700 font-bold">✅ Sudah Lapor</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Tepat Waktu</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-emerald-700 tabular-nums tracking-tight">
                    {{ $complianceSummary['total_submitted'] }} <span class="text-xs font-normal text-slate-500">cabang</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Data stok terekam di tanggal acuan</div>
            </div>

            <div wire:click="$set('complianceStatus', 'missing')" class="bg-white border {{ $complianceStatus === 'missing' ? 'border-rose-500 ring-2 ring-rose-200 shadow-sm' : 'border-slate-200/80' }} rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-rose-700 font-bold">⚠️ Belum Lapor</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">Follow-up</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-rose-700 tabular-nums tracking-tight">
                    {{ $complianceSummary['total_missing'] }} <span class="text-xs font-normal text-slate-500">cabang</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Belum setor snapshot stok</div>
            </div>

            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-slate-600 font-bold">TINGKAT KEPATUHAN</span>
                    <span class="text-xs font-bold {{ $complianceSummary['compliance_rate'] >= 80 ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $complianceSummary['compliance_rate'] }}%
                    </span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-2.5 mt-3 overflow-hidden">
                    <div class="h-2.5 rounded-full transition-all duration-500"
                         style="width: {{ min(100, $complianceSummary['compliance_rate']) }}%; background: linear-gradient(90deg, #10b981 0%, #0d6d5f 100%);"></div>
                </div>
                <div class="text-[10px] text-slate-400 mt-2 pt-2 border-t border-slate-100 flex justify-between">
                    <span>Target: 100%</span>
                    <span>Acuan: <strong class="text-slate-600">{{ \Illuminate\Support\Carbon::parse($complianceSummary['target_date'])->translatedFormat('d M Y') }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Tabel Kepatuhan & Filter Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="pb-5 border-b border-slate-100 mb-5 space-y-3.5">
                <!-- Baris 1: Judul & Subtitle di kiri, Tgl Acuan & Status di kanan -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Status Kepatuhan Laporan Stok Cabang</h3>
                        <p class="text-xs text-slate-500 mt-1">Daftar cabang distributor dan status pengunggahan snapshot pada tanggal acuan</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Date Picker Tanggal Acuan -->
                        <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tgl Acuan:</span>
                            <div class="w-32">
                                <x-date-picker wire:model.live="complianceDate"
                                               class="w-full text-xs border-0 bg-transparent text-slate-900 font-bold focus:outline-none focus:ring-0 cursor-pointer p-0"
                                               placeholder="DD/MM/YYYY"
                                               title="Tanggal Acuan" />
                            </div>
                        </div>

                        <!-- Sub-Filter Buttons as Segmented Control -->
                        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs shadow-2xs">
                            <button type="button" wire:click="$set('complianceStatus', 'all')"
                                    class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $complianceStatus === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                Semua ({{ $complianceSummary['total_branches'] }})
                            </button>
                            <button type="button" wire:click="$set('complianceStatus', 'submitted')"
                                    class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $complianceStatus === 'submitted' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                Sudah ({{ $complianceSummary['total_submitted'] }})
                            </button>
                            <button type="button" wire:click="$set('complianceStatus', 'missing')"
                                    class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold {{ $complianceStatus === 'missing' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 hover:bg-rose-50' }}">
                                Belum ({{ $complianceSummary['total_missing'] }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Search Input dengan icon yang rapi -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="complianceSearch"
                               placeholder="Cari kode atau nama cabang..."
                               class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
                    </div>
                    <div class="text-xs text-slate-500 font-medium">
                        Total <span class="font-bold text-slate-800">{{ $complianceTable->total() }}</span> cabang terdaftar
                    </div>
                </div>
            </div>

            <!-- Tabel Data Kepatuhan -->
            <div class="border border-slate-200/80 rounded-2xl overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead style="background: #f8faf9;" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">#</th>
                            <th class="py-3.5 px-4 min-w-[220px] max-w-[270px]">Kode & Nama Distributor</th>
                            <th class="py-3.5 px-4 w-24 text-center">Grup</th>
                            <th class="py-3.5 px-4 w-40 text-center">Status Lapor</th>
                            <th class="py-3.5 px-4 w-40">Upload Terakhir</th>
                            <th class="py-3.5 px-4 w-40 text-right">Snapshot Terakhir</th>
                            <th class="py-3.5 px-4 w-36 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($complianceTable as $idx => $row)
                            <tr class="hover:bg-[#f8faf9] transition duration-150">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono">
                                    {{ ($complianceTable->currentPage() - 1) * $perPage + $idx + 1 }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-xs">{{ $row->distributor->name }}</div>
                                    <div class="font-mono text-[10px] text-slate-500 mt-0.5">{{ $row->distributor->distributor_code }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ \App\Livewire\Dashboard::getDistributorGroup($row->distributor->distributor_code) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if ($row->hasSubmitted)
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                            <span>✓ Sudah Lapor</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200 animate-pulse shadow-2xs">
                                            <span>✕ Belum Lapor</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono">
                                    @if ($row->lastDate)
                                        <div class="font-bold text-slate-800 text-[11px]">
                                            {{ \Illuminate\Support\Carbon::parse($row->lastDate)->translatedFormat('d M Y') }}
                                        </div>
                                        <span class="text-[10px] text-slate-500 mt-0.5 block">
                                            {{ $row->daysOverdue !== null ? ($row->daysOverdue === 0 ? 'Hari ini' : $row->daysOverdue . ' hari lalu') : '—' }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Belum pernah lapor</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono">
                                    @if ($row->lastDate)
                                        <div class="font-extrabold text-slate-900">{{ number_format($row->totalQty, 0, ',', '.') }}</div>
                                        <span class="text-[10px] text-slate-500 font-normal mt-0.5 block">{{ $row->totalRows }} baris</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <!-- Icon Riwayat -->
                                        <a href="{{ route('stock.history', ['distributor_id' => $row->distributor->id]) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-emerald-50 hover:bg-[#0d6d5f] hover:text-white border border-emerald-200/80 transition duration-150 shadow-2xs"
                                           title="Lihat Riwayat Stok Cabang">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </a>

                                        <!-- Icon Upload -->
                                        <a href="{{ route('stock.upload', ['distributor_id' => $row->distributor->id]) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white border border-blue-200/80 transition duration-150 shadow-2xs"
                                           title="Unggah Stok Cabang">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="text-3xl mb-2">🏢</div>
                                    <div class="font-bold text-slate-600 text-sm">Tidak ada data cabang distributor yang cocok dengan filter kepatuhan</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Kepatuhan -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <b class="text-slate-800">{{ $complianceTable->firstItem() ?? 0 }}</b> - <b class="text-slate-800">{{ $complianceTable->lastItem() ?? 0 }}</b> dari <b class="text-slate-800">{{ $complianceTable->total() }}</b> cabang
                </div>
                <div>
                    {{ $complianceTable->links('livewire::tailwind') }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- TAB 4: STOK MACET & SLOW-MOVING (DEAD STOCK ALERT) -->
    @if ($activeTab === 'stagnant')
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs mb-7">
        <!-- Toolbar Filter Tab 4 -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Stok Macet &amp; Slow-Moving (Dead Stock Alert)</h3>
                    @if ($stagnantSortBy !== 'days_stagnant' || $stagnantSortDir !== 'desc')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-mono bg-amber-50 text-amber-900 border border-amber-200">
                            <span>Sortir: <b>{{ match($stagnantSortBy) {
                                'quantity' => 'Stok Terkini',
                                'distributor' => 'Distributor',
                                'expired_date' => 'Expired Date',
                                'turnover_pct' => 'Perputaran (%)',
                                'item_name' => 'Nama Produk',
                                default => 'Hari Stagnan',
                            } }}</b> ({{ $stagnantSortDir === 'asc' ? 'Terkecil / A→Z' : 'Terbanyak / Z→A' }})</span>
                            <button type="button" wire:click="$set('stagnantSortBy', 'days_stagnant'); $set('stagnantSortDir', 'desc');" class="text-amber-700 hover:text-amber-950 ml-0.5 font-bold cursor-pointer" title="Kembalikan sortir default">&times;</button>
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Deteksi SKU yang tidak mengalami pergerakan stok atau perputarannya sangat lambat dalam {{ $stagnantPeriod }} hari terakhir
                </p>
            </div>

            <!-- Action Buttons: Export CSV & Reset -->
            <div class="flex items-center gap-2">
                <button type="button" wire:click="exportStagnantCsv"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Unduh CSV</span>
                </button>

                @if ($stagnantPeriod !== 30 || $stagnantRiskFilter !== 'all' || $stagnantBranchId || $stagnantSatuanFilter || $stagnantSearch || $stagnantSortBy !== 'days_stagnant' || $stagnantSortDir !== 'desc')
                    <button type="button" wire:click="resetStagnantFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Reset</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Filter Row (Independen) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 mb-5">
            <!-- Period Selector (14, 30, 60 Hari) -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Periode Evaluasi</label>
                <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200/80 text-xs">
                    <button type="button" wire:click="$set('stagnantPeriod', 14)"
                            class="flex-1 py-1.5 rounded-lg transition-all cursor-pointer {{ $stagnantPeriod === 14 ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        14 Hari
                    </button>
                    <button type="button" wire:click="$set('stagnantPeriod', 30)"
                            class="flex-1 py-1.5 rounded-lg transition-all cursor-pointer {{ $stagnantPeriod === 30 ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        30 Hari
                    </button>
                    <button type="button" wire:click="$set('stagnantPeriod', 60)"
                            class="flex-1 py-1.5 rounded-lg transition-all cursor-pointer {{ $stagnantPeriod === 60 ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                        60 Hari
                    </button>
                </div>
            </div>

            <!-- Filter Status Kategori -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status Pergerakan</label>
                <select wire:model.live="stagnantRiskFilter"
                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-[#0d6d5f] focus:outline-hidden">
                    <option value="all">Semua Risiko Macet &amp; Slow</option>
                    <option value="dead">Macet Total (Dead Stock - Outflow 0)</option>
                    <option value="slow">Pergerakan Lambat (&lt; 10%)</option>
                    <option value="critical_ed">Macet &amp; Dekat ED (&le; 6 Bulan)</option>
                </select>
            </div>

            <!-- Filter Cabang -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cabang</label>
                <select wire:model.live="stagnantBranchId"
                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-[#0d6d5f] focus:outline-hidden">
                    <option value="">Semua Cabang</option>
                    @foreach ($availableBranches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Satuan -->
            <div class="lg:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Satuan</label>
                <select wire:model.live="stagnantSatuanFilter"
                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-slate-800 focus:border-[#0d6d5f] focus:outline-hidden">
                    <option value="">Semua</option>
                    <option value="BTL">BTL</option>
                    <option value="AMP">AMP</option>
                    <option value="PCS">PCS</option>
                </select>
            </div>

            <!-- Pencarian -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pencarian</label>
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="stagnantSearch"
                           placeholder="Cari produk, batch, cabang..."
                           class="w-full text-xs rounded-xl border border-slate-200 bg-white pl-8 pr-3 py-2 text-slate-800 placeholder-slate-400 focus:border-[#0d6d5f] focus:outline-hidden">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 4 KPI Cards Khusus Tab 4 -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <!-- Card 1: Total SKU Mengendap -->
            <div class="bg-amber-50/50 rounded-xl border border-amber-200/80 p-3.5">
                <span class="text-[10px] font-bold tracking-wider uppercase text-amber-800">TOTAL SKU MENGENDAP</span>
                <div class="text-2xl font-extrabold text-amber-950 font-mono mt-1">
                    {{ $stagnantSummary['total'] ?? 0 }} <span class="text-xs font-normal text-amber-700">SKU</span>
                </div>
            </div>

            <!-- Card 2: Macet Total (Dead Stock) -->
            <div class="bg-rose-50/50 rounded-xl border border-rose-200/80 p-3.5">
                <span class="text-[10px] font-bold tracking-wider uppercase text-rose-800">MACET TOTAL (DEAD STOCK)</span>
                <div class="text-2xl font-extrabold text-rose-700 font-mono mt-1">
                    {{ $stagnantSummary['dead'] ?? 0 }} <span class="text-xs font-normal text-rose-600">SKU (Outflow 0)</span>
                </div>
            </div>

            <!-- Card 3: Macet & Kritis ED -->
            <div class="bg-purple-50/50 rounded-xl border border-purple-200/80 p-3.5">
                <span class="text-[10px] font-bold tracking-wider uppercase text-purple-800">MACET &amp; DEKAT ED (&le; 6 BLN)</span>
                <div class="text-2xl font-extrabold text-purple-700 font-mono mt-1">
                    {{ $stagnantSummary['critical_ed'] ?? 0 }} <span class="text-xs font-normal text-purple-600">Prioritas Tindakan</span>
                </div>
            </div>

            <!-- Card 4: Total Volume Terkunci -->
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-3.5">
                <span class="text-[10px] font-bold tracking-wider uppercase text-slate-600">TOTAL VOLUME TERKUNCI</span>
                <div class="text-2xl font-extrabold text-slate-800 font-mono mt-1">
                    {{ number_format($stagnantSummary['total_qty'] ?? 0, 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-500">Unit Fisik</span>
                </div>
            </div>
        </div>

        <!-- Tabel Detail Stok Macet & Slow-Moving -->
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-3 cursor-pointer hover:bg-slate-100 transition select-none" wire:click="setStagnantSort('item_name')">
                            <div class="flex items-center gap-1">
                                <span>Produk</span>
                                @if ($stagnantSortBy === 'item_name')
                                    <span>{{ $stagnantSortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-3 cursor-pointer hover:bg-slate-100 transition select-none" wire:click="setStagnantSort('distributor')">
                            <div class="flex items-center gap-1">
                                <span>Distributor / Cabang</span>
                                @if ($stagnantSortBy === 'distributor')
                                    <span>{{ $stagnantSortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-2 text-center">Satuan / Batch</th>
                        <th class="py-3 px-3 text-center cursor-pointer hover:bg-slate-100 transition select-none" wire:click="setStagnantSort('expired_date')">
                            <div class="flex items-center justify-center gap-1">
                                <span>Kedaluwarsa (ED)</span>
                                @if ($stagnantSortBy === 'expired_date')
                                    <span>{{ $stagnantSortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-3 text-right">Stok Awal</th>
                        <th class="py-3 px-3 text-right cursor-pointer hover:bg-slate-100 transition select-none" wire:click="setStagnantSort('quantity')">
                            <div class="flex items-center justify-end gap-1">
                                <span>Stok Terkini</span>
                                @if ($stagnantSortBy === 'quantity')
                                    <span>{{ $stagnantSortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-3 text-center cursor-pointer hover:bg-slate-100 transition select-none" wire:click="setStagnantSort('turnover_pct')">
                            <div class="flex items-center justify-center gap-1">
                                <span>Perputaran</span>
                                @if ($stagnantSortBy === 'turnover_pct')
                                    <span>{{ $stagnantSortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-3 text-center cursor-pointer hover:bg-slate-100 transition select-none" wire:click="setStagnantSort('days_stagnant')">
                            <div class="flex items-center justify-center gap-1">
                                <span>Hari Stagnan</span>
                                @if ($stagnantSortBy === 'days_stagnant')
                                    <span>{{ $stagnantSortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-3 text-center">Status &amp; Rekomendasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse ($stagnantTable as $row)
                        <tr class="hover:bg-amber-50/20 transition-colors">
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 leading-tight">
                                    {{ $row->distributorItem?->item_name ?? 'Produk Tanpa Nama' }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5 flex items-center gap-1.5">
                                    @if ($row->distributorItem?->source_item_id)
                                        <span>Code: {{ $row->distributorItem->source_item_id }}</span>
                                    @endif
                                    @if ($row->distributorItem?->netsuiteItem?->netsuite_name)
                                        &bull; <span class="text-emerald-700 font-semibold">{{ $row->distributorItem->netsuiteItem->netsuite_name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-slate-800">{{ $row->distributor?->name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono uppercase">{{ $row->distributor?->distributor_code ?? '' }}</div>
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <span class="inline-block px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono bg-slate-100 text-slate-700">
                                    {{ $row->entry->satuan }}
                                </span>
                                @if ($row->entry->batch_no)
                                    <div class="text-[10px] font-mono text-slate-500 mt-0.5">{{ $row->entry->batch_no }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if ($row->entry->expired_date)
                                    <div class="font-mono text-xs font-semibold {{ $row->isNearEd ? 'text-rose-700' : 'text-slate-700' }}">
                                        {{ $row->entry->expired_date->format('d/m/Y') }}
                                    </div>
                                    <div class="text-[10px] mt-0.5">
                                        @if ($row->entry->daysToExpiry() !== null)
                                            @if ($row->entry->daysToExpiry() < 0)
                                                <span class="text-rose-600 font-bold">Lewat ED</span>
                                            @elseif ($row->entry->daysToExpiry() <= 90)
                                                <span class="text-rose-600 font-bold">&lt; 3 Bln ({{ $row->entry->daysToExpiry() }} hr)</span>
                                            @elseif ($row->entry->daysToExpiry() <= 180)
                                                <span class="text-amber-600 font-semibold">3-6 Bln ({{ $row->entry->daysToExpiry() }} hr)</span>
                                            @else
                                                <span class="text-slate-400">&gt; 6 Bln</span>
                                            @endif
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono tabular-nums text-slate-600">
                                {{ number_format($row->qFirst, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono tabular-nums font-bold text-slate-900">
                                {{ number_format($row->qLatest, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono text-xs">
                                @if ($row->turnoverPct <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        0%
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        -{{ $row->turnoverPct }}%
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono">
                                <span class="font-bold text-slate-800">{{ $row->daysStagnant }}</span>
                                <span class="text-[10px] text-slate-400">hari</span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div>
                                    @if ($row->status === 'dead_stock')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800">
                                            Macet Total
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800">
                                            Slow-Moving
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-1">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold
                                        {{ match($row->actionColor) {
                                            'rose' => 'bg-rose-50 text-rose-700 border border-rose-200',
                                            'amber' => 'bg-amber-50 text-amber-800 border border-amber-200',
                                            'orange' => 'bg-orange-50 text-orange-800 border border-orange-200',
                                            'sky' => 'bg-sky-50 text-sky-800 border border-sky-200',
                                            default => 'bg-slate-100 text-slate-700',
                                        } }}">
                                        {{ $row->actionText }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="text-3xl mb-2">🎉</div>
                                <div class="font-bold text-slate-700 text-sm">Tidak ada stok macet atau lambat bergerak</div>
                                <div class="text-xs text-slate-400 mt-0.5">Semua produk pada filter ini memiliki perputaran stok yang lancar.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Tab 4 -->
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan <b class="text-slate-800">{{ $stagnantTable->firstItem() ?? 0 }}</b> - <b class="text-slate-800">{{ $stagnantTable->lastItem() ?? 0 }}</b> dari <b class="text-slate-800">{{ $stagnantTable->total() }}</b> SKU
            </div>
            <div>
                {{ $stagnantTable->links('livewire::tailwind') }}
            </div>
        </div>
    </div>
    @endif

    <!-- Scripts Chart.js -->
    @script
    <script>
        let chartTopInstance = null;
        let chartDonutInstance = null;
        let chartTrendInstance = null;
        let chartFefoInstance = null;

        function updateChartData(topData, donutData, trendData, fefoData) {
            const canvasTop = document.getElementById('chart-top-products');
            const noDataTop = document.getElementById('no-data-top');
            const canvasDonut = document.getElementById('chart-donut-dist');
            const noDataDonut = document.getElementById('no-data-donut');
            const canvasTrend = document.getElementById('chart-stock-trend');
            const noDataTrend = document.getElementById('no-data-trend');

            // 1. Line Chart (Trend Stock On Hand)
            if (canvasTrend && window.Chart) {
                if (chartTrendInstance) {
                    chartTrendInstance.destroy();
                    chartTrendInstance = null;
                }

                const hasTrendData = trendData && trendData.labels && trendData.labels.length > 0 &&
                    (trendData.data || []).some(v => v !== null && v !== undefined);

                if (!hasTrendData) {
                    canvasTrend.style.display = 'none';
                    if (noDataTrend) noDataTrend.classList.remove('hidden');
                } else {
                    canvasTrend.style.display = 'block';
                    if (noDataTrend) noDataTrend.classList.add('hidden');

                    const ctx = canvasTrend.getContext('2d');
                    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                    gradient.addColorStop(0, 'rgba(13, 109, 95, 0.22)');
                    gradient.addColorStop(1, 'rgba(13, 109, 95, 0.00)');

                    chartTrendInstance = new window.Chart(canvasTrend, {
                        type: 'line',
                        data: {
                            labels: trendData.labels,
                            datasets: [{
                                label: 'Total Stok (' + (trendData.unit || '') + ')',
                                data: trendData.data,
                                borderColor: '#0d6d5f',
                                backgroundColor: gradient,
                                borderWidth: 2.5,
                                tension: 0.3,
                                fill: true,
                                spanGaps: false, // JANGAN hubungkan garis jika data null (missing snapshot)
                                pointRadius: 3.5,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#0d6d5f',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                fullDates: trendData.full_dates || []
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                    titleFont: { size: 12, family: 'Plus Jakarta Sans', weight: 'bold' },
                                    bodyFont: { size: 12, family: 'JetBrains Mono' },
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        title: function(ctxList) {
                                            if (!ctxList.length) return '';
                                            const idx = ctxList[0].dataIndex;
                                            const fDates = ctxList[0].dataset.fullDates;
                                            return (fDates && fDates[idx]) ? fDates[idx] : ctxList[0].label;
                                        },
                                        label: function(ctx) {
                                            if (ctx.raw === null || ctx.raw === undefined) {
                                                return 'Tidak ada upload data (Libur/Belum setor)';
                                            }
                                            return 'Total Stok: ' + Number(ctx.raw).toLocaleString('id-ID') + ' ' + (trendData.unit || '');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: '#f1f5f9' },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 15
                                    }
                                },
                                y: {
                                    grid: { color: '#f1f5f9' },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        callback: val => Number(val).toLocaleString('id-ID')
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // 2. Horizontal Bar Chart (Top 10 Produk)
            if (canvasTop && window.Chart) {
                if (chartTopInstance) {
                    chartTopInstance.destroy();
                    chartTopInstance = null;
                }

                const hasTopData = topData && topData.labels && topData.labels.length > 0;
                if (!hasTopData) {
                    canvasTop.style.display = 'none';
                    if (noDataTop) noDataTop.classList.remove('hidden');
                } else {
                    canvasTop.style.display = 'block';
                    if (noDataTop) noDataTop.classList.add('hidden');

                    const isStacked = topData.datasets && topData.datasets.length > 1;
                    chartTopInstance = new window.Chart(canvasTop, {
                        type: 'bar',
                        data: topData,
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: isStacked,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 11, family: 'Plus Jakarta Sans' }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(ctx) {
                                            return ctx.dataset.label + ': ' + Number(ctx.raw || 0).toLocaleString('id-ID');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: isStacked,
                                    grid: { color: '#f1f5f9' },
                                    ticks: {
                                        font: { size: 10, family: 'JetBrains Mono' },
                                        callback: val => Number(val).toLocaleString('id-ID')
                                    }
                                },
                                y: {
                                    stacked: isStacked,
                                    grid: { display: false },
                                    ticks: {
                                        font: { size: 11, family: 'Plus Jakarta Sans' }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // 3. Donut Chart (Distribusi Stok)
            if (canvasDonut && window.Chart) {
                if (chartDonutInstance) {
                    chartDonutInstance.destroy();
                    chartDonutInstance = null;
                }

                const totalDonutQty = (donutData?.data || []).reduce((a, b) => a + Number(b || 0), 0);
                const hasDonutData = donutData && donutData.labels && donutData.labels.length > 0 && totalDonutQty > 0;

                if (!hasDonutData) {
                    canvasDonut.style.display = 'none';
                    if (noDataDonut) noDataDonut.classList.remove('hidden');
                } else {
                    canvasDonut.style.display = 'block';
                    if (noDataDonut) noDataDonut.classList.add('hidden');

                    chartDonutInstance = new window.Chart(canvasDonut, {
                        type: 'doughnut',
                        data: {
                            labels: donutData.labels,
                            datasets: [{
                                data: donutData.data,
                                backgroundColor: donutData.colors,
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 11, family: 'Plus Jakarta Sans' }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(ctx) {
                                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                            const val = ctx.raw || 0;
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                            return ctx.label + ': ' + Number(val).toLocaleString('id-ID') + ' (' + pct + '%)';
                                        }
                                    }
                                }
                            },
                            cutout: '65%'
                        }
                    });
                }
            }

            // 4. Horizontal Stacked Bar Chart (Horizon Kedaluwarsa Makro)
            const canvasFefo = document.getElementById('chart-fefo-horizon');
            const noDataFefo = document.getElementById('no-data-fefo');

            if (!canvasFefo && chartFefoInstance) {
                chartFefoInstance.destroy();
                chartFefoInstance = null;
            }

            if (canvasFefo && window.Chart) {
                if (chartFefoInstance) {
                    chartFefoInstance.destroy();
                    chartFefoInstance = null;
                }

                const hasFefoData = fefoData && fefoData.has_data && fefoData.labels && fefoData.labels.length > 0;
                if (!hasFefoData) {
                    canvasFefo.style.display = 'none';
                    if (noDataFefo) noDataFefo.classList.remove('hidden');
                } else {
                    canvasFefo.style.display = 'block';
                    if (noDataFefo) noDataFefo.classList.add('hidden');

                    chartFefoInstance = new window.Chart(canvasFefo, {
                        type: 'bar',
                        data: {
                            labels: fefoData.labels,
                            datasets: fefoData.datasets
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 11, family: 'Plus Jakarta Sans' }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                    titleFont: { size: 12, family: 'Plus Jakarta Sans', weight: 'bold' },
                                    bodyFont: { size: 12, family: 'JetBrains Mono' },
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(ctx) {
                                            const val = ctx.raw || 0;
                                            if (val === 0) return null;
                                            const label = ctx.dataset.label || '';
                                            return label + ': ' + Number(val).toLocaleString('id-ID') + ' ' + (fefoData.unit || '');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: { color: '#f1f5f9' },
                                    ticks: {
                                        font: { size: 10, family: 'JetBrains Mono' },
                                        callback: val => Number(val).toLocaleString('id-ID')
                                    }
                                },
                                y: {
                                    stacked: true,
                                    grid: { display: false },
                                    ticks: {
                                        font: { size: 11, family: 'Plus Jakarta Sans' }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        // 1. Initial Render from DOM data holder
        function readAndRender() {
            const holder = document.getElementById('charts-data-holder');
            if (holder) {
                try {
                    const top = JSON.parse(holder.getAttribute('data-top') || '{}');
                    const donut = JSON.parse(holder.getAttribute('data-donut') || '{}');
                    const trend = JSON.parse(holder.getAttribute('data-trend') || '{}');
                    const fefo = JSON.parse(holder.getAttribute('data-fefo') || '{}');
                    updateChartData(top, donut, trend, fefo);
                } catch(e) {
                    console.error('Error parsing chart data:', e);
                }
            }
        }

        readAndRender();

        let lastChartEventTime = 0;

        // 2. Event Listener dari Livewire Dispatch
        $wire.on('charts-updated', (payload) => {
            lastChartEventTime = Date.now();
            const data = Array.isArray(payload) ? payload[0] : payload;
            if (data) {
                updateChartData(data.top, data.donut, data.trend, data.fefo);
            }
        });

        // 3. Fail-safe hook saat morphing selesai (dilewati jika baru saja diperbarui via charts-updated)
        if (window.Livewire) {
            window.Livewire.hook('morph.updated', () => {
                if (Date.now() - lastChartEventTime > 300) {
                    readAndRender();
                }
            });
        }
    </script>
    @endscript
</div>
