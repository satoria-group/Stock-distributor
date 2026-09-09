<div>
    <!-- Top Header Banner (Looker Studio Style) -->
    <div class="bg-gradient-to-r from-[#0d6d5f] to-[#07352d] text-white rounded-2xl p-6 mb-6 shadow-md border border-emerald-800/30">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="text-[11px] font-mono tracking-widest uppercase text-emerald-200 font-semibold mb-1">
                    Satoria Internal Logistics Control
                </div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL'): ?>
                        STOCK ON HAND HARIAN DISTRIBUTOR (NASIONAL)
                    <?php else: ?>
                        STOCK HARIAN <?php echo e($selectedGroup === 'OTHER' ? 'DISTRIBUTOR LAINNYA' : $selectedGroup); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedBranchId): ?>
                            <span class="text-base font-normal text-emerald-200">· <?php echo e($availableBranches->firstWhere('id', $selectedBranchId)?->name); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </h1>
                <div class="flex items-center gap-2 mt-1.5 text-xs text-emerald-100">
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Last Update: <b><?php echo e($latestDate ? \Illuminate\Support\Carbon::parse($latestDate)->translatedFormat('d F Y') : 'Belum ada data'); ?></b></span>
                    <span class="text-emerald-300/60">•</span>
                    <span>Snapshot Posisi Stok Terkini</span>
                </div>
            </div>

            <!-- Distributor Group Tabs -->
            <div class="flex items-center gap-1.5 bg-black/20 p-1.5 rounded-xl flex-wrap backdrop-blur-xs">
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
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer <?php echo e($selectedGroup === $gKey ? 'bg-white text-[#07352d] shadow-sm' : 'text-emerald-100 hover:bg-white/10 hover:text-white'); ?>">
                        <?php echo e($gLabel); ?>

                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 6 KPI Cards (Looker Studio Inspired Satuan Breakdown) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <!-- Card 1: Total Btl (Infus) -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Total Stock (Btl)</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                <?php echo e(number_format($kpi['total_btl'], 0, ',', '.')); ?>

            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Larutan Infus (RL, NS, D5)</div>
        </div>

        <!-- Card 2: Total Amp (Injeksi) -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Total Stock (Amp)</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                <?php echo e(number_format($kpi['total_amp'], 0, ',', '.')); ?>

            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Injeksi & Pelarut (WFI)</div>
        </div>

        <!-- Card 3: Total Pcs/Box (Alkes) -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Total Stock (Pcs/Box)</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                <?php echo e(number_format($kpi['total_pcs'], 0, ',', '.')); ?>

            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Alkes & Disposable</div>
        </div>

        <!-- Card 4: Total SKU -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Total SKU</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                <?php echo e($kpi['total_sku']); ?>

            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Varian item aktif</div>
        </div>

        <!-- Card 5: Total Cabang -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-emerald-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Total Cabang / Plant</div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                <?php echo e($kpi['total_branches']); ?>

            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Gudang aktif melapor</div>
        </div>

        <!-- Card 6: Alert ED Kritis -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-red-200 transition">
            <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium">Batch Dekat/Lewat ED</div>
            <div class="text-2xl font-bold mt-1 tabular-nums <?php echo e($kpi['expiring_soon'] > 0 ? 'text-red-600' : 'text-gray-900'); ?>">
                <?php echo e($kpi['expiring_soon']); ?>

            </div>
            <div class="text-[11px] <?php echo e($kpi['expiring_soon'] > 0 ? 'text-red-500 font-medium' : 'text-gray-400'); ?> mt-1 truncate">
                <?php echo e($kpi['expiring_soon'] > 0 ? 'Perlu tindakan FEFO' : 'Semua batch aman'); ?>

            </div>
        </div>
    </div>

    <!-- Hidden Data Holder for Safe Morph Updates -->
    <div id="charts-data-holder"
         class="hidden"
         data-top='<?php echo json_encode($chartTopProducts, 15, 512) ?>'
         data-donut='<?php echo json_encode($chartDonut, 15, 512) ?>'>
    </div>

    <!-- Dua Grafik Utama: Top 10 Produk & Distribusi Stok -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <!-- Grafik Kiri (Col-span-2): Top 10 Produk Berdasarkan Kuantitas -->
        <div class="lg:col-span-2 bg-white border border-[#e7e9e3] rounded-xl p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Top 10 Produk Berdasarkan Kuantitas</h3>
                    <p class="text-xs text-gray-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL' && ! $selectedBranchId): ?>
                            Akumulasi kuantitas nasional dengan kontribusi distributor group
                        <?php else: ?>
                            Akumulasi kuantitas produk pada <?php echo e($selectedBranchId ? $availableBranches->firstWhere('id', $selectedBranchId)?->name : ($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup)); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 320px;">
                <canvas id="chart-top-products"></canvas>
                <div id="no-data-top" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 text-xs hidden">
                    <div class="text-3xl mb-1">📊</div>
                    <span class="font-medium text-gray-500">Belum ada data stok produk untuk grup ini.</span>
                    <span class="text-[11px] text-gray-400">Silakan pilih distributor lain atau unggah data stok harian.</span>
                </div>
            </div>
        </div>

        <!-- Grafik Kanan (Col-span-1): Distribusi Stok per Distributor (Donut) -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL' && ! $selectedBranchId): ?>
                                Distribusi Stok per Distributor
                            <?php else: ?>
                                Komposisi Sediaan (<?php echo e($selectedBranchId ? $availableBranches->firstWhere('id', $selectedBranchId)?->name : ($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup)); ?>)
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </h3>
                        <p class="text-xs text-gray-500">Porsi persentase volume fisik stok</p>
                    </div>
                </div>
                <div wire:ignore class="relative flex items-center justify-center" style="height: 250px;">
                    <canvas id="chart-donut-dist"></canvas>
                    <div id="no-data-donut" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 text-xs hidden">
                        <div class="text-3xl mb-1">🍩</div>
                        <span class="font-medium text-gray-500">Belum ada data stok.</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 text-[11px] text-gray-400 text-center font-mono">
                Total Volume: <?php echo e(number_format($kpi['total_btl'] + $kpi['total_amp'] + $kpi['total_pcs'], 0, ',', '.')); ?> unit fisik
            </div>
        </div>
    </div>

    <!-- Tabel Detail Stock (Interaktif ala Looker Studio) -->
    <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 shadow-xs mb-6">
        <!-- Toolbar Filter Tabel -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-4 border-b border-[#e7e9e3] mb-4">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Detail Stock On Hand</h3>
                <p class="text-xs text-gray-500 mt-0.5">Daftar stok per cabang distributor dan perubahan kuantitas</p>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter Cabang -->
                <div class="w-56">
                    <select wire:model.live="selectedBranchId"
                            class="w-full text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Semua Cabang (<?php echo e(count($availableBranches)); ?>) —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $availableBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($b->id); ?>"><?php echo e($b->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>

                <!-- Filter Satuan -->
                <div class="w-36">
                    <select wire:model.live="satuanFilter"
                            class="w-full text-xs rounded-lg border border-gray-300 px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">Semua Satuan</option>
                        <option value="BTL">Botol (Btl)</option>
                        <option value="AMP">Ampul (Amp)</option>
                        <option value="PCS">Pcs / Box (Alkes)</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative w-48 sm:w-60">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Cari produk / batch..."
                           class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <!-- Reset Filter Button -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedBranchId || $satuanFilter || $search): ?>
                    <button type="button" wire:click="$set('selectedBranchId', null); $set('satuanFilter', ''); $set('search', '')"
                            class="px-2.5 py-2 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 border border-gray-200 transition cursor-pointer">
                        Reset Filter
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-[#f7f5ed] text-gray-600 font-mono uppercase text-[11px] border-b border-gray-200">
                    <tr>
                        <th class="text-center py-2.5 px-3 w-12">No</th>
                        <th class="text-left py-2.5 px-3">Item Produk</th>
                        <th class="text-center py-2.5 px-3 w-20">Satuan</th>
                        <th class="text-left py-2.5 px-3">Cabang Distributor</th>
                        <th class="text-right py-2.5 px-3 w-28">Kuantitas</th>
                        <th class="text-right py-2.5 px-3 w-32">Δ vs Sebelumnya</th>
                        <th class="text-left py-2.5 px-3 w-40">ED / Batch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $stockTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-[#fcfdfc] transition">
                            <td class="text-center py-2.5 px-3 text-gray-400 font-mono">
                                <?php echo e(($stockTable->currentPage() - 1) * $perPage + $idx + 1); ?>

                            </td>
                            <td class="py-2.5 px-3 font-semibold text-gray-900">
                                <?php echo e($r->entry->distributorItem?->item_name ?? '—'); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($r->entry->distributorItem?->isMapped())): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono uppercase bg-amber-100 text-amber-800 font-normal ml-1">
                                        Belum Mapping
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono text-gray-600">
                                <?php echo e($r->entry->satuan ?: '—'); ?>

                            </td>
                            <td class="py-2.5 px-3 text-gray-700">
                                <span class="font-medium"><?php echo e($r->entry->distributor?->name ?? '—'); ?></span>
                                <span class="block text-[10px] font-mono text-gray-400"><?php echo e($r->entry->distributor?->distributor_code); ?></span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-900 text-sm">
                                <?php echo e(number_format($r->entry->quantity, 0, ',', '.')); ?>

                            </td>
                            <td class="py-2.5 px-3 text-right font-mono">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->delta_pct === null): ?>
                                    <span class="text-gray-400">—</span>
                                <?php elseif($r->delta_pct > 0): ?>
                                    <span class="inline-flex items-center gap-0.5 text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.5 rounded">
                                        ▲ +<?php echo e($r->delta_pct); ?>%
                                    </span>
                                <?php elseif($r->delta_pct < 0): ?>
                                    <span class="inline-flex items-center gap-0.5 text-red-600 font-semibold bg-red-50 px-1.5 py-0.5 rounded">
                                        ▼ <?php echo e($r->delta_pct); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">0%</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-[11px]">
                                <?php
                                    $expStatus = $r->entry->expiryStatus();
                                    $days = $r->entry->daysToExpiry();
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->entry->expired_date): ?>
                                    <span class="<?php echo e($expStatus === 'critical' ? 'text-red-600 font-bold' : ($expStatus === 'warning' ? 'text-amber-700 font-medium' : 'text-gray-600')); ?>">
                                        <?php echo e($r->entry->expired_date->format('Y-m-d')); ?>

                                    </span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($days !== null): ?>
                                        <span class="text-[10px] text-gray-400 block">
                                            (<?php echo e($days < 0 ? 'Lewat '.abs($days).'h' : $days.' hari lagi'); ?>)
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->entry->batch_no): ?>
                                    <span class="text-[10px] text-gray-500 font-mono block">B: <?php echo e($r->entry->batch_no); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-400">
                                <div class="text-2xl mb-1">📦</div>
                                Tidak ada data stok yang cocok dengan filter.
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Summary -->
        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-500">
                Menampilkan <b><?php echo e($stockTable->firstItem() ?? 0); ?></b> - <b><?php echo e($stockTable->lastItem() ?? 0); ?></b> dari <b><?php echo e($stockTable->total()); ?></b> baris stok
            </div>
            <div>
                <?php echo e($stockTable->links('livewire::tailwind')); ?>

            </div>
        </div>
    </div>

    <!-- Section Early Warning: Batch Kadaluarsa Kritis -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiryAlerts->isNotEmpty()): ?>
        <div class="bg-white border border-red-200 rounded-xl p-5 shadow-xs mb-6">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Perhatian Logistik: Batch Mendekati / Lewat Kadaluarsa</h3>
                    <p class="text-xs text-gray-500">Prioritaskan pengeluaran stok (*FEFO*) atau koordinasikan penarikan sebelum retur kedaluwarsa</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $expiryAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $days = $alert->daysToExpiry();
                        $status = $alert->expiryStatus();
                    ?>
                    <div class="p-3 rounded-lg border <?php echo e($status === 'critical' ? 'border-red-200 bg-red-50/50' : 'border-amber-200 bg-amber-50/50'); ?> text-xs">
                        <div class="font-bold text-gray-900 truncate"><?php echo e($alert->distributorItem?->item_name); ?></div>
                        <div class="text-gray-500 truncate text-[11px]"><?php echo e($alert->distributor?->name); ?></div>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200/60 font-mono">
                            <span class="text-gray-600">Qty: <b><?php echo e(number_format($alert->quantity, 0, ',', '.')); ?></b></span>
                            <span class="font-bold <?php echo e($status === 'critical' ? 'text-red-700' : 'text-amber-800'); ?>">
                                <?php echo e($days < 0 ? 'Lewat '.abs($days).'h' : $days.' hari lagi'); ?>

                            </span>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Scripts Chart.js -->
        <?php
        $__scriptKey = '2198190063-0';
        ob_start();
    ?>
    <script>
        let chartTopInstance = null;
        let chartDonutInstance = null;

        function updateChartData(topData, donutData) {
            const canvasTop = document.getElementById('chart-top-products');
            const noDataTop = document.getElementById('no-data-top');
            const canvasDonut = document.getElementById('chart-donut-dist');
            const noDataDonut = document.getElementById('no-data-donut');

            // 1. Horizontal Bar Chart (Top 10 Produk)
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
                                        font: { size: 11, family: 'IBM Plex Sans' }
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
                                    grid: { color: '#f3f4f6' },
                                    ticks: {
                                        font: { size: 10, family: 'IBM Plex Mono' },
                                        callback: val => Number(val).toLocaleString('id-ID')
                                    }
                                },
                                y: {
                                    stacked: isStacked,
                                    grid: { display: false },
                                    ticks: {
                                        font: { size: 11, family: 'IBM Plex Sans' }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // 2. Donut Chart (Distribusi Stok)
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
                                        font: { size: 11, family: 'IBM Plex Sans' }
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
        }

        // 1. Initial Render from DOM data holder
        function readAndRender() {
            const holder = document.getElementById('charts-data-holder');
            if (holder) {
                try {
                    const top = JSON.parse(holder.getAttribute('data-top') || '{}');
                    const donut = JSON.parse(holder.getAttribute('data-donut') || '{}');
                    updateChartData(top, donut);
                } catch(e) {
                    console.error('Error parsing chart data:', e);
                }
            }
        }

        readAndRender();

        // 2. Event Listener dari Livewire Dispatch
        $wire.on('charts-updated', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            if (data) {
                updateChartData(data.top, data.donut);
            }
        });

        // 3. Fail-safe hook saat morphing selesai
        if (window.Livewire) {
            window.Livewire.hook('morph.updated', () => {
                readAndRender();
            });
        }
    </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
</div>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/dashboard.blade.php ENDPATH**/ ?>