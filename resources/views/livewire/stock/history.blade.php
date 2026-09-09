<div>
    <!-- Top Header Banner (Seragam dengan Dashboard Satoria) -->
    <div class="text-white rounded-2xl p-6 mb-6 shadow-md border border-emerald-900/30"
         style="background: linear-gradient(135deg, #0d6d5f 0%, #07352d 100%); color: #ffffff;">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div>
                <div class="text-[11px] font-mono tracking-widest uppercase text-emerald-200 font-semibold mb-1">
                    Satoria Internal Logistics Control
                </div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-white">
                    @if ($selectedGroup === 'ALL')
                        RIWAYAT ARSIP STOK HARIAN (NASIONAL)
                    @else
                        RIWAYAT STOK {{ $selectedGroup === 'OTHER' ? 'DISTRIBUTOR LAINNYA' : $selectedGroup }}
                        @if ($distributorId)
                            <span class="text-base font-normal text-emerald-200">· {{ $availableBranches->firstWhere('id', $distributorId)?->name }}</span>
                        @endif
                    @endif
                </h1>
                <div class="flex items-center gap-2 mt-2 text-xs text-emerald-100 flex-wrap">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Posisi Terkini: <b>{{ $stats['latest_date'] ? \Carbon\Carbon::parse($stats['latest_date'])->translatedFormat('d F Y') : 'Belum ada data' }}</b></span>
                    <span class="text-emerald-300/60">•</span>
                    <span>Arsip snapshot harian & audit batch distributor</span>
                </div>
            </div>

            <!-- Distributor Group Tabs (Embedded Looker Studio Style) -->
            <div class="flex items-center gap-1.5 p-1.5 rounded-xl flex-wrap" style="background-color: rgba(0, 0, 0, 0.25);">
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
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {{ $selectedGroup === $gKey ? 'bg-white text-[#07352d] shadow-sm font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}">
                        {{ $gLabel }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 4 Kartu Metrik Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <!-- Card 1: Total Snapshot -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Snapshot Tersimpan</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                {{ number_format($stats['total_snapshots']) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Arsip Tanggal × Cabang</div>
        </div>

        <!-- Card 2: Distributor Aktif -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Distributor Aktif</div>
            <div class="text-2xl font-bold mt-1 text-[#0d6d5f] tabular-nums">
                {{ number_format($stats['active_distributors']) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Cabang dengan data stok</div>
        </div>

        <!-- Card 3: Snapshot Terkini -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Snapshot Terkini</div>
            <div class="text-xl font-bold mt-1 text-gray-900 truncate">
                {{ $stats['latest_date'] ? \Carbon\Carbon::parse($stats['latest_date'])->translatedFormat('d M Y') : '—' }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Posisi tanggal terbaru</div>
        </div>

        <!-- Card 4: Total Baris Data -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Total Baris Entri</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                {{ number_format($stats['total_rows']) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Akumulasi entri historis</div>
        </div>
    </div>

    <!-- Tabel Arsip Snapshot dengan Toolbar Filter Terintegrasi -->
    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden shadow-xs">
        <!-- Toolbar Header & Controls -->
        <div class="p-4 border-b border-[#e7e9e3] flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Arsip Snapshot Stok Harian</h2>
                <p class="text-xs text-gray-500">
                    Menampilkan {{ $snapshots->total() }} arsip snapshot tersimpan
                </p>
            </div>

            <!-- Filter Controls Inline -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Date Dari -->
                <div class="w-36">
                    <input type="date" wire:model.live="startDate"
                           class="w-full text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand"
                           title="Tanggal Mulai">
                </div>

                <!-- Date Sampai -->
                <div class="w-36">
                    <input type="date" wire:model.live="endDate"
                           class="w-full text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand"
                           title="Tanggal Akhir">
                </div>

                <!-- Filter Cabang -->
                <div class="w-56">
                    <select wire:model.live="distributorId"
                            class="w-full text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
                        <option value="">— Semua Cabang ({{ count($availableBranches) }}) —</option>
                        @foreach ($availableBranches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative w-48 sm:w-56">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Cari distributor / kode..."
                           class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
                </div>

                <!-- Reset Filter Button -->
                @if ($startDate || $endDate || $distributorId || $search || $selectedGroup !== 'ALL')
                    <button type="button" wire:click="resetFilters"
                            class="px-2.5 py-2 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 border border-gray-200 transition cursor-pointer">
                        Reset Filter
                    </button>
                @endif
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-[#eef1ea] text-gray-600 font-mono uppercase text-[11px] border-b border-[#e7e9e3]">
                    <tr>
                        <th class="text-center py-2.5 px-3 w-12">No</th>
                        <th class="text-left py-2.5 px-4 w-36">Tanggal Snapshot</th>
                        <th class="text-left py-2.5 px-4">Distributor / Cabang</th>
                        <th class="text-center py-2.5 px-3 w-24">Grup</th>
                        <th class="text-center py-2.5 px-3 w-24">Total SKU</th>
                        <th class="text-right py-2.5 px-4 w-32">Total Kuantitas</th>
                        <th class="text-left py-2.5 px-4 w-40">Pengunggah</th>
                        <th class="text-left py-2.5 px-4 w-36">Waktu Input</th>
                        <th class="text-center py-2.5 px-4 w-44">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e7e9e3]">
                    @forelse ($snapshots as $idx => $s)
                        @php
                            $grp = \App\Livewire\Dashboard::getDistributorGroup($s->distributor?->distributor_code);
                            $groupBadges = [
                                'KFTD' => 'bg-blue-100 text-blue-800',
                                'SDL' => 'bg-orange-100 text-orange-800',
                                'UDC' => 'bg-purple-100 text-purple-800',
                                'GMP' => 'bg-emerald-100 text-emerald-800',
                                'MAM' => 'bg-cyan-100 text-cyan-800',
                                'OTHER' => 'bg-amber-100 text-amber-800',
                            ];
                            $dateKey = $s->tanggal->format('Y-m-d');
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="text-center py-2.5 px-3 text-gray-400 font-mono">
                                {{ ($snapshots->currentPage() - 1) * $snapshots->perPage() + $idx + 1 }}
                            </td>
                            <td class="py-2.5 px-4 font-mono font-semibold text-gray-900 whitespace-nowrap">
                                {{ $s->tanggal->translatedFormat('d M Y') }}
                            </td>
                            <td class="py-2.5 px-4">
                                <div class="font-semibold text-gray-900">
                                    {{ $s->distributor?->name ?? 'Distributor Tidak Diketahui' }}
                                </div>
                                <div class="text-[11px] font-mono text-gray-400">
                                    {{ $s->distributor?->distributor_code ?? '—' }}
                                </div>
                            </td>
                            <td class="text-center py-2.5 px-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-mono font-medium {{ $groupBadges[$grp] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $grp === 'OTHER' ? 'Lainnya' : $grp }}
                                </span>
                            </td>
                            <td class="text-center py-2.5 px-3 font-mono">
                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">
                                    {{ $s->total_sku }} SKU
                                </span>
                            </td>
                            <td class="text-right py-2.5 px-4 font-bold text-gray-900 font-mono tabular-nums">
                                {{ number_format((float) $s->total_quantity, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-4 text-gray-600 truncate">
                                {{ $s->uploader?->name ?? 'Sistem / Impor' }}
                            </td>
                            <td class="py-2.5 px-4 text-[11px] text-gray-500 font-mono">
                                {{ \Carbon\Carbon::parse($s->last_updated_at)->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Tombol Detail -->
                                    <button type="button"
                                            wire:click="viewDetail('{{ $dateKey }}', {{ $s->distributor_id }})"
                                            style="background-color: #0d6d5f; color: #ffffff;"
                                            class="px-2.5 py-1 rounded hover:opacity-90 text-xs font-medium transition cursor-pointer">
                                        Detail
                                    </button>

                                    <!-- Tombol Unduh CSV -->
                                    <button type="button"
                                            wire:click="exportCsv('{{ $dateKey }}', {{ $s->distributor_id }})"
                                            title="Unduh CSV"
                                            class="px-2 py-1 rounded border border-gray-200 text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                                        CSV
                                    </button>

                                    <!-- Tombol Edit di Grid Upload -->
                                    @can('create', \App\Models\StockEntry::class)
                                        <a href="{{ route('stock.upload', ['tanggal' => $dateKey, 'distributor_id' => $s->distributor_id]) }}"
                                           title="Buka & Koreksi di Form Upload"
                                           class="px-2 py-1 rounded border border-gray-200 text-gray-600 hover:bg-gray-100 transition">
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                <div class="text-2xl mb-1">📋</div>
                                <div class="font-medium text-gray-600">Tidak ada riwayat snapshot yang cocok dengan filter.</div>
                                <div class="text-xs text-gray-400 mt-0.5">Silakan sesuaikan filter tanggal atau pilihan grup distributor.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($snapshots->hasPages())
            <div class="p-3 border-t border-[#e7e9e3] bg-white">
                {{ $snapshots->links('livewire::tailwind') }}
            </div>
        @endif
    </div>

    <!-- MODAL DETAIL SNAPSHOT (POPUP ELEGAN BERWARNA SATORIA EMERALD) -->
    @if ($showDetailModal && $selectedSnapshot)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity"
             x-data="{ itemSearch: '' }">
            <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-[#e7e9e3]">
                <!-- Header Modal: Satoria Emerald Gradient Solid -->
                <div class="px-6 py-4 flex items-center justify-between text-white"
                     style="background: linear-gradient(135deg, #0d6d5f 0%, #07352d 100%); color: #ffffff;">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-mono uppercase font-bold text-[#07352d] bg-white">
                                Snapshot Detail
                            </span>
                            <span class="text-xs text-emerald-100 font-mono font-medium">
                                {{ $selectedSnapshot['tanggal_formatted'] }}
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-white mt-1.5 leading-tight">
                            {{ $selectedSnapshot['distributor_name'] }}
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-emerald-100/90 font-mono mt-1">
                            <span>Kode: <strong class="text-white bg-white/20 px-1.5 py-0.5 rounded">{{ $selectedSnapshot['distributor_code'] }}</strong></span>
                            <span>•</span>
                            <span>Grup: <strong class="text-white bg-white/20 px-1.5 py-0.5 rounded">{{ $selectedSnapshot['group'] }}</strong></span>
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

                <!-- 6 Mini KPI Boxes -->
                <div class="p-4 bg-[#f6f5f1] border-b border-[#e7e9e3] grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <div class="bg-white border border-[#e7e9e3] rounded-xl p-3 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Total SKU</div>
                        <div class="text-lg font-bold text-gray-900 mt-1 font-mono">
                            {{ $selectedSnapshot['total_sku'] }} Item
                        </div>
                    </div>
                    <div class="bg-white border border-[#e7e9e3] rounded-xl p-3 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Total Kuantitas</div>
                        <div class="text-lg font-bold text-[#0d6d5f] mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_quantity'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-[#e7e9e3] rounded-xl p-3 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Botol (Btl)</div>
                        <div class="text-lg font-bold text-emerald-700 mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_btl'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-[#e7e9e3] rounded-xl p-3 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Ampul (Amp)</div>
                        <div class="text-lg font-bold text-cyan-700 mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_amp'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-[#e7e9e3] rounded-xl p-3 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Pcs / Box</div>
                        <div class="text-lg font-bold text-amber-700 mt-1 font-mono tabular-nums">
                            {{ number_format($selectedSnapshot['total_pcs'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white border border-[#e7e9e3] rounded-xl p-3 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Batch Dekat ED</div>
                        <div class="text-lg font-bold {{ $selectedSnapshot['expiring_count'] > 0 ? 'text-red-600' : 'text-emerald-700' }} mt-1 font-mono">
                            {{ $selectedSnapshot['expiring_count'] }} Batch
                        </div>
                    </div>
                </div>

                <!-- Search Input & Mapping Pill di Modal -->
                <div class="px-6 py-3 border-b border-[#e7e9e3] flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" x-model="itemSearch"
                               placeholder="Cari nama produk, Netsuite, batch..."
                               class="w-full pl-8.5 pr-8 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
                        <button type="button" x-show="itemSearch" @click="itemSearch = ''"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            Ter-mapping: <strong>{{ $selectedSnapshot['mapped_count'] }}</strong> Item
                        </span>
                        @if ($selectedSnapshot['unmapped_count'] > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                Belum Mapped: <strong>{{ $selectedSnapshot['unmapped_count'] }}</strong> Item
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Tabel Item Produk Snapshot (Dengan Kolom Netsuite yang Jelas & Terlihat) -->
                <div class="flex-1 overflow-y-auto max-h-[50vh]">
                    <table class="w-full text-xs">
                        <thead class="bg-[#eef1ea] text-gray-700 font-mono uppercase text-[11px] sticky top-0 border-b border-[#e7e9e3] z-10">
                            <tr>
                                <th class="text-center py-2.5 px-3 w-10">No</th>
                                <th class="text-left py-2.5 px-4 w-72">Nama Produk Distributor</th>
                                <th class="text-left py-2.5 px-4">Item Satoria (Netsuite)</th>
                                <th class="text-center py-2.5 px-2 w-16">Satuan</th>
                                <th class="text-right py-2.5 px-3 w-28">Kuantitas</th>
                                <th class="text-left py-2.5 px-3 w-28">No Batch</th>
                                <th class="text-center py-2.5 px-3 w-32">Expired Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e7e9e3]">
                            @foreach ($selectedSnapshot['items'] as $iIdx => $item)
                                <tr class="hover:bg-gray-50 transition"
                                    x-show="!itemSearch || $el.textContent.toLowerCase().includes(itemSearch.toLowerCase().trim())">
                                    <td class="text-center py-2.5 px-3 text-gray-400 font-mono">
                                        {{ $iIdx + 1 }}
                                    </td>
                                    <!-- Nama Produk Distributor -->
                                    <td class="py-2.5 px-4 font-semibold text-gray-900">
                                        <div>{{ $item['item_name'] }}</div>
                                        @unless ($item['is_mapped'])
                                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-mono bg-amber-100 text-amber-800 border border-amber-200">
                                                Unmapped
                                            </span>
                                        @endunless
                                    </td>
                                    <!-- Item Satoria Netsuite (Kini Menampilkan Nama & Kode Netsuite Resmi) -->
                                    <td class="py-2.5 px-4">
                                        @if ($item['is_mapped'])
                                            <div class="font-semibold text-gray-900 text-xs">
                                                {{ $item['netsuite_name'] }}
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-100/70 text-[#07352d] font-mono font-bold text-[10px] border border-emerald-300/60">
                                                    {{ $item['netsuite_code'] }}
                                                </span>
                                                <span class="text-[10px] text-emerald-700 font-medium">✓ Mapped</span>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] bg-amber-50 text-amber-800 border border-amber-200">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                Belum di-mapping ke Netsuite
                                            </span>
                                        @endif
                                    </td>
                                    <!-- Satuan -->
                                    <td class="text-center py-2.5 px-2 font-mono text-gray-700">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px]">
                                            {{ $item['satuan'] }}
                                        </span>
                                    </td>
                                    <!-- Kuantitas -->
                                    <td class="text-right py-2.5 px-3 font-bold font-mono text-gray-900 tabular-nums text-xs">
                                        {{ number_format($item['quantity'], 0, ',', '.') }}
                                    </td>
                                    <!-- No Batch -->
                                    <td class="py-2.5 px-3 font-mono text-gray-600">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-50 border border-gray-200 text-gray-700 text-[11px]">
                                            {{ $item['batch_no'] }}
                                        </span>
                                    </td>
                                    <!-- Expired Date & Status ED -->
                                    <td class="text-center py-2.5 px-3 font-mono">
                                        @if ($item['expiry_status'] === 'expired')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-red-100 text-red-800 font-bold border border-red-200">
                                                {{ $item['expired_date'] }} (Lewat ED)
                                            </span>
                                        @elseif ($item['expiry_status'] === 'critical')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 font-semibold border border-rose-200">
                                                {{ $item['expired_date'] }} (&lt; 3 bln)
                                            </span>
                                        @elseif ($item['expiry_status'] === 'warning')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-800 font-medium border border-amber-200">
                                                {{ $item['expired_date'] }} (&lt; 6 bln)
                                            </span>
                                        @else
                                            <span class="text-gray-600 text-[11px]">
                                                {{ $item['expired_date'] }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-3.5 bg-[#f6f5f1] border-t border-[#e7e9e3] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div class="text-gray-600 flex items-center gap-1.5">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Diunggah oleh <strong class="text-gray-900">{{ $selectedSnapshot['uploader_name'] }}</strong>
                        pada <span class="font-mono text-gray-700">{{ $selectedSnapshot['updated_at'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Unduh CSV -->
                        <button type="button"
                                wire:click="exportCsv('{{ $selectedSnapshot['tanggal'] }}', {{ $selectedSnapshot['distributor_id'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#e7e9e3] bg-white text-gray-700 hover:bg-gray-100 font-medium text-xs transition cursor-pointer shadow-2xs">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Unduh CSV
                        </button>

                        <!-- Buka di Grid -->
                        @can('create', \App\Models\StockEntry::class)
                            <a href="{{ route('stock.upload', ['tanggal' => $selectedSnapshot['tanggal'], 'distributor_id' => $selectedSnapshot['distributor_id']]) }}"
                               style="background-color: #0d6d5f; color: #ffffff;"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-white hover:opacity-90 font-semibold text-xs shadow-xs transition">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Buka di Form Upload
                            </a>
                        @endcan

                        <!-- Tutup -->
                        <button type="button" wire:click="closeDetailModal"
                                class="px-3.5 py-1.5 rounded-lg border border-[#e7e9e3] bg-white text-gray-600 hover:bg-gray-100 text-xs font-medium transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
