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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL'): ?>
                        Stock On Hand Harian Distributor
                    <?php else: ?>
                        Stock Harian <?php echo e($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedBranchId): ?>
                            <span class="text-emerald-200 font-normal text-xl md:text-2xl">· <?php echo e($availableBranches->firstWhere('id', $selectedBranchId)?->name); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                        <span>Update Terakhir: <b class="text-white"><?php echo e($latestDate ? \Illuminate\Support\Carbon::parse($latestDate)->translatedFormat('d F Y') : 'Belum ada data'); ?></b></span>
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
                    <?php echo e(number_format($kpi['total_btl'], 0, ',', '.')); ?>

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
                    <?php echo e(number_format($kpi['total_amp'], 0, ',', '.')); ?>

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
                    <?php echo e(number_format($kpi['total_pcs'], 0, ',', '.')); ?>

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
                    <?php echo e($kpi['total_sku']); ?>

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
                    <?php echo e($kpi['total_branches']); ?>

                </div>
            </div>
        </div>

        <!-- Card 6: Alert ED Kritis (Static) -->
        <div class="bg-white rounded-2xl border <?php echo e($kpi['expiring_soon'] > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200/80'); ?> p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-wider uppercase <?php echo e($kpi['expiring_soon'] > 0 ? 'text-rose-700' : 'text-slate-500'); ?>">DEKAT/LEWAT ED</span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                     style="<?php echo e($kpi['expiring_soon'] > 0 ? 'background: #fff1f2; color: #e11d48; border-color: #fecdd3;' : 'background: #f1f5f9; color: #64748b; border-color: #e2e8f0;'); ?>">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold tabular-nums tracking-tight mt-2.5 <?php echo e($kpi['expiring_soon'] > 0 ? 'text-rose-600' : 'text-slate-900'); ?>">
                    <?php echo e($kpi['expiring_soon']); ?>

                </div>
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-7">
        <!-- Grafik Kiri (Col-span-2): Top 10 Produk Berdasarkan Kuantitas -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Top 10 Produk Berdasarkan Kuantitas</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL' && ! $selectedBranchId): ?>
                            Akumulasi volume kuantitas produk secara nasional dengan breakdown grup distributor
                        <?php else: ?>
                            Akumulasi kuantitas produk pada <?php echo e($selectedBranchId ? $availableBranches->firstWhere('id', $selectedBranchId)?->name : ($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup)); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedGroup === 'ALL' && ! $selectedBranchId): ?>
                                Distribusi Stok per Distributor
                            <?php else: ?>
                                Komposisi Sediaan (<?php echo e($selectedBranchId ? $availableBranches->firstWhere('id', $selectedBranchId)?->name : ($selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $selectedGroup)); ?>)
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                <span class="font-bold text-slate-900"><?php echo e(number_format($kpi['total_btl'] + $kpi['total_amp'] + $kpi['total_pcs'], 0, ',', '.')); ?> unit</span>
            </div>
        </div>
    </div>

    <!-- Modern Segmented Control Navigation Tabs -->
    <div class="inline-flex p-1.5 rounded-2xl border border-slate-200 gap-1.5 shadow-2xs mb-6 overflow-x-auto max-w-full"
         style="background: #edf2f1;">
        <!-- Tab 1: Stok On-Hand -->
        <button type="button" wire:click="switchTab('stock')"
                class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer"
                style="<?php echo e($activeTab === 'stock' ? 'background: #ffffff; color: #07352d; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;'); ?>">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="<?php echo e($activeTab === 'stock' ? 'text-[#0d6d5f]' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            <span>Posisi Stok On-Hand</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold"
                  style="<?php echo e($activeTab === 'stock' ? 'background: #0d6d5f; color: #ffffff;' : 'background: #e2e8f0; color: #475569;'); ?>">
                <?php echo e(number_format($totalDisplayRows, 0, ',', '.')); ?>

            </span>
        </button>

        <!-- Tab 2: Monitoring Kedaluwarsa (FEFO) -->
        <button type="button" wire:click="switchTab('expiry')"
                class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer"
                style="<?php echo e($activeTab === 'expiry' ? 'background: #ffffff; color: #be123c; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;'); ?>">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="<?php echo e($activeTab === 'expiry' ? 'text-rose-600' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Monitoring Kedaluwarsa (FEFO)</span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($kpi['expiring_soon'] > 0): ?>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono bg-rose-600 text-white font-bold animate-pulse shadow-2xs">
                    <?php echo e($kpi['expiring_soon']); ?> Kritis
                </span>
            <?php else: ?>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold"
                      style="<?php echo e($activeTab === 'expiry' ? 'background: #ffe4e6; color: #9f1239;' : 'background: #e2e8f0; color: #475569;'); ?>">
                    <?php echo e($fefoSummary['total'] ?? 0); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </button>

        <!-- Tab 3: Kepatuhan Upload Cabang -->
        <button type="button" wire:click="switchTab('compliance')"
                class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-xl text-xs md:text-sm font-bold transition cursor-pointer"
                style="<?php echo e($activeTab === 'compliance' ? 'background: #ffffff; color: #0d6d5f; box-shadow: 0 2px 6px rgba(0,0,0,0.08);' : 'color: #475569; background: transparent;'); ?>">
            <svg width="17" height="17" style="width: 17px; height: 17px; min-width: 17px; flex-shrink: 0;" class="<?php echo e($activeTab === 'compliance' ? 'text-[#0d6d5f]' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Kepatuhan Upload Cabang</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold shadow-2xs text-white"
                  style="background: <?php echo e($complianceSummary['compliance_rate'] >= 80 ? '#059669' : '#d97706'); ?>;">
                <?php echo e($complianceSummary['compliance_rate']); ?>%
            </span>
        </button>
    </div>

    <!-- TAB 1: POSISI STOK ON-HAND -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'stock'): ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs mb-7">
        <!-- Toolbar Filter Tabel -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Detail Stock On Hand</h3>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-xs text-slate-500">Daftar stok per cabang distributor dan mutasi kuantitas snapshot</p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy !== 'item_name' || $sortDir !== 'asc'): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span>Sortir: <b><?php echo e(match($sortBy) {
                                'quantity' => 'Kuantitas',
                                'distributor' => 'Distributor',
                                'satuan' => 'Satuan',
                                'delta' => 'Δ vs Kemarin',
                                'expired_date' => 'ED / Batch',
                                default => 'Nama Produk',
                            }); ?></b> (<?php echo e($sortDir === 'asc' ? 'A→Z / Terkecil' : 'Z→A / Terbanyak'); ?>)</span>
                            <button type="button" wire:click="$set('sortBy', 'item_name'); $set('sortDir', 'asc');" class="text-emerald-600 hover:text-emerald-900 ml-0.5 font-bold cursor-pointer" title="Kembalikan sortir default">×</button>
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Filter Cabang -->
                <div class="w-56">
                    <select wire:model.live="selectedBranchId"
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                        <option value="">— Semua Cabang (<?php echo e(count($availableBranches)); ?>) —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $availableBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($b->id); ?>"><?php echo e($b->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedBranchId || $satuanFilter || $search || $sortBy !== 'item_name' || $sortDir !== 'asc'): ?>
                    <button type="button" wire:click="resetFilters"
                            class="px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer inline-flex items-center gap-1.5"
                            title="Reset semua filter dan sortir ke kondisi awal">
                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Reset</span>
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                                <span class="<?php echo e($sortBy === 'item_name' ? 'font-bold' : ''); ?>" style="<?php echo e($sortBy === 'item_name' ? 'color: #0d6d5f;' : ''); ?>">Item Produk</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'item_name'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Satuan (Sortable) -->
                        <th wire:click="setSort('satuan')"
                            class="text-center py-3.5 px-4 w-24 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Satuan">
                            <div class="inline-flex items-center justify-center gap-1.5 w-full">
                                <span class="<?php echo e($sortBy === 'satuan' ? 'font-bold' : ''); ?>" style="<?php echo e($sortBy === 'satuan' ? 'color: #0d6d5f;' : ''); ?>">Satuan</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'satuan'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Cabang Distributor (Sortable) -->
                        <th wire:click="setSort('distributor')"
                            class="text-left py-3.5 px-4 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Cabang Distributor">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="<?php echo e($sortBy === 'distributor' ? 'font-bold' : ''); ?>" style="<?php echo e($sortBy === 'distributor' ? 'color: #0d6d5f;' : ''); ?>">Cabang Distributor</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'distributor'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Kuantitas (Sortable) -->
                        <th wire:click="setSort('quantity')"
                            class="text-right py-3.5 px-4 w-32 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Kuantitas Stok">
                            <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                <span class="<?php echo e($sortBy === 'quantity' ? 'font-bold' : ''); ?>" style="<?php echo e($sortBy === 'quantity' ? 'color: #0d6d5f;' : ''); ?>">Kuantitas</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'quantity'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- Delta vs Sebelumnya (Sortable) -->
                        <th wire:click="setSort('delta')"
                            class="text-right py-3.5 px-4 w-36 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Perubahan Delta">
                            <div class="inline-flex items-center justify-end gap-1.5 w-full">
                                <span class="<?php echo e($sortBy === 'delta' ? 'font-bold' : ''); ?>" style="<?php echo e($sortBy === 'delta' ? 'color: #0d6d5f;' : ''); ?>">Δ vs Kemarin</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'delta'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>

                        <!-- ED / Batch (Sortable) -->
                        <th wire:click="setSort('expired_date')"
                            class="text-left py-3.5 px-4 w-44 cursor-pointer select-none hover:bg-slate-100 transition group"
                            title="Klik untuk mengurutkan berdasarkan Tanggal Kedaluwarsa">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="<?php echo e($sortBy === 'expired_date' ? 'font-bold' : ''); ?>" style="<?php echo e($sortBy === 'expired_date' ? 'color: #0d6d5f;' : ''); ?>">ED / Batch</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'expired_date'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDir === 'asc'): ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" class="text-slate-300 group-hover:text-slate-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $stockTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-[#f8faf9] transition duration-150">
                            <td class="text-center py-3.5 px-4 text-slate-400 font-mono">
                                <?php echo e(($stockTable->currentPage() - 1) * $perPage + $idx + 1); ?>

                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <div class="font-bold text-slate-900">
                                    <?php echo e($r->entry->distributorItem?->item_name ?? '—'); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($r->entry->distributorItem?->isMapped())): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono uppercase bg-amber-50 text-amber-800 border border-amber-200 font-semibold ml-1.5">
                                            Belum Mapping
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->entry->tanggal): ?>
                                    <span class="block text-[10px] font-mono text-slate-400 mt-0.5">
                                        Snapshot: <?php echo e($r->entry->tanggal->translatedFormat('d M Y')); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-600">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                    <?php echo e($r->entry->satuan ?: '—'); ?>

                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700">
                                <span class="font-bold text-slate-900"><?php echo e($r->entry->distributor?->name ?? '—'); ?></span>
                                <span class="block text-[10px] font-mono text-slate-400 mt-0.5"><?php echo e($r->entry->distributor?->distributor_code); ?></span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-extrabold text-slate-900 text-sm">
                                <?php echo e(number_format($r->entry->quantity, 0, ',', '.')); ?>

                            </td>
                            <td class="py-3.5 px-4 text-right font-mono">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->delta_pct === null): ?>
                                    <span class="text-slate-400">—</span>
                                <?php elseif($r->delta_pct > 0): ?>
                                    <span class="inline-flex items-center gap-0.5 text-emerald-800 font-bold bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full text-[11px]">
                                        ▲ +<?php echo e($r->delta_pct); ?>%
                                    </span>
                                <?php elseif($r->delta_pct < 0): ?>
                                    <span class="inline-flex items-center gap-0.5 text-rose-800 font-bold bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full text-[11px]">
                                        ▼ <?php echo e($r->delta_pct); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 font-medium">0%</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px]">
                                <?php
                                    $expStatus = $r->entry->expiryStatus();
                                    $days = $r->entry->daysToExpiry();
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->entry->expired_date): ?>
                                    <span class="font-bold <?php echo e($expStatus === 'critical' ? 'text-rose-600' : ($expStatus === 'warning' ? 'text-amber-700' : 'text-slate-700')); ?>">
                                        <?php echo e($r->entry->expired_date->format('Y-m-d')); ?>

                                    </span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($days !== null): ?>
                                        <span class="text-[10px] text-slate-400 block mt-0.5">
                                            (<?php echo e($days < 0 ? 'Lewat '.abs($days).'h' : $days.' hari lagi'); ?>)
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->entry->batch_no): ?>
                                    <span class="text-[10px] text-slate-500 font-mono block mt-0.5">B: <?php echo e($r->entry->batch_no); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="py-14 text-center text-slate-400">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="font-bold text-slate-600 text-sm">Tidak ada data stok yang cocok dengan filter</div>
                                <div class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau cabang distributor</div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Summary -->
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan <b class="text-slate-800"><?php echo e($stockTable->firstItem() ?? 0); ?></b> - <b class="text-slate-800"><?php echo e($stockTable->lastItem() ?? 0); ?></b> dari <b class="text-slate-800"><?php echo e($stockTable->total()); ?></b> baris stok
            </div>
            <div>
                <?php echo e($stockTable->links('livewire::tailwind')); ?>

            </div>
        </div>
    </div>

    <!-- Section Early Warning: Batch Kadaluarsa Kritis -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiryAlerts->isNotEmpty()): ?>
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $expiryAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $days = $alert->daysToExpiry();
                        $status = $alert->expiryStatus();
                    ?>
                    <div class="p-4 rounded-xl border <?php echo e($status === 'critical' ? 'border-rose-200 bg-white' : 'border-amber-200 bg-white'); ?> text-xs shadow-2xs hover:shadow-sm transition">
                        <div class="font-bold text-slate-900 truncate text-xs"><?php echo e($alert->distributorItem?->item_name); ?></div>
                        <div class="text-slate-500 truncate text-[11px] mt-0.5"><?php echo e($alert->distributor?->name); ?></div>
                        <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100 font-mono">
                            <span class="text-slate-600">Qty: <b class="text-slate-900"><?php echo e(number_format($alert->quantity, 0, ',', '.')); ?></b></span>
                            <span class="font-bold <?php echo e($status === 'critical' ? 'text-rose-700' : 'text-amber-800'); ?>">
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
    <div class="space-y-6 mb-7">
        <!-- 4 Summary Cards FEFO (Static Sans-serif without jumpy hover) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div wire:click="$set('expiryRiskFilter', 'critical')" class="bg-white border <?php echo e($expiryRiskFilter === 'critical' ? 'border-rose-500 ring-2 ring-rose-200 shadow-sm' : 'border-slate-200/80'); ?> rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-rose-700 font-bold">🔴 Kritis (&lt; 3 Bulan)</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">FEFO Prioritas</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-rose-700 tabular-nums tracking-tight">
                    <?php echo e($fefoSummary['critical']); ?> <span class="text-xs font-normal text-slate-500">batch</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Berisiko tinggi ditolak RS / Apotek</div>
            </div>

            <div wire:click="$set('expiryRiskFilter', 'warning')" class="bg-white border <?php echo e($expiryRiskFilter === 'warning' ? 'border-amber-500 ring-2 ring-amber-200 shadow-sm' : 'border-slate-200/80'); ?> rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-amber-700 font-bold">🟡 Waspada (3 - 6 Bulan)</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold">Near-ED</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-amber-700 tabular-nums tracking-tight">
                    <?php echo e($fefoSummary['warning']); ?> <span class="text-xs font-normal text-slate-500">batch</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Akselerasi penjualan ke cabang</div>
            </div>

            <div wire:click="$set('expiryRiskFilter', 'expired')" class="bg-white border <?php echo e($expiryRiskFilter === 'expired' ? 'border-red-600 ring-2 ring-red-200 shadow-sm' : 'border-slate-200/80'); ?> rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-red-700 font-bold">⛔ Sudah Expired</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 font-bold">Karantina</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-red-700 tabular-nums tracking-tight">
                    <?php echo e($fefoSummary['expired']); ?> <span class="text-xs font-normal text-slate-500">batch</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Wajib ditarik & isolasi retur</div>
            </div>

            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-slate-600 font-bold">📦 Total Qty Berisiko ED</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold">Fisik</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-slate-900 tabular-nums tracking-tight">
                    <?php echo e(number_format($fefoSummary['total_qty_at_risk'], 0, ',', '.')); ?>

                </div>
                <div class="text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-100">Total unit batch expired &lt;6 bulan</div>
            </div>
        </div>

        <!-- Tabel FEFO & Filter Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="pb-5 border-b border-slate-100 mb-5 space-y-3.5">
                <!-- Baris 1: Judul di kiri & Tombol Ekspor CSV di kanan -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Daftar Batch Terurut Kedaluwarsa (FEFO Order)</h3>
                        <p class="text-xs text-slate-500 mt-1">Urutan teratas adalah batch yang paling mendekati tanggal kedaluwarsa</p>
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

                <!-- Baris 2: Filter Risiko (Segmented Control) & Kolom Pencarian -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
                    <!-- Sub-Filter Buttons as Segmented Control -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs overflow-x-auto">
                        <button type="button" wire:click="$set('expiryRiskFilter', 'all')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($expiryRiskFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'); ?>">
                            Semua (<?php echo e($fefoSummary['total']); ?>)
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'critical')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($expiryRiskFilter === 'critical' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 hover:bg-rose-50'); ?>">
                            Kritis (<?php echo e($fefoSummary['critical']); ?>)
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'warning')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($expiryRiskFilter === 'warning' ? 'bg-amber-500 text-white shadow-2xs' : 'text-amber-700 hover:bg-amber-50'); ?>">
                            Waspada (<?php echo e($fefoSummary['warning']); ?>)
                        </button>
                        <button type="button" wire:click="$set('expiryRiskFilter', 'safe')"
                                class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($expiryRiskFilter === 'safe' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-emerald-700 hover:bg-emerald-50'); ?>">
                            Aman (<?php echo e($fefoSummary['safe']); ?>)
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="expirySearch"
                               placeholder="Cari produk, batch, cabang..."
                               class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
                    </div>
                </div>
            </div>

            <!-- Tabel Data FEFO -->
            <div class="border border-slate-200/80 rounded-2xl overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead style="background: #f8faf9;" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">#</th>
                            <th class="py-3.5 px-4">Produk Satoria & Distributor</th>
                            <th class="py-3.5 px-4 min-w-[190px] max-w-[240px]">Distributor / Cabang</th>
                            <th class="py-3.5 px-4 w-28">No. Batch</th>
                            <th class="py-3.5 px-4 w-32 text-center">Expired Date</th>
                            <th class="py-3.5 px-4 w-36 text-center">Sisa Waktu</th>
                            <th class="py-3.5 px-4 w-28 text-right">Kuantitas</th>
                            <th class="py-3.5 px-4 w-52">Status & Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $fefoTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $e = $r->entry;
                                $ns = $e->distributorItem?->netsuiteItem;
                            ?>
                            <tr class="hover:bg-[#f8faf9] transition duration-150">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono">
                                    <?php echo e(($fefoTable->currentPage() - 1) * $perPage + $idx + 1); ?>

                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-xs"><?php echo e($e->distributorItem?->item_name); ?></div>
                                    <div class="font-mono text-[10px] text-slate-500 mt-0.5">
                                        <?php echo e($ns ? "[{$ns->netsuite_id}] {$ns->netsuite_name}" : 'Belum Ter-mapping'); ?>

                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900"><?php echo e($e->distributor?->name); ?></span>
                                    <span class="block text-[10px] font-mono text-slate-400 mt-0.5"><?php echo e($e->distributor?->distributor_code); ?></span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    <?php echo e($e->batch_no ?: '—'); ?>

                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">
                                    <span class="font-bold <?php echo e($r->tier === 'expired' || $r->tier === 'critical' ? 'text-rose-700' : ($r->tier === 'warning' ? 'text-amber-700' : 'text-slate-700')); ?>">
                                        <?php echo e($e->expired_date ? $e->expired_date->format('d/m/Y') : '—'); ?>

                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->days < 0): ?>
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 font-bold text-[10px]">
                                            Lewat <?php echo e(abs($r->days)); ?> hari
                                        </span>
                                    <?php elseif($r->days <= 90): ?>
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px]">
                                            <?php echo e($r->days); ?> hari (~<?php echo e(round($r->days/30, 1)); ?> bln)
                                        </span>
                                    <?php elseif($r->days <= 180): ?>
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold text-[10px]">
                                            <?php echo e($r->days); ?> hari (~<?php echo e(round($r->days/30, 1)); ?> bln)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-emerald-700 font-bold text-[11px]">
                                            <?php echo e($r->days); ?> hari
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-slate-900">
                                    <?php echo e(number_format($e->quantity, 0, ',', '.')); ?>

                                    <span class="block text-[10px] text-slate-400 font-normal"><?php echo e($e->satuan); ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-mono uppercase font-bold border <?php echo e($r->badgeClass); ?>">
                                        <?php echo e($r->label); ?>

                                    </span>
                                    <span class="block text-[10px] text-slate-500 mt-1 font-medium leading-tight">
                                        <?php echo e($r->action); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="text-3xl mb-2">🗓️</div>
                                    <div class="font-bold text-slate-600 text-sm">Tidak ada data batch yang sesuai dengan filter kedaluwarsa</div>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination FEFO -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <b class="text-slate-800"><?php echo e($fefoTable->firstItem() ?? 0); ?></b> - <b class="text-slate-800"><?php echo e($fefoTable->lastItem() ?? 0); ?></b> dari <b class="text-slate-800"><?php echo e($fefoTable->total()); ?></b> batch
                </div>
                <div>
                    <?php echo e($fefoTable->links('livewire::tailwind')); ?>

                </div>
            </div>
        </div>
    </div>
    <?php elseif($activeTab === 'compliance'): ?>
    <!-- TAB 3: KEPATUHAN UPLOAD CABANG (COMPLIANCE TRACKER) -->
    <div class="space-y-6 mb-7">
        <!-- 4 Summary Cards Kepatuhan (Static Sans-serif without jumpy hover) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div class="text-[11px] uppercase text-slate-500 font-bold">TOTAL CABANG AKTIF</div>
                <div class="text-3xl font-extrabold mt-3 text-slate-900 tabular-nums tracking-tight">
                    <?php echo e($complianceSummary['total_branches']); ?> <span class="text-xs font-normal text-slate-500">gudang</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-100">Seluruh cabang terdaftar</div>
            </div>

            <div wire:click="$set('complianceStatus', 'submitted')" class="bg-white border <?php echo e($complianceStatus === 'submitted' ? 'border-emerald-500 ring-2 ring-emerald-200 shadow-sm' : 'border-slate-200/80'); ?> rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-emerald-700 font-bold">✅ Sudah Lapor</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Tepat Waktu</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-emerald-700 tabular-nums tracking-tight">
                    <?php echo e($complianceSummary['total_submitted']); ?> <span class="text-xs font-normal text-slate-500">cabang</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Data stok terekam di tanggal acuan</div>
            </div>

            <div wire:click="$set('complianceStatus', 'missing')" class="bg-white border <?php echo e($complianceStatus === 'missing' ? 'border-rose-500 ring-2 ring-rose-200 shadow-sm' : 'border-slate-200/80'); ?> rounded-2xl p-5 shadow-xs cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-rose-700 font-bold">⚠️ Belum Lapor</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">Follow-up</span>
                </div>
                <div class="text-3xl font-extrabold mt-3 text-rose-700 tabular-nums tracking-tight">
                    <?php echo e($complianceSummary['total_missing']); ?> <span class="text-xs font-normal text-slate-500">cabang</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">Belum setor snapshot stok</div>
            </div>

            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] uppercase text-slate-600 font-bold">TINGKAT KEPATUHAN</span>
                    <span class="text-xs font-bold <?php echo e($complianceSummary['compliance_rate'] >= 80 ? 'text-emerald-700' : 'text-amber-700'); ?>">
                        <?php echo e($complianceSummary['compliance_rate']); ?>%
                    </span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-2.5 mt-3 overflow-hidden">
                    <div class="h-2.5 rounded-full transition-all duration-500"
                         style="width: <?php echo e(min(100, $complianceSummary['compliance_rate'])); ?>%; background: linear-gradient(90deg, #10b981 0%, #0d6d5f 100%);"></div>
                </div>
                <div class="text-[10px] text-slate-400 mt-2 pt-2 border-t border-slate-100 flex justify-between">
                    <span>Target: 100%</span>
                    <span>Acuan: <?php echo e(\Illuminate\Support\Carbon::parse($complianceSummary['target_date'])->translatedFormat('d M Y')); ?></span>
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
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tgl Acuan:</span>
                            <input type="date" wire:model.live="complianceDate"
                                   class="text-xs border-0 bg-transparent text-slate-900 font-bold focus:outline-none focus:ring-0 cursor-pointer">
                        </div>

                        <!-- Sub-Filter Buttons as Segmented Control -->
                        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs shadow-2xs">
                            <button type="button" wire:click="$set('complianceStatus', 'all')"
                                    class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($complianceStatus === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'); ?>">
                                Semua (<?php echo e($complianceSummary['total_branches']); ?>)
                            </button>
                            <button type="button" wire:click="$set('complianceStatus', 'submitted')"
                                    class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($complianceStatus === 'submitted' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-emerald-700 hover:bg-emerald-50'); ?>">
                                Sudah (<?php echo e($complianceSummary['total_submitted']); ?>)
                            </button>
                            <button type="button" wire:click="$set('complianceStatus', 'missing')"
                                    class="px-3 py-1.5 rounded-lg cursor-pointer transition font-bold <?php echo e($complianceStatus === 'missing' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 hover:bg-rose-50'); ?>">
                                Belum (<?php echo e($complianceSummary['total_missing']); ?>)
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
                        Total <span class="font-bold text-slate-800"><?php echo e($complianceTable->total()); ?></span> cabang terdaftar
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
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $complianceTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-[#f8faf9] transition duration-150">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono">
                                    <?php echo e(($complianceTable->currentPage() - 1) * $perPage + $idx + 1); ?>

                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-xs"><?php echo e($row->distributor->name); ?></div>
                                    <div class="font-mono text-[10px] text-slate-500 mt-0.5"><?php echo e($row->distributor->distributor_code); ?></div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?php echo e(\App\Livewire\Dashboard::getDistributorGroup($row->distributor->distributor_code)); ?>

                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->hasSubmitted): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                            <span>✓ Sudah Lapor</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200 animate-pulse shadow-2xs">
                                            <span>✕ Belum Lapor</span>
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->lastDate): ?>
                                        <div class="font-bold text-slate-800 text-[11px]">
                                            <?php echo e(\Illuminate\Support\Carbon::parse($row->lastDate)->translatedFormat('d M Y')); ?>

                                        </div>
                                        <span class="text-[10px] text-slate-500 mt-0.5 block">
                                            <?php echo e($row->daysOverdue !== null ? ($row->daysOverdue === 0 ? 'Hari ini' : $row->daysOverdue . ' hari lalu') : '—'); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic text-[11px]">Belum pernah lapor</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->lastDate): ?>
                                        <div class="font-extrabold text-slate-900"><?php echo e(number_format($row->totalQty, 0, ',', '.')); ?></div>
                                        <span class="text-[10px] text-slate-500 font-normal mt-0.5 block"><?php echo e($row->totalRows); ?> baris</span>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <!-- Icon Riwayat -->
                                        <a href="<?php echo e(route('stock.history', ['distributor_id' => $row->distributor->id])); ?>"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-emerald-50 hover:bg-[#0d6d5f] hover:text-white border border-emerald-200/80 transition duration-150 shadow-2xs"
                                           title="Lihat Riwayat Stok Cabang">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </a>

                                        <!-- Icon Upload -->
                                        <a href="<?php echo e(route('stock.upload', ['distributor_id' => $row->distributor->id])); ?>"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white border border-blue-200/80 transition duration-150 shadow-2xs"
                                           title="Unggah Stok Cabang">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="text-3xl mb-2">🏢</div>
                                    <div class="font-bold text-slate-600 text-sm">Tidak ada data cabang distributor yang cocok dengan filter kepatuhan</div>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Kepatuhan -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <b class="text-slate-800"><?php echo e($complianceTable->firstItem() ?? 0); ?></b> - <b class="text-slate-800"><?php echo e($complianceTable->lastItem() ?? 0); ?></b> dari <b class="text-slate-800"><?php echo e($complianceTable->total()); ?></b> cabang
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
        $__scriptKey = '1244352516-0';
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
<?php /**PATH /var/www/html/resources/views/livewire/dashboard.blade.php ENDPATH**/ ?>