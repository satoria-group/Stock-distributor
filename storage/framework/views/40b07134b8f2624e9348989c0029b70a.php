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

        <!-- Card 5: Total Cabang (Klik untuk Tab Kepatuhan) -->
        <div wire:click="switchTab('compliance')"
             class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-blue-300 hover:shadow-md transition cursor-pointer group"
             title="Klik untuk melihat status kepatuhan upload cabang">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium group-hover:text-blue-600 transition">Total Cabang / Plant</div>
                <span class="text-[10px] text-blue-600 font-bold opacity-0 group-hover:opacity-100 transition">Lihat ↗</span>
            </div>
            <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                <?php echo e($kpi['total_branches']); ?>

            </div>
            <div class="text-[11px] text-gray-400 mt-1 truncate">Gudang aktif melapor</div>
        </div>

        <!-- Card 6: Alert ED Kritis (Klik untuk Tab FEFO) -->
        <div wire:click="switchTab('expiry', 'critical')"
             class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs hover:border-rose-300 hover:shadow-md transition cursor-pointer group"
             title="Klik untuk membuka monitoring kedaluwarsa (FEFO)">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500 font-medium group-hover:text-rose-600 transition">Batch Dekat/Lewat ED</div>
                <span class="text-[10px] text-rose-600 font-bold opacity-0 group-hover:opacity-100 transition">Periksa ↗</span>
            </div>
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

    <!-- Main Dashboard Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-[#e7e9e3] mb-6 overflow-x-auto">
        <button type="button" wire:click="switchTab('stock')"
                class="inline-flex items-center gap-2 px-5 py-3.5 border-b-2 font-bold text-xs md:text-sm transition cursor-pointer <?php echo e($activeTab === 'stock' ? 'border-[#0d6d5f] text-[#0d6d5f] bg-[#0d6d5f]/5 rounded-t-lg' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'); ?>">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            <span>Posisi Stok On-Hand</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-mono <?php echo e($activeTab === 'stock' ? 'bg-[#0d6d5f] text-white' : 'bg-gray-100 text-gray-600'); ?>">
                <?php echo e(number_format($totalDisplayRows, 0, ',', '.')); ?>

            </span>
        </button>

        <button type="button" wire:click="switchTab('expiry')"
                class="inline-flex items-center gap-2 px-5 py-3.5 border-b-2 font-bold text-xs md:text-sm transition cursor-pointer <?php echo e($activeTab === 'expiry' ? 'border-rose-600 text-rose-700 bg-rose-50/60 rounded-t-lg' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'); ?>">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Monitoring Kedaluwarsa (FEFO)</span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($kpi['expiring_soon'] > 0): ?>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-rose-600 text-white font-bold animate-pulse">
                    <?php echo e($kpi['expiring_soon']); ?> Kritis
                </span>
            <?php else: ?>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono <?php echo e($activeTab === 'expiry' ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-600'); ?>">
                    <?php echo e($fefoSummary['total'] ?? 0); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </button>

        <button type="button" wire:click="switchTab('compliance')"
                class="inline-flex items-center gap-2 px-5 py-3.5 border-b-2 font-bold text-xs md:text-sm transition cursor-pointer <?php echo e($activeTab === 'compliance' ? 'border-blue-600 text-blue-700 bg-blue-50/60 rounded-t-lg' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'); ?>">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Kepatuhan Upload Cabang</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-mono <?php echo e($complianceSummary['compliance_rate'] >= 80 ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white'); ?> font-bold">
                <?php echo e($complianceSummary['compliance_rate']); ?>%
            </span>
        </button>
    </div>

    <!-- TAB 1: POSISI STOK ON-HAND -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'stock'): ?>
    <!-- Tabel Detail Stock (Interaktif ala Looker Studio) -->
    <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 shadow-xs mb-6">
        <!-- Toolbar Filter Tabel -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-4 border-b border-[#e7e9e3] mb-4">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Detail Stock On Hand</h3>
                <div class="flex items-center gap-2 mt-0.5">
                    <p class="text-xs text-gray-500">Daftar stok per cabang distributor dan perubahan kuantitas</p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy !== 'item_name' || $sortDir !== 'asc'): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span>Sortir: <b><?php echo e(match($sortBy) {
                                'quantity' => 'Kuantitas',
                                'distributor' => 'Distributor',
                                'satuan' => 'Satuan',
                                'delta' => 'Δ vs Sebelumnya',
                                'expired_date' => 'ED / Batch',
                                default => 'Nama Produk',
                            }); ?></b> (<?php echo e($sortDir === 'asc' ? 'A→Z / Terkecil' : 'Z→A / Terbanyak'); ?>)</span>
                            <button type="button" wire:click="$set('sortBy', 'item_name'); $set('sortDir', 'asc');" class="text-emerald-600 hover:text-emerald-900 ml-0.5 font-bold cursor-pointer" title="Kembalikan sortir default">×</button>
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
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
                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Cari produk / batch..."
                           class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <!-- Reset Filter Button -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedBranchId || $satuanFilter || $search || $sortBy !== 'item_name' || $sortDir !== 'asc'): ?>
                    <button type="button" wire:click="resetFilters"
                            class="px-2.5 py-2 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 border border-gray-200 transition cursor-pointer inline-flex items-center gap-1.5"
                            title="Reset semua filter dan sortir ke kondisi awal">
                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Reset</span>
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

                        <!-- Item Produk (Sortable) -->
                        <th wire:click="setSort('item_name')"
                            class="text-left py-2.5 px-3 cursor-pointer select-none hover:bg-[#edeae0] transition group"
                            title="Klik untuk mengurutkan berdasarkan Nama Produk">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="<?php echo e($sortBy === 'item_name' ? 'text-brand font-bold' : ''); ?>" style="<?php echo e($sortBy === 'item_name' ? 'color: #0d6d5f;' : ''); ?>">Item Produk</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'item_name'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" class="text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Satuan (Sortable) -->
                        <th wire:click="setSort('satuan')"
                            class="text-center py-2.5 px-3 w-20 cursor-pointer select-none hover:bg-[#edeae0] transition group"
                            title="Klik untuk mengurutkan berdasarkan Satuan">
                            <div class="inline-flex items-center justify-center gap-1.5 w-full">
                                <span class="<?php echo e($sortBy === 'satuan' ? 'text-brand font-bold' : ''); ?>" style="<?php echo e($sortBy === 'satuan' ? 'color: #0d6d5f;' : ''); ?>">Satuan</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'satuan'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" class="text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Cabang Distributor (Sortable) -->
                        <th wire:click="setSort('distributor')"
                            class="text-left py-2.5 px-3 cursor-pointer select-none hover:bg-[#edeae0] transition group"
                            title="Klik untuk mengurutkan berdasarkan Cabang Distributor">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="<?php echo e($sortBy === 'distributor' ? 'text-brand font-bold' : ''); ?>" style="<?php echo e($sortBy === 'distributor' ? 'color: #0d6d5f;' : ''); ?>">Cabang Distributor</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'distributor'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" class="text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Kuantitas (Sortable) -->
                        <th wire:click="setSort('quantity')"
                            class="text-right py-2.5 px-3 w-28 cursor-pointer select-none hover:bg-[#edeae0] transition group"
                            title="Klik untuk mengurutkan berdasarkan Kuantitas Stok">
                            <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                <span class="<?php echo e($sortBy === 'quantity' ? 'text-brand font-bold' : ''); ?>" style="<?php echo e($sortBy === 'quantity' ? 'color: #0d6d5f;' : ''); ?>">Kuantitas</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'quantity'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" class="text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Delta vs Sebelumnya (Sortable) -->
                        <th wire:click="setSort('delta')"
                            class="text-right py-2.5 px-3 w-32 cursor-pointer select-none hover:bg-[#edeae0] transition group"
                            title="Klik untuk mengurutkan berdasarkan Perubahan Delta">
                            <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                <span class="<?php echo e($sortBy === 'delta' ? 'text-brand font-bold' : ''); ?>" style="<?php echo e($sortBy === 'delta' ? 'color: #0d6d5f;' : ''); ?>">Δ vs Sebelumnya</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'delta'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" class="text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- ED / Batch (Sortable) -->
                        <th wire:click="setSort('expired_date')"
                            class="text-left py-2.5 px-3 w-40 cursor-pointer select-none hover:bg-[#edeae0] transition group"
                            title="Klik untuk mengurutkan berdasarkan Tanggal Kedaluwarsa">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="<?php echo e($sortBy === 'expired_date' ? 'text-brand font-bold' : ''); ?>" style="<?php echo e($sortBy === 'expired_date' ? 'color: #0d6d5f;' : ''); ?>">ED / Batch</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'expired_date'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px; flex-shrink: 0;" class="text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
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
    <?php elseif($activeTab === 'expiry'): ?>
    <!-- TAB 2: MONITORING KEDALUWARSA (FEFO WATCHLIST) -->
    <div class="space-y-6 mb-6">
        <!-- 4 Summary Cards FEFO -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div wire:click="$set('expiryRiskFilter', 'critical')" class="bg-white border <?php echo e($expiryRiskFilter === 'critical' ? 'border-rose-500 ring-2 ring-rose-200' : 'border-rose-200'); ?> rounded-xl p-4 shadow-xs hover:shadow-md transition cursor-pointer">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-rose-700 font-bold">🔴 Kritis (&lt; 3 Bulan)</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold">FEFO Prioritas</span>
                </div>
                <div class="text-2xl font-bold mt-2 text-rose-700 tabular-nums">
                    <?php echo e($fefoSummary['critical']); ?> <span class="text-xs font-normal text-gray-500">batch</span>
                </div>
                <div class="text-[11px] text-gray-500 mt-1">Berisiko tinggi ditolak RS / Apotek</div>
            </div>

            <div wire:click="$set('expiryRiskFilter', 'warning')" class="bg-white border <?php echo e($expiryRiskFilter === 'warning' ? 'border-amber-500 ring-2 ring-amber-200' : 'border-amber-200'); ?> rounded-xl p-4 shadow-xs hover:shadow-md transition cursor-pointer">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-amber-700 font-bold">🟡 Waspada (3 - 6 Bulan)</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">Near-ED</span>
                </div>
                <div class="text-2xl font-bold mt-2 text-amber-700 tabular-nums">
                    <?php echo e($fefoSummary['warning']); ?> <span class="text-xs font-normal text-gray-500">batch</span>
                </div>
                <div class="text-[11px] text-gray-500 mt-1">Akselerasi penjualan ke cabang</div>
            </div>

            <div wire:click="$set('expiryRiskFilter', 'expired')" class="bg-white border <?php echo e($expiryRiskFilter === 'expired' ? 'border-red-500 ring-2 ring-red-200' : 'border-red-200'); ?> rounded-xl p-4 shadow-xs hover:shadow-md transition cursor-pointer">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-red-700 font-bold">⛔ Sudah Expired</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800 font-bold">Karantina</span>
                </div>
                <div class="text-2xl font-bold mt-2 text-red-700 tabular-nums">
                    <?php echo e($fefoSummary['expired']); ?> <span class="text-xs font-normal text-gray-500">batch</span>
                </div>
                <div class="text-[11px] text-gray-500 mt-1">Wajib ditarik & isolasi retur</div>
            </div>

            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-gray-600 font-bold">📦 Total Qty Berisiko ED</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-mono">Fisik</span>
                </div>
                <div class="text-2xl font-bold mt-2 text-gray-900 tabular-nums">
                    <?php echo e(number_format($fefoSummary['total_qty_at_risk'], 0, ',', '.')); ?>

                </div>
                <div class="text-[11px] text-gray-400 mt-1">Total unit batch expired &lt;6 bulan</div>
            </div>
        </div>

        <!-- Tabel FEFO & Filter Toolbar -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-4 border-b border-[#e7e9e3] mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>Daftar Batch Terurut Kedaluwarsa (FEFO Order)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Urutan teratas adalah batch yang paling mendekati tanggal kedaluwarsa</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Sub-Filter Buttons -->
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden text-xs">
                        <button type="button" wire:click="$set('expiryRiskFilter', 'all')"
                                class="px-3 py-1.5 cursor-pointer transition <?php echo e($expiryRiskFilter === 'all' ? 'bg-[#0d6d5f] text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-50'); ?>">
                            Semua (<?php echo e($fefoSummary['total']); ?>)
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'critical')"
                                class="px-3 py-1.5 border-l border-gray-300 cursor-pointer transition <?php echo e($expiryRiskFilter === 'critical' ? 'bg-rose-600 text-white font-bold' : 'bg-white text-rose-700 hover:bg-rose-50'); ?>">
                            Kritis (<?php echo e($fefoSummary['critical']); ?>)
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'warning')"
                                class="px-3 py-1.5 border-l border-gray-300 cursor-pointer transition <?php echo e($expiryRiskFilter === 'warning' ? 'bg-amber-600 text-white font-bold' : 'bg-white text-amber-700 hover:bg-amber-50'); ?>">
                            Waspada (<?php echo e($fefoSummary['warning']); ?>)
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'safe')"
                                class="px-3 py-1.5 border-l border-gray-300 cursor-pointer transition <?php echo e($expiryRiskFilter === 'safe' ? 'bg-emerald-600 text-white font-bold' : 'bg-white text-emerald-700 hover:bg-emerald-50'); ?>">
                            Aman (<?php echo e($fefoSummary['safe']); ?>)
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="w-60">
                        <input type="text" wire:model.live.debounce.300ms="expirySearch"
                               placeholder="Cari produk, batch, cabang..."
                               class="w-full text-xs rounded-lg border border-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand">
                    </div>

                    <!-- Export Near-ED CSV Button -->
                    <button type="button" wire:click="exportNearEdCsv"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 text-xs font-semibold shadow-2xs transition cursor-pointer disabled:opacity-50"
                            title="Unduh rekapitulasi batch near-ED dalam format CSV Excel">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Ekspor CSV</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Data FEFO -->
            <div class="border border-[#e7e9e3] rounded-xl overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead class="bg-[#eef1ea] text-[10px] font-mono uppercase tracking-wider text-gray-600 border-b border-[#e7e9e3]">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center">#</th>
                            <th class="py-3 px-3">Produk Satoria & Distributor</th>
                            <th class="py-3 px-3">Distributor / Cabang</th>
                            <th class="py-3 px-3 w-28">No. Batch</th>
                            <th class="py-3 px-3 w-28 text-center">Expired Date</th>
                            <th class="py-3 px-3 w-32 text-center">Sisa Waktu</th>
                            <th class="py-3 px-3 w-24 text-right">Kuantitas</th>
                            <th class="py-3 px-3 w-48">Status & Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7e9e3] bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $fefoTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $e = $r->entry;
                                $ns = $e->distributorItem?->netsuiteItem;
                            ?>
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="py-2.5 px-3 text-center text-gray-400 font-mono">
                                    <?php echo e(($fefoTable->currentPage() - 1) * $perPage + $idx + 1); ?>

                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900 text-xs"><?php echo e($e->distributorItem?->item_name); ?></div>
                                    <div class="font-mono text-[10px] text-gray-500">
                                        <?php echo e($ns ? "[{$ns->netsuite_id}] {$ns->netsuite_name}" : 'Belum Ter-mapping'); ?>

                                    </div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="font-semibold text-gray-800"><?php echo e($e->distributor?->name); ?></span>
                                    <span class="block text-[10px] font-mono text-gray-400"><?php echo e($e->distributor?->distributor_code); ?></span>
                                </td>
                                <td class="py-2.5 px-3 font-mono font-bold text-gray-800">
                                    <?php echo e($e->batch_no ?: '—'); ?>

                                </td>
                                <td class="py-2.5 px-3 text-center font-mono">
                                    <span class="font-bold <?php echo e($r->tier === 'expired' || $r->tier === 'critical' ? 'text-red-700' : ($r->tier === 'warning' ? 'text-amber-700' : 'text-gray-700')); ?>">
                                        <?php echo e($e->expired_date ? $e->expired_date->format('d/m/Y') : '—'); ?>

                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->days < 0): ?>
                                        <span class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-800 font-bold text-[10px]">
                                            Lewat <?php echo e(abs($r->days)); ?> hari
                                        </span>
                                    <?php elseif($r->days <= 90): ?>
                                        <span class="inline-block px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px]">
                                            <?php echo e($r->days); ?> hari (~<?php echo e(round($r->days/30, 1)); ?> bln)
                                        </span>
                                    <?php elseif($r->days <= 180): ?>
                                        <span class="inline-block px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold text-[10px]">
                                            <?php echo e($r->days); ?> hari (~<?php echo e(round($r->days/30, 1)); ?> bln)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-emerald-700 font-medium text-[11px]">
                                            <?php echo e($r->days); ?> hari
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-900">
                                    <?php echo e(number_format($e->quantity, 0, ',', '.')); ?>

                                    <span class="block text-[10px] text-gray-400 font-normal"><?php echo e($e->satuan); ?></span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-mono uppercase font-bold border <?php echo e($r->badgeClass); ?>">
                                        <?php echo e($r->label); ?>

                                    </span>
                                    <span class="block text-[10px] text-gray-500 mt-1 font-medium leading-tight">
                                        <?php echo e($r->action); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="py-10 text-center text-gray-400">
                                    Tidak ada data batch yang sesuai dengan filter kedaluwarsa.
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination FEFO -->
            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-gray-500">
                    Menampilkan <b><?php echo e($fefoTable->firstItem() ?? 0); ?></b> - <b><?php echo e($fefoTable->lastItem() ?? 0); ?></b> dari <b><?php echo e($fefoTable->total()); ?></b> batch
                </div>
                <div>
                    <?php echo e($fefoTable->links('livewire::tailwind')); ?>

                </div>
            </div>
        </div>
    </div>
    <?php elseif($activeTab === 'compliance'): ?>
    <!-- TAB 3: KEPATUHAN UPLOAD CABANG (COMPLIANCE TRACKER) -->
    <div class="space-y-6 mb-6">
        <!-- 4 Summary Cards Kepatuhan -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs">
                <div class="text-[11px] font-mono uppercase text-gray-500 font-medium">Total Cabang Aktif</div>
                <div class="text-2xl font-bold mt-1 text-gray-900 tabular-nums">
                    <?php echo e($complianceSummary['total_branches']); ?> <span class="text-xs font-normal text-gray-500">gudang</span>
                </div>
                <div class="text-[11px] text-gray-400 mt-1">Seluruh cabang terdaftar</div>
            </div>

            <div wire:click="$set('complianceStatus', 'submitted')" class="bg-white border <?php echo e($complianceStatus === 'submitted' ? 'border-emerald-500 ring-2 ring-emerald-200' : 'border-emerald-200'); ?> rounded-xl p-4 shadow-xs hover:shadow-md transition cursor-pointer">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-emerald-700 font-bold">✅ Sudah Lapor</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Tepat Waktu</span>
                </div>
                <div class="text-2xl font-bold mt-1 text-emerald-700 tabular-nums">
                    <?php echo e($complianceSummary['total_submitted']); ?> <span class="text-xs font-normal text-gray-500">cabang</span>
                </div>
                <div class="text-[11px] text-gray-500 mt-1">Data stok terekam di tanggal acuan</div>
            </div>

            <div wire:click="$set('complianceStatus', 'missing')" class="bg-white border <?php echo e($complianceStatus === 'missing' ? 'border-rose-500 ring-2 ring-rose-200' : 'border-rose-200'); ?> rounded-xl p-4 shadow-xs hover:shadow-md transition cursor-pointer">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-rose-700 font-bold">⚠️ Belum Lapor</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold animate-pulse">Perlu Follow-up</span>
                </div>
                <div class="text-2xl font-bold mt-1 text-rose-700 tabular-nums">
                    <?php echo e($complianceSummary['total_missing']); ?> <span class="text-xs font-normal text-gray-500">cabang</span>
                </div>
                <div class="text-[11px] text-gray-500 mt-1">Belum setor snapshot stok</div>
            </div>

            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-mono uppercase text-gray-600 font-bold">Tingkat Kepatuhan</span>
                    <span class="text-xs font-mono font-bold <?php echo e($complianceSummary['compliance_rate'] >= 80 ? 'text-emerald-700' : 'text-amber-700'); ?>">
                        <?php echo e($complianceSummary['compliance_rate']); ?>%
                    </span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full <?php echo e($complianceSummary['compliance_rate'] >= 80 ? 'bg-emerald-600' : 'bg-amber-500'); ?>"
                         style="width: <?php echo e(min(100, $complianceSummary['compliance_rate'])); ?>%"></div>
                </div>
                <div class="text-[10px] text-gray-400 mt-1.5 flex justify-between font-mono">
                    <span>Target: 100%</span>
                    <span>Acuan: <?php echo e(\Illuminate\Support\Carbon::parse($complianceSummary['target_date'])->translatedFormat('d M Y')); ?></span>
                </div>
            </div>
        </div>

        <!-- Tabel Kepatuhan & Filter Toolbar -->
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-4 border-b border-[#e7e9e3] mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>Status Kepatuhan Laporan Stok Cabang</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Daftar cabang distributor dan status pengunggahan snapshot pada tanggal acuan</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Date Picker Tanggal Acuan -->
                    <div class="flex items-center gap-1.5 bg-[#f8faf9] px-2.5 py-1 rounded-lg border border-gray-300">
                        <span class="text-[11px] font-mono uppercase text-gray-500 font-semibold">Tgl Acuan:</span>
                        <input type="date" wire:model.live="complianceDate"
                               class="text-xs border-0 bg-transparent text-gray-800 font-semibold focus:outline-none focus:ring-0">
                    </div>

                    <!-- Sub-Filter Buttons -->
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden text-xs">
                        <button type="button" wire:click="$set('complianceStatus', 'all')"
                                class="px-3 py-1.5 cursor-pointer transition <?php echo e($complianceStatus === 'all' ? 'bg-[#0d6d5f] text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-50'); ?>">
                            Semua (<?php echo e($complianceSummary['total_branches']); ?>)
                        </button>
                        <button type="button" wire:click="$set('complianceStatus', 'submitted')"
                                class="px-3 py-1.5 border-l border-gray-300 cursor-pointer transition <?php echo e($complianceStatus === 'submitted' ? 'bg-emerald-600 text-white font-bold' : 'bg-white text-emerald-700 hover:bg-emerald-50'); ?>">
                            Sudah (<?php echo e($complianceSummary['total_submitted']); ?>)
                        </button>
                        <button type="button" wire:click="$set('complianceStatus', 'missing')"
                                class="px-3 py-1.5 border-l border-gray-300 cursor-pointer transition <?php echo e($complianceStatus === 'missing' ? 'bg-rose-600 text-white font-bold' : 'bg-white text-rose-700 hover:bg-rose-50'); ?>">
                            Belum (<?php echo e($complianceSummary['total_missing']); ?>)
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="w-60">
                        <input type="text" wire:model.live.debounce.300ms="complianceSearch"
                               placeholder="Cari kode atau nama cabang..."
                               class="w-full text-xs rounded-lg border border-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand">
                    </div>
                </div>
            </div>

            <!-- Tabel Data Kepatuhan -->
            <div class="border border-[#e7e9e3] rounded-xl overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead class="bg-[#eef1ea] text-[10px] font-mono uppercase tracking-wider text-gray-600 border-b border-[#e7e9e3]">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center">#</th>
                            <th class="py-3 px-3">Kode & Nama Distributor</th>
                            <th class="py-3 px-3 w-20 text-center">Grup</th>
                            <th class="py-3 px-3 w-36 text-center">Status Lapor</th>
                            <th class="py-3 px-3 w-36">Upload Terakhir</th>
                            <th class="py-3 px-3 w-36 text-right">Snapshot Terakhir</th>
                            <th class="py-3 px-3 w-32 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7e9e3] bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $complianceTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="py-2.5 px-3 text-center text-gray-400 font-mono">
                                    <?php echo e(($complianceTable->currentPage() - 1) * $perPage + $idx + 1); ?>

                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900 text-xs"><?php echo e($row->distributor->name); ?></div>
                                    <div class="font-mono text-[10px] text-gray-500"><?php echo e($row->distributor->distributor_code); ?></div>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-gray-100 text-gray-800">
                                        <?php echo e(\App\Livewire\Dashboard::getDistributorGroup($row->distributor->distributor_code)); ?>

                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->hasSubmitted): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span>✓ Sudah Lapor</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 animate-pulse">
                                            <span>✕ Belum Lapor</span>
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->lastDate): ?>
                                        <div class="font-bold text-gray-800 text-[11px]">
                                            <?php echo e(\Illuminate\Support\Carbon::parse($row->lastDate)->translatedFormat('d M Y')); ?>

                                        </div>
                                        <span class="text-[10px] text-gray-500">
                                            <?php echo e($row->daysOverdue !== null ? ($row->daysOverdue === 0 ? 'Hari ini' : $row->daysOverdue . ' hari lalu') : '—'); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic text-[11px]">Belum pernah lapor</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->lastDate): ?>
                                        <div class="font-bold text-gray-900"><?php echo e(number_format($row->totalQty, 0, ',', '.')); ?></div>
                                        <span class="text-[10px] text-gray-500 font-normal"><?php echo e($row->totalRows); ?> baris</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 text-center space-x-2">
                                    <a href="<?php echo e(route('stock.history', ['distributor_id' => $row->distributor->id])); ?>"
                                       class="inline-flex items-center px-2 py-1 rounded text-[11px] font-semibold text-brand hover:underline"
                                       style="color: #0d6d5f;"
                                       title="Lihat riwayat stok cabang ini">
                                        Riwayat
                                    </a>
                                    <a href="<?php echo e(route('stock.upload', ['distributor_id' => $row->distributor->id])); ?>"
                                       class="inline-flex items-center px-2 py-1 rounded text-[11px] font-semibold text-blue-600 hover:underline"
                                       title="Unggah stok untuk cabang ini">
                                        Upload
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-400">
                                    Tidak ada data cabang distributor yang cocok dengan filter kepatuhan.
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Kepatuhan -->
            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-gray-500">
                    Menampilkan <b><?php echo e($complianceTable->firstItem() ?? 0); ?></b> - <b><?php echo e($complianceTable->lastItem() ?? 0); ?></b> dari <b><?php echo e($complianceTable->total()); ?></b> cabang
                </div>
                <div>
                    <?php echo e($complianceTable->links('livewire::tailwind')); ?>

                </div>
            </div>
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