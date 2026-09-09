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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL'): ?>
                        Riwayat Arsip Stok Harian (Nasional)
                    <?php else: ?>
                        Riwayat Stok <?php echo e($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($distributorId): ?>
                            <span class="text-emerald-200 font-normal text-xl md:text-2xl">· <?php echo e($availableBranches->firstWhere('id', $distributorId)?->name); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </h1>

                <p class="text-xs md:text-sm text-emerald-100/80 mt-2 leading-relaxed max-w-2xl">
                    Arsip snapshot stok harian historis, perbandingan lintas tanggal cut-off, dan pelacakan audit nomor batch distributor.
                </p>

                <div class="flex items-center gap-3 mt-4 text-xs text-emerald-100/90 flex-wrap">
                    <div class="inline-flex items-center gap-1.5 bg-black/20 backdrop-blur-xs px-3 py-1 rounded-full border border-white/10">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Posisi Terkini: <b class="text-white"><?php echo e($stats['latest_date'] ? \Carbon\Carbon::parse($stats['latest_date'])->translatedFormat('d F Y') : 'Belum ada data'); ?></b></span>
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
                    <?php
                        $groups = [
                            'ALL' => 'Ringkasan',
                            'GMP' => 'GMP',
                            'KFTD' => 'KFTD',
                            'MAM' => 'MAM',
                            'SDL' => 'SDL',
                            'UDC' => 'UDC',
                            'OTHER' => 'Lainnya',
                        ];
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gKey => $gLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" wire:click="$set('selectedGroup', '<?php echo e($gKey); ?>')"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition duration-150 cursor-pointer"
                                style="<?php echo e($selectedGroup === $gKey ? 'background: #ffffff; color: #07352d; box-shadow: 0 2px 8px rgba(0,0,0,0.18); font-weight: 800;' : 'color: #d1fae5; background: transparent;'); ?>">
                            <?php echo e($gLabel); ?>

                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
                    <?php echo e(number_format($stats['total_snapshots'])); ?>

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
                    <?php echo e(number_format($stats['active_distributors'])); ?>

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
                    <?php echo e($stats['latest_date'] ? \Carbon\Carbon::parse($stats['latest_date'])->translatedFormat('d M Y') : '—'); ?>

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
                    <?php echo e(number_format($stats['total_rows'])); ?>

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
                    Menampilkan <b class="text-slate-800"><?php echo e($snapshots->total()); ?></b> arsip snapshot tersimpan dalam database
                </p>
            </div>

            <!-- Filter Controls Inline -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Date Dari -->
                <div class="w-36">
                    <input type="date" wire:model.live="startDate"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition"
                           title="Tanggal Mulai">
                </div>

                <!-- Date Sampai -->
                <div class="w-36">
                    <input type="date" wire:model.live="endDate"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition"
                           title="Tanggal Akhir">
                </div>

                <!-- Filter Cabang -->
                <div class="w-56">
                    <select wire:model.live="distributorId"
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        <option value="">— Semua Cabang (<?php echo e(count($availableBranches)); ?>) —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $availableBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($b->id); ?>"><?php echo e($b->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($startDate || $endDate || $distributorId || $search || $selectedGroup !== 'ALL'): ?>
                    <button type="button" wire:click="resetFilters"
                            class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer inline-flex items-center gap-1.5">
                        Reset Filter
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $snapshots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
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
                        ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="text-center py-3.5 px-4 text-slate-400 font-medium">
                                <?php echo e(($snapshots->currentPage() - 1) * $snapshots->perPage() + $idx + 1); ?>

                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                <?php echo e($s->tanggal->translatedFormat('d M Y')); ?>

                            </td>
                            <td class="py-3.5 px-4 w-60 min-w-[210px] max-w-[260px]">
                                <div class="font-semibold text-slate-900 leading-snug">
                                    <?php echo e($s->distributor?->name ?? 'Distributor Tidak Diketahui'); ?>

                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-0.5">
                                    <?php echo e($s->distributor?->distributor_code ?? '—'); ?>

                                </div>
                            </td>
                            <td class="text-center py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo e($groupBadges[$grp] ?? 'bg-slate-100 text-slate-700 border border-slate-200'); ?>">
                                    <?php echo e($grp === 'OTHER' ? 'Lainnya' : $grp); ?>

                                </span>
                            </td>
                            <td class="text-center py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold text-[11px]">
                                    <?php echo e($s->total_sku); ?> SKU
                                </span>
                            </td>
                            <td class="text-right py-3.5 px-4 font-extrabold text-slate-900 tabular-nums text-sm whitespace-nowrap">
                                <?php echo e(number_format((float) $s->total_quantity, 0, ',', '.')); ?>

                            </td>
                            <td class="py-3.5 px-4 text-slate-600 truncate">
                                <?php echo e($s->uploader?->name ?? 'Sistem / Impor'); ?>

                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-slate-500 whitespace-nowrap">
                                <?php echo e(\Carbon\Carbon::parse($s->last_updated_at)->translatedFormat('d M Y, H:i')); ?>

                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Tombol Detail -->
                                    <button type="button"
                                            wire:click="viewDetail('<?php echo e($dateKey); ?>', <?php echo e($s->distributor_id); ?>)"
                                            title="Lihat Detail Snapshot"
                                            class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-[#0d6d5f] border border-emerald-200/80 transition cursor-pointer shadow-2xs hover:scale-105">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Unduh CSV -->
                                    <button type="button"
                                            wire:click="exportCsv('<?php echo e($dateKey); ?>', <?php echo e($s->distributor_id); ?>)"
                                            title="Unduh CSV Snapshot"
                                            class="p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 transition cursor-pointer shadow-2xs hover:scale-105">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Edit di Grid Upload -->
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\StockEntry::class)): ?>
                                        <a href="<?php echo e(route('stock.upload', ['tanggal' => $dateKey, 'distributor_id' => $s->distributor_id])); ?>"
                                           title="Buka & Koreksi di Form Upload"
                                           class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 transition cursor-pointer shadow-2xs hover:scale-105">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="py-14 text-center text-slate-400">
                                <div class="text-3xl mb-2">📋</div>
                                <div class="font-medium text-slate-600 text-sm">Tidak ada riwayat snapshot yang cocok dengan filter</div>
                                <div class="text-xs text-slate-400 mt-1">Silakan sesuaikan filter tanggal atau pilihan grup distributor</div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($snapshots->hasPages()): ?>
            <div class="p-4 border-t border-slate-100 bg-white">
                <?php echo e($snapshots->links('livewire::tailwind')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- MODAL DETAIL SNAPSHOT -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showDetailModal && $selectedSnapshot): ?>
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
                                <?php echo e($selectedSnapshot['tanggal_formatted']); ?>

                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-white mt-1.5 leading-tight">
                            <?php echo e($selectedSnapshot['distributor_name']); ?>

                        </h3>
                        <div class="flex items-center gap-2 text-xs text-emerald-100/90 font-mono mt-1">
                            <span>Kode: <strong class="text-white bg-white/20 px-2 py-0.5 rounded-md"><?php echo e($selectedSnapshot['distributor_code']); ?></strong></span>
                            <span>•</span>
                            <span>Grup: <strong class="text-white bg-white/20 px-2 py-0.5 rounded-md"><?php echo e($selectedSnapshot['group']); ?></strong></span>
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
                <div class="p-5 bg-slate-50/80 border-b border-slate-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Total SKU</div>
                        <div class="text-lg font-bold text-slate-900 mt-1 font-mono">
                            <?php echo e($selectedSnapshot['total_sku']); ?> Item
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Total Kuantitas</div>
                        <div class="text-lg font-bold text-[#0d6d5f] mt-1 font-mono tabular-nums">
                            <?php echo e(number_format($selectedSnapshot['total_quantity'], 0, ',', '.')); ?>

                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Botol (Btl)</div>
                        <div class="text-lg font-bold text-teal-700 mt-1 font-mono tabular-nums">
                            <?php echo e(number_format($selectedSnapshot['total_btl'], 0, ',', '.')); ?>

                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Ampul (Amp)</div>
                        <div class="text-lg font-bold text-cyan-700 mt-1 font-mono tabular-nums">
                            <?php echo e(number_format($selectedSnapshot['total_amp'], 0, ',', '.')); ?>

                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Pcs / Box</div>
                        <div class="text-lg font-bold text-indigo-700 mt-1 font-mono tabular-nums">
                            <?php echo e(number_format($selectedSnapshot['total_pcs'], 0, ',', '.')); ?>

                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                        <div class="text-[11px] font-mono uppercase text-slate-500 font-medium">Batch Dekat ED</div>
                        <div class="text-lg font-bold <?php echo e($selectedSnapshot['expiring_count'] > 0 ? 'text-rose-600' : 'text-emerald-700'); ?> mt-1 font-mono">
                            <?php echo e($selectedSnapshot['expiring_count']); ?> Batch
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
                            Ter-mapping: <strong><?php echo e($selectedSnapshot['mapped_count']); ?></strong> Item
                        </span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedSnapshot['unmapped_count'] > 0): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                Belum Mapped: <strong><?php echo e($selectedSnapshot['unmapped_count']); ?></strong> Item
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selectedSnapshot['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $iIdx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-slate-50/70 transition"
                                    x-show="!itemSearch || $el.textContent.toLowerCase().includes(itemSearch.toLowerCase().trim())">
                                    <td class="text-center py-3.5 px-4 text-slate-400 font-mono">
                                        <?php echo e($iIdx + 1); ?>

                                    </td>
                                    <!-- Nama Produk Distributor -->
                                    <td class="py-3.5 px-4 font-bold text-slate-900">
                                        <div><?php echo e($item['item_name']); ?></div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($item['is_mapped'])): ?>
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-mono bg-amber-50 text-amber-800 border border-amber-200 font-normal">
                                                Unmapped
                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                    <!-- Item Satoria Netsuite -->
                                    <td class="py-3.5 px-4">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item['is_mapped']): ?>
                                            <div class="font-bold text-slate-900 text-xs">
                                                <?php echo e($item['netsuite_name']); ?>

                                            </div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-100/80 text-[#07352d] font-mono font-bold text-[10px] border border-emerald-300/60">
                                                    <?php echo e($item['netsuite_code']); ?>

                                                </span>
                                                <span class="text-[10px] text-emerald-700 font-medium">✓ Mapped</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] bg-amber-50 text-amber-800 border border-amber-200">
                                                Belum di-mapping ke Netsuite
                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                    <!-- Satuan -->
                                    <td class="text-center py-3.5 px-3 font-mono text-slate-700">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-medium">
                                            <?php echo e($item['satuan']); ?>

                                        </span>
                                    </td>
                                    <!-- Kuantitas -->
                                    <td class="text-right py-3.5 px-4 font-extrabold font-mono text-slate-900 tabular-nums text-xs">
                                        <?php echo e(number_format($item['quantity'], 0, ',', '.')); ?>

                                    </td>
                                    <!-- No Batch -->
                                    <td class="py-3.5 px-4 font-mono text-slate-600">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-[11px]">
                                            <?php echo e($item['batch_no']); ?>

                                        </span>
                                    </td>
                                    <!-- Expired Date & Status ED -->
                                    <td class="text-center py-3.5 px-4 font-mono">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item['expiry_status'] === 'expired'): ?>
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] bg-red-100 text-red-800 font-bold border border-red-200">
                                                <?php echo e($item['expired_date']); ?> (Lewat ED)
                                            </span>
                                        <?php elseif($item['expiry_status'] === 'critical'): ?>
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 font-semibold border border-rose-200">
                                                <?php echo e($item['expired_date']); ?> (&lt; 3 bln)
                                            </span>
                                        <?php elseif($item['expiry_status'] === 'warning'): ?>
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-800 font-medium border border-amber-200">
                                                <?php echo e($item['expired_date']); ?> (&lt; 6 bln)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-600 text-[11px]">
                                                <?php echo e($item['expired_date']); ?>

                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div class="text-slate-600 flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Diunggah oleh <strong class="text-slate-900"><?php echo e($selectedSnapshot['uploader_name']); ?></strong>
                        pada <span class="font-mono text-slate-700"><?php echo e($selectedSnapshot['updated_at']); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Unduh CSV -->
                        <button type="button"
                                wire:click="exportCsv('<?php echo e($selectedSnapshot['tanggal']); ?>', <?php echo e($selectedSnapshot['distributor_id']); ?>)"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-semibold text-xs transition cursor-pointer shadow-2xs">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Unduh CSV
                        </button>

                        <!-- Buka di Grid -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\StockEntry::class)): ?>
                            <a href="<?php echo e(route('stock.upload', ['tanggal' => $selectedSnapshot['tanggal'], 'distributor_id' => $selectedSnapshot['distributor_id']])); ?>"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white bg-[#0d6d5f] hover:bg-[#07352d] font-bold text-xs shadow-2xs transition">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Buka di Form Upload
                            </a>
                        <?php endif; ?>

                        <!-- Tutup -->
                        <button type="button" wire:click="closeDetailModal"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 text-xs font-semibold transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/stock/history.blade.php ENDPATH**/ ?>