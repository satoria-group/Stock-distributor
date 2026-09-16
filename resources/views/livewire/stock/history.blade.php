<div>
    <!-- Top Header Banner (Modern Hero Banner ala Satoria Logistics) -->
    <div class="relative overflow-hidden rounded-3xl text-white p-7 md:p-8 mb-7 shadow-xl border border-emerald-950/20 satoria-gradient-banner"
         style="background: linear-gradient(135deg, #062c25 0%, #09483e 50%, #0d6d5f 100%) !important; color: #ffffff !important;">
        <!-- Subtle Glow Effect in Background -->
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="max-w-3xl">
                <!-- Glowing Live Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs text-emerald-200 font-medium mb-3 shadow-2xs">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                    </span>
                    <span>Satoria Historical Audit Pipeline</span>
                </div>

                <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold tracking-tight text-white leading-tight">
                    @if ($selectedGroup === 'ALL')
                        Riwayat Arsip Stok Harian (Nasional)
                    @else
                        Riwayat Stok {{ $selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup }}
                        @if ($distributorId)
                            <span class="text-emerald-200 font-normal text-xl md:text-2xl">· {{ $availableBranches->firstWhere('id', $distributorId)?->name }}</span>
                        @endif
                    @endif
                </h1>

                <p class="text-xs md:text-sm text-emerald-100/80 mt-2 leading-relaxed max-w-2xl">
                    Arsip snapshot stok harian historis, perbandingan lintas tanggal cut-off, dan pelacakan audit nomor batch distributor.
                </p>

                <div class="flex items-center gap-3 mt-4 text-xs text-emerald-100/90 flex-wrap">
                    <div class="inline-flex items-center gap-1.5 bg-black/20 backdrop-blur-xs px-3 py-1 rounded-full border border-white/10">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Posisi Terkini: <b class="text-white">{{ $stats['latest_date'] ? \Carbon\Carbon::parse($stats['latest_date'])->translatedFormat('d M Y') : 'Belum ada data' }}</b></span>
                    </div>
                    <span class="text-emerald-300/40">•</span>
                    <span class="text-emerald-200/90 text-xs">Arsip Snapshot Terintegrasi</span>
                </div>
            </div>

            <!-- Distributor Group Tabs -->
            <div class="shrink-0">
                <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-200 mb-2 lg:text-right">
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

    @include('partials.flash-alert')

    <!-- 4 Kartu Metrik Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-7">
        <!-- Card 1: Total Snapshot -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">SNAPSHOT TERSIMPAN</span>
                <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-100/80 text-teal-700 flex items-center justify-center shrink-0">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2">
                    {{ number_format($stats['total_snapshots']) }}
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Arsip Tanggal × Cabang</div>
            </div>
        </div>

        <!-- Card 2: Distributor Aktif -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">DISTRIBUTOR AKTIF</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100/80 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-[#0d6d5f] tabular-nums tracking-tight mt-2">
                    {{ number_format($stats['active_distributors']) }}
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Cabang dengan data stok</div>
            </div>
        </div>

        <!-- Card 3: Snapshot Terkini -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">SNAPSHOT TERKINI</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100/80 text-blue-700 flex items-center justify-center shrink-0">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-xl font-extrabold text-slate-900 tracking-tight mt-2 truncate">
                    {{ $stats['latest_date'] ? \Carbon\Carbon::parse($stats['latest_date'])->translatedFormat('d M Y') : '—' }}
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Posisi tanggal cut-off terbaru</div>
            </div>
        </div>

        <!-- Card 4: Total Baris Data -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">TOTAL BARIS ENTRI</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100/80 text-indigo-700 flex items-center justify-center shrink-0">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight mt-2">
                    {{ number_format($stats['total_rows']) }}
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Akumulasi entri historis</div>
            </div>
        </div>
    </div>

    <!-- Tabel Arsip Snapshot dengan Toolbar Filter Terintegrasi -->
    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs mb-7">
        <!-- Toolbar Header & Controls -->
        <div class="p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white">
            <div>
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Arsip Snapshot Stok Harian</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Menampilkan <b class="text-slate-800">{{ $snapshots->total() }}</b> arsip snapshot tersimpan dalam database
                </p>
            </div>

            <!-- Filter Controls Inline -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Date Dari -->
                <div class="w-36">
                    <x-date-picker wire:model.live="startDate"
                                   class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition"
                                   placeholder="DD/MM/YYYY"
                                   title="Tanggal Mulai" />
                </div>

                <!-- Date Sampai -->
                <div class="w-36">
                    <x-date-picker wire:model.live="endDate"
                                   class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition"
                                   placeholder="DD/MM/YYYY"
                                   title="Tanggal Akhir" />
                </div>


                <!-- Filter Cabang -->
                <div class="w-56">
                    <select wire:model.live="distributorId"
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        <option value="">— Semua Cabang ({{ count($availableBranches) }}) —</option>
                        @foreach ($availableBranches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative w-48 sm:w-56">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Cari distributor / kode..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                </div>

                <!-- Reset Filter Button -->
                @if ($startDate || $endDate || $distributorId || $search || $selectedGroup !== 'ALL')
                    <button type="button" wire:click="resetFilters"
                            class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer inline-flex items-center gap-1.5">
                        Reset Filter
                    </button>
                @endif
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead class="bg-slate-50/80 text-slate-500 font-bold uppercase text-[11px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="text-center py-3.5 px-4 w-12">No</th>
                        <th class="text-left py-3.5 px-4 w-36 whitespace-nowrap">Tanggal Snapshot</th>
                        <th class="text-left py-3.5 px-4 w-60 min-w-[210px] max-w-[260px]">Distributor / Cabang</th>
                        <th class="text-center py-3.5 px-4 w-24">Grup</th>
                        <th class="text-center py-3.5 px-4 w-24">Total SKU</th>
                        <th class="text-right py-3.5 px-4 w-32 whitespace-nowrap">Total Kuantitas</th>
                        <th class="text-left py-3.5 px-4 w-36">Pengunggah</th>
                        <th class="text-left py-3.5 px-4 w-36 whitespace-nowrap">Waktu Input</th>
                        <th class="text-center py-3.5 px-4 w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($snapshots as $idx => $s)
                        @php
                            $grp = \App\Livewire\Dashboard::getDistributorGroup($s->distributor?->distributor_code);
                            $groupBadges = [
                                'KFTD' => 'bg-blue-50 text-blue-800 border border-blue-200',
                                'SDL' => 'bg-orange-50 text-orange-800 border border-orange-200',
                                'UDC' => 'bg-purple-50 text-purple-800 border border-purple-200',
                                'GMP' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                                'MAM' => 'bg-cyan-50 text-cyan-800 border border-cyan-200',
                                'OTHER' => 'bg-amber-50 text-amber-800 border border-amber-200',
                            ];
                            $dateKey = $s->tanggal->format('Y-m-d');
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="text-center py-3.5 px-4 text-slate-400 font-medium">
                                {{ ($snapshots->currentPage() - 1) * $snapshots->perPage() + $idx + 1 }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                {{ $s->tanggal->translatedFormat('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 w-60 min-w-[210px] max-w-[260px]">
                                <div class="font-semibold text-slate-900 leading-snug">
                                    {{ $s->distributor?->name ?? 'Distributor Tidak Diketahui' }}
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-0.5">
                                    {{ $s->distributor?->distributor_code ?? '—' }}
                                </div>
                            </td>
                            <td class="text-center py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $groupBadges[$grp] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    {{ $grp === 'OTHER' ? 'Lainnya' : $grp }}
                                </span>
                            </td>
                            <td class="text-center py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold text-[11px]">
                                    {{ $s->total_sku }} SKU
                                </span>
                            </td>
                            <td class="text-right py-3.5 px-4 font-extrabold text-slate-900 tabular-nums text-sm whitespace-nowrap">
                                {{ number_format((float) $s->total_quantity, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($s->is_automation)
                                    @if ($s->is_reviewed)
                                        <div class="flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                Auto
                                            </span>
                                            <span class="text-xs font-semibold text-slate-900" title="Telah direview oleh {{ $s->reviewer_name }}">
                                                {{ $s->reviewer_name ?: 'Reviewer' }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs">
                                                <svg class="w-2.5 h-2.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                                Otomasi Email
                                            </span>
                                            <span class="text-[10px] font-semibold text-amber-600 flex items-center gap-0.5">
                                                ⚠️ Perlu Review
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs font-medium text-slate-700 block truncate max-w-[150px]" title="{{ $s->uploader_display }}">
                                        {{ $s->uploader_display ?: ($s->uploader?->name ?? 'Sistem / Impor') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-slate-500 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($s->last_updated_at)->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Tombol Tandai Sudah di-Review (hanya muncul jika Otomasi & Belum Review) -->
                                    @if ($s->is_automation && ! $s->is_reviewed)
                                        <button type="button"
                                                wire:click="markAsReviewed('{{ $dateKey }}', {{ $s->distributor_id }})"
                                                wire:loading.attr="disabled"
                                                title="Tandai Sudah di-Review"
                                                class="p-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-600 transition cursor-pointer shadow-2xs hover:scale-105">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    @endif

                                    <!-- Tombol Detail (Kuning jika Otomasi & Belum Review, Normal Emerald jika sudah review / manual) -->
                                    <button type="button"
                                            wire:click="viewDetail('{{ $dateKey }}', {{ $s->distributor_id }})"
                                            title="{{ ($s->is_automation && ! $s->is_reviewed) ? 'Lihat Detail (Otomasi - Perlu Review)' : 'Lihat Detail Snapshot' }}"
                                            class="p-1.5 rounded-lg transition cursor-pointer shadow-2xs hover:scale-105 {{ ($s->is_automation && ! $s->is_reviewed) ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 ring-2 ring-amber-400/40' : 'bg-emerald-50 hover:bg-emerald-100 text-[#0d6d5f] border border-emerald-200/80' }}">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Unduh CSV -->
                                    <button type="button"
                                            wire:click="exportCsv('{{ $dateKey }}', {{ $s->distributor_id }})"
                                            title="Unduh CSV Snapshot"
                                            class="p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 transition cursor-pointer shadow-2xs hover:scale-105">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Edit di Grid Upload -->
                                    @can('create', \App\Models\StockEntry::class)
                                        <a href="{{ route('stock.upload', ['tanggal' => $dateKey, 'distributor_id' => $s->distributor_id]) }}"
                                           title="Buka & Koreksi di Form Upload"
                                           class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 transition cursor-pointer shadow-2xs hover:scale-105">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </a>
                                    @endcan

                                    <!-- Tombol Hapus Snapshot -->
                                    @can('delete', \App\Models\StockEntry::class)
                                        <button type="button"
                                                wire:click="confirmDeleteSnapshot('{{ $dateKey }}', {{ $s->distributor_id }})"
                                                wire:loading.attr="disabled"
                                                title="Hapus Snapshot Ini"
                                                class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 transition cursor-pointer shadow-2xs hover:scale-105">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-14 text-center text-slate-400">
                                <div class="text-3xl mb-2">📋</div>
                                <div class="font-medium text-slate-600 text-sm">Tidak ada riwayat snapshot yang cocok dengan filter</div>
                                <div class="text-xs text-slate-400 mt-1">Silakan sesuaikan filter tanggal atau pilihan grup distributor</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($snapshots->hasPages())
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $snapshots->links('livewire::tailwind') }}
            </div>
        @endif
    </div>

    <!-- MODAL DETAIL SNAPSHOT -->
    @if ($showDetailModal && $selectedSnapshot)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity"
             x-data="{ itemSearch: '' }">
            <div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-slate-200">
                <!-- Header Modal: Satoria Emerald Gradient Solid -->
                <div class="px-7 py-5 flex items-center justify-between text-white bg-gradient-to-r from-[#07352d] via-[#09483e] to-[#0d6d5f]">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono uppercase font-bold text-[#07352d] bg-white shadow-2xs">
                                Snapshot Detail
                            </span>
                            <span class="text-xs text-emerald-200 font-mono font-medium">
                                {{ $selectedSnapshot['tanggal_formatted'] }}
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-white mt-1.5 leading-tight">
                            {{ $selectedSnapshot['distributor_name'] }}
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-emerald-100/90 font-mono mt-1">
                            <span>Kode: <strong class="text-white bg-white/20 px-2 py-0.5 rounded-md">{{ $selectedSnapshot['distributor_code'] }}</strong></span>
                            <span>•</span>
                            <span>Grup: <strong class="text-white bg-white/20 px-2 py-0.5 rounded-md">{{ $selectedSnapshot['group'] }}</strong></span>
                        </div>
                    </div>
                    <button type="button" wire:click="closeDetailModal"
                            class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition cursor-pointer"
                            title="Tutup Modal">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Status Otomasi & Review Banner di Modal -->
                @if ($selectedSnapshot['is_automation'])
                    @if (! $selectedSnapshot['is_reviewed'])
                        <div class="px-7 py-3 bg-amber-50 border-b border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 text-xs text-amber-900 font-medium">
                                <span class="flex h-2.5 w-2.5 relative shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                                </span>
                                <span>Snapshot ini diimpor melalui <strong>Otomasi Email</strong> dan saat ini <strong>menunggu peninjauan (review)</strong>.</span>
                            </div>
                            <button type="button"
                                    wire:click="markAsReviewed('{{ $selectedSnapshot['tanggal'] }}', {{ $selectedSnapshot['distributor_id'] }})"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition cursor-pointer shadow-xs whitespace-nowrap">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Tandai Sudah di-Review
                            </button>
                        </div>
                    @else
                        <div class="px-7 py-2.5 bg-emerald-50/80 border-b border-emerald-100 flex items-center justify-between text-xs text-emerald-800">
                            <div class="flex items-center gap-2 font-medium">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Hasil Otomasi Email ini telah diverifikasi & di-review oleh <strong>{{ $selectedSnapshot['reviewer_name'] ?: 'Reviewer' }}</strong>.</span>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-100/70 px-2 py-0.5 rounded-md">Verified</span>
                        </div>
                    @endif
                @endif

                <!-- 6 Mini KPI Boxes -->
                <div class="p-5 bg-slate-50/80 border-b border-slate-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Total SKU</div>
                        <div class="text-lg font-bold text-slate-900 mt-1 font-mono">
                            {{ $selectedSnapshot['total_sku'] }} Item
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Total Kuantitas</div>
                        <div class="text-lg font-bold text-[#0d6d5f] mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_quantity'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Botol (Btl)</div>
                        <div class="text-lg font-bold text-teal-700 mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_btl'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Ampul (Amp)</div>
                        <div class="text-lg font-bold text-cyan-700 mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_amp'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Pcs / Box</div>
                        <div class="text-lg font-bold text-indigo-700 mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_pcs'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Batch Dekat ED</div>
                        <div class="text-lg font-bold {{ $selectedSnapshot['expiring_count'] > 0 ? 'text-rose-600' : 'text-emerald-700' }} mt-1 font-mono">
                            {{ $selectedSnapshot['expiring_count'] }} Batch
                        </div>
                    </div>
                </div>

                <!-- Search Input & Mapping Pill di Modal -->
                <div class="px-6 py-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" x-model="itemSearch"
                               placeholder="Cari nama produk, Netsuite, batch..."
                               class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/70 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        <button type="button" x-show="itemSearch" @click="itemSearch = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            Ter-mapping: <strong>{{ $selectedSnapshot['mapped_count'] }}</strong> Item
                        </span>
                        @if ($selectedSnapshot['unmapped_count'] > 0)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                Belum Mapped: <strong>{{ $selectedSnapshot['unmapped_count'] }}</strong> Item
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Tabel Item Produk Snapshot -->
                <div class="flex-1 overflow-y-auto max-h-[50vh]">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[11px] tracking-wider sticky top-0 border-b border-slate-200 z-10">
                            <tr>
                                <th class="text-center py-3.5 px-4 w-12">No</th>
                                <th class="text-left py-3.5 px-4 w-72">Nama Produk Distributor</th>
                                <th class="text-left py-3.5 px-4">Item Satoria (Netsuite)</th>
                                <th class="text-center py-3.5 px-3 w-20">Satuan</th>
                                <th class="text-right py-3.5 px-4 w-32">Kuantitas</th>
                                <th class="text-left py-3.5 px-4 w-32">No Batch</th>
                                <th class="text-center py-3.5 px-4 w-36">Expired Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($selectedSnapshot['items'] as $iIdx => $item)
                                <tr class="hover:bg-slate-50/70 transition"
                                    x-show="!itemSearch || $el.textContent.toLowerCase().includes(itemSearch.toLowerCase().trim())">
                                    <td class="text-center py-3.5 px-4 text-slate-400 font-mono">
                                        {{ $iIdx + 1 }}
                                    </td>
                                    <!-- Nama Produk Distributor -->
                                    <td class="py-3.5 px-4 font-bold text-slate-900">
                                        <div>{{ $item['item_name'] }}</div>
                                        @unless ($item['is_mapped'])
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-mono bg-amber-50 text-amber-800 border border-amber-200 font-normal">
                                                Unmapped
                                            </span>
                                        @endunless
                                    </td>
                                    <!-- Item Satoria Netsuite -->
                                    <td class="py-3.5 px-4">
                                        @if ($item['is_mapped'])
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $item['netsuite_name'] }}
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-100/80 text-[#07352d] font-mono font-bold text-[10px] border border-emerald-300/60">
                                                    {{ $item['netsuite_code'] }}
                                                </span>
                                                <span class="text-[10px] text-emerald-700 font-medium">✓ Mapped</span>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] bg-amber-50 text-amber-800 border border-amber-200">
                                                Belum di-mapping ke Netsuite
                                            </span>
                                        @endif
                                    </td>
                                    <!-- Satuan -->
                                    <td class="text-center py-3.5 px-3 font-mono text-slate-700">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-medium">
                                            {{ $item['satuan'] }}
                                        </span>
                                    </td>
                                    <!-- Kuantitas -->
                                    <td class="text-right py-3.5 px-4 font-extrabold font-mono text-slate-900 tabular-nums text-xs">
                                        {{ number_format($item['quantity'], 0, ',', '.') }}
                                    </td>
                                    <!-- No Batch -->
                                    <td class="py-3.5 px-4 font-mono text-slate-600">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-[11px]">
                                            {{ $item['batch_no'] }}
                                        </span>
                                    </td>
                                    <!-- Expired Date & Status ED -->
                                    <td class="text-center py-3.5 px-4 font-mono">
                                        @if ($item['expiry_status'] === 'expired')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] bg-red-100 text-red-800 font-bold border border-red-200">
                                                {{ $item['expired_date'] }} (Lewat ED)
                                            </span>
                                        @elseif ($item['expiry_status'] === 'critical')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 font-semibold border border-rose-200">
                                                {{ $item['expired_date'] }} (&lt; 3 bln)
                                            </span>
                                        @elseif ($item['expiry_status'] === 'warning')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-800 font-medium border border-amber-200">
                                                {{ $item['expired_date'] }} (&lt; 6 bln)
                                            </span>
                                        @else
                                            <span class="text-slate-600 text-[11px]">
                                                {{ $item['expired_date'] }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Riwayat Aktivitas & Edit (Audit Trail) -->
                @if (! empty($selectedSnapshot['activities']))
                    <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-200">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Riwayat Pengedit & Aktivitas ({{ count($selectedSnapshot['activities']) }})</span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-normal">Audit Trail</span>
                        </div>
                        <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">
                            @foreach ($selectedSnapshot['activities'] as $act)
                                <div class="flex items-center justify-between text-xs bg-white border border-slate-200/80 rounded-xl px-3 py-1.5 shadow-2xs">
                                    <div class="flex items-center gap-2">
                                        @if ($act['action'] === 'automation')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shrink-0">Otomasi</span>
                                        @elseif ($act['action'] === 'review')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0">Review</span>
                                        @elseif ($act['action'] === 'edit')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 shrink-0">Koreksi</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 shrink-0">Upload</span>
                                        @endif
                                        <span class="text-slate-800 font-medium">{{ $act['description'] }}</span>
                                    </div>
                                    <div class="text-slate-400 text-[11px] font-mono whitespace-nowrap ml-2">
                                        {{ $act['created_at'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div class="text-slate-600 flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Pengunggah / Reviewer: <strong class="text-slate-900">{{ $selectedSnapshot['uploader_name'] }}</strong>
                        pada <span class="font-mono text-slate-700">{{ $selectedSnapshot['updated_at'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Unduh CSV -->
                        <button type="button"
                                wire:click="exportCsv('{{ $selectedSnapshot['tanggal'] }}', {{ $selectedSnapshot['distributor_id'] }})"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-semibold text-xs transition cursor-pointer shadow-2xs">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Unduh CSV
                        </button>

                        <!-- Buka di Grid -->
                        @can('create', \App\Models\StockEntry::class)
                            <a href="{{ route('stock.upload', ['tanggal' => $selectedSnapshot['tanggal'], 'distributor_id' => $selectedSnapshot['distributor_id']]) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white bg-[#0d6d5f] hover:bg-[#07352d] font-bold text-xs shadow-2xs transition">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Buka di Form Upload
                            </a>
                        @endcan

                        <!-- Hapus Snapshot di Detail -->
                        @can('delete', \App\Models\StockEntry::class)
                            <button type="button"
                                    wire:click="confirmDeleteSnapshot('{{ $selectedSnapshot['tanggal'] }}', {{ $selectedSnapshot['distributor_id'] }})"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 font-semibold text-xs transition cursor-pointer">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Hapus
                            </button>
                        @endcan

                        <!-- Tutup -->
                        <button type="button" wire:click="closeDetailModal"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 text-xs font-semibold transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL KONFIRMASI HAPUS SNAPSHOT -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
             x-data
             @keydown.escape.window="$wire.cancelDeleteSnapshot()">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
                <!-- Header Modal: Rose/Red Solid Accent -->
                <div class="px-6 py-5 bg-gradient-to-r from-rose-600 to-red-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center shrink-0">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white leading-tight">Hapus Snapshot Stok?</h3>
                            <p class="text-xs text-rose-100/90 mt-0.5">Konfirmasi penghapusan permanen data</p>
                        </div>
                    </div>
                    <button type="button" wire:click="cancelDeleteSnapshot"
                            class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-4">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Anda akan menghapus seluruh entri stok distributor pada tanggal cut-off berikut:
                    </p>

                    <!-- Card Ringkasan Snapshot yang akan Dihapus -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Distributor:</span>
                            <span class="font-bold text-slate-900 text-right">{{ $deleteDistributorName }}</span>
                        </div>
                        @if ($deleteDistributorCode)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Kode Cabang:</span>
                                <span class="font-mono font-semibold text-slate-800">{{ $deleteDistributorCode }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Tanggal Snapshot:</span>
                            <span class="font-bold text-slate-900">
                                {{ $deleteTanggal ? \Carbon\Carbon::parse($deleteTanggal)->translatedFormat('d M Y') : '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200/60 pt-2">
                            <span class="text-slate-500 font-medium">Total SKU:</span>
                            <span class="font-bold text-slate-900">{{ number_format($deleteTotalSku ?? 0) }} Item</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Total Kuantitas:</span>
                            <span class="font-extrabold text-rose-600 tabular-nums">
                                {{ number_format($deleteTotalQuantity ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Warning Alert -->
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="leading-relaxed">
                            Data yang dihapus <strong>tidak dapat dikembalikan</strong>. Metrik riwayat dan dashboard akan langsung diperbarui.
                        </span>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" wire:click="cancelDeleteSnapshot"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteSnapshot"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white bg-rose-600 hover:bg-rose-700 font-bold text-xs shadow-xs transition cursor-pointer disabled:opacity-50">
                        <span wire:loading.remove wire:target="deleteSnapshot" class="inline-flex items-center gap-1.5">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Ya, Hapus Snapshot
                        </span>
                        <span wire:loading wire:target="deleteSnapshot" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menghapus...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

