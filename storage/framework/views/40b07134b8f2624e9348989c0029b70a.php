<div>
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <select wire:model.live="distributorId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            <option value="">Semua Distributor</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $distributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari produk..."
               class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($latestDate): ?>
            <span class="text-xs text-gray-500 font-mono">Data terkini: <?php echo e(\Illuminate\Support\Carbon::parse($latestDate)->format('d M Y')); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $latestDate): ?>
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-10 text-center text-gray-400 text-sm">
            Belum ada data stock. Silakan upload stock harian dulu.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">Distributor Aktif</div>
                <div class="text-2xl font-bold mt-1 tabular-nums"><?php echo e($kpi['distributors']); ?></div>
            </div>
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">SKU Termonitor</div>
                <div class="text-2xl font-bold mt-1 tabular-nums"><?php echo e($kpi['skus']); ?></div>
            </div>
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">Item Belum Ter-mapping</div>
                <div class="text-2xl font-bold mt-1 tabular-nums <?php echo e($kpi['unmapped'] > 0 ? 'text-amber-600' : ''); ?>"><?php echo e($kpi['unmapped']); ?></div>
            </div>
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">Batch Mendekati/Lewat ED</div>
                <div class="text-2xl font-bold mt-1 tabular-nums <?php echo e($kpi['expiring_soon'] > 0 ? 'text-red-600' : ''); ?>"><?php echo e($kpi['expiring_soon']); ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Stock Terkini per Distributor × Produk</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-[11px] uppercase tracking-wide text-gray-500 font-mono border-b border-[#e7e9e3]">
                                <tr>
                                    <th class="text-left py-2">Distributor</th>
                                    <th class="text-left py-2">Produk</th>
                                    <th class="text-right py-2">Qty</th>
                                    <th class="text-right py-2">Δ vs sebelumnya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#e7e9e3]">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $stockTable->take(30); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="py-2"><?php echo e($r->entry->distributor->name); ?></td>
                                        <td class="py-2">
                                            <?php echo e($r->entry->distributorItem->item_name); ?>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($r->entry->distributorItem->isMapped())): ?>
                                                <span class="ml-1 text-[10px] font-mono uppercase bg-amber-100 text-amber-700 rounded-full px-1.5 py-0.5">belum ter-mapping</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                        <td class="py-2 text-right tabular-nums"><?php echo e(number_format($r->entry->quantity, 0)); ?></td>
                                        <td class="py-2 text-right tabular-nums">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->delta_pct === null): ?>
                                                <span class="text-gray-400">—</span>
                                            <?php elseif($r->delta_pct > 0): ?>
                                                <span class="text-brand">▲ <?php echo e($r->delta_pct); ?>%</span>
                                            <?php elseif($r->delta_pct < 0): ?>
                                                <span class="text-red-600">▼ <?php echo e(abs($r->delta_pct)); ?>%</span>
                                            <?php else: ?>
                                                <span class="text-gray-400">0%</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="4" class="py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Tren Total Stock (<?php echo e(count($trend['labels'])); ?> snapshot terakhir)</h3>
                    <canvas id="trend-chart" height="90"></canvas>
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Alert Kadaluarsa</h3>
                    <div class="space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $expiryAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $status = $r->expiryStatus();
                                $badgeClass = match($status) {
                                    'expired' => 'bg-red-100 text-red-700',
                                    'critical' => 'bg-red-50 text-red-600',
                                    'warning' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-brand-soft text-brand-dark',
                                };
                                $days = $r->daysToExpiry();
                            ?>
                            <div class="flex items-center justify-between text-sm">
                                <div class="min-w-0">
                                    <div class="truncate"><?php echo e($r->distributorItem->item_name); ?></div>
                                    <div class="text-xs text-gray-400 font-mono"><?php echo e($r->batch_no ?: '—'); ?></div>
                                </div>
                                <span class="text-xs font-mono uppercase <?php echo e($badgeClass); ?> rounded-full px-2 py-0.5 shrink-0">
                                    <?php echo e($days < 0 ? 'lewat '.abs($days).'h' : $days.' hari'); ?>

                                </span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-sm text-gray-400">Tidak ada batch dengan ED tercatat.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Top Movers</h3>
                    <div class="space-y-2 text-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topMovers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="flex items-center justify-between">
                                <span class="truncate"><?php echo e($r->entry->distributorItem->item_name); ?></span>
                                <span class="<?php echo e($r->delta_pct > 0 ? 'text-brand' : 'text-red-600'); ?> font-mono text-xs shrink-0">
                                    <?php echo e($r->delta_pct > 0 ? '▲' : '▼'); ?> <?php echo e(abs($r->delta_pct)); ?>%
                                </span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-gray-400">Belum ada perbandingan snapshot.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Slow Moving (stagnan)</h3>
                    <div class="space-y-2 text-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $slowMovers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="flex items-center justify-between">
                                <span class="truncate"><?php echo e($r->entry->distributorItem->item_name); ?></span>
                                <span class="text-gray-400 font-mono text-xs shrink-0"><?php echo e(number_format($r->entry->quantity, 0)); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-gray-400">Tidak ada data.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php
        $__scriptKey = '2198190063-0';
        ob_start();
    ?>
    <script>
        let trendData = <?php echo \Illuminate\Support\Js::from($trend)->toHtml() ?>;
        let chartInstance = null;

        function renderChart() {
            const canvas = document.getElementById('trend-chart');
            if (!canvas || !window.Chart) return;
            if (chartInstance) chartInstance.destroy();
            chartInstance = new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: 'Total Qty',
                        data: trendData.values,
                        borderColor: '#0d6d5f',
                        backgroundColor: 'rgba(13,109,95,0.12)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        }

        renderChart();

        // Re-draw after any Livewire update on this component (filter change, etc.)
        // so the trend chart stays in sync with fresh server-computed data.
        let dashboardComponentId = window.Livewire.find('<?php echo e($_instance->getId()); ?>').__instance?.id ?? null;
        if (window.Livewire && dashboardComponentId) {
            window.Livewire.hook('morph.updated', ({ component }) => {
                if (component?.id === dashboardComponentId) {
                    trendData = <?php echo \Illuminate\Support\Js::from($trend)->toHtml() ?>;
                    renderChart();
                }
            });
        }
    </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
</div>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/dashboard.blade.php ENDPATH**/ ?>