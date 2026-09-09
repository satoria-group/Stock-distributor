<div>
    <!-- Top Filter Bar (1 Baris) -->
    <div class="flex items-center justify-between gap-2.5 mb-6 flex-nowrap overflow-x-auto pb-1">
        <div class="flex items-center gap-2.5 flex-nowrap shrink-0">
            <!-- Search Input -->
            <div class="relative w-52 sm:w-60 shrink-0">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama item distributor..."
                       class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition shadow-2xs">
            </div>

            <!-- Distributor Select Filter -->
            <div class="w-48 sm:w-52 shrink-0">
                <select wire:model.live="distributorFilter"
                        class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition shadow-2xs truncate">
                    <option value="">— Semua Distributor —</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $distributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>

            <!-- Status Mapping Segmented Filter -->
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs shadow-2xs shrink-0">
                <button type="button" wire:click="$set('mappingFilter', 'all')"
                        class="px-2.5 py-1.5 rounded-lg cursor-pointer transition font-semibold <?php echo e($mappingFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'); ?>">
                    Semua
                </button>
                <button type="button" wire:click="$set('mappingFilter', 'mapped')"
                        class="px-2.5 py-1.5 rounded-lg cursor-pointer transition font-semibold <?php echo e($mappingFilter === 'mapped' ? 'bg-[#0d6d5f] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'); ?>">
                    Ter-mapping
                </button>
                <button type="button" wire:click="$set('mappingFilter', 'unmapped')"
                        class="px-2.5 py-1.5 rounded-lg cursor-pointer transition font-semibold <?php echo e($mappingFilter === 'unmapped' ? 'bg-amber-500 text-white shadow-2xs font-bold' : 'text-amber-800 hover:bg-amber-50'); ?>">
                    Belum ter-mapping <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unmappedCount): ?> (<?php echo e($unmappedCount); ?>) <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Action Buttons (Right Aligned on Same Row) -->
        <div class="flex items-center gap-2 shrink-0">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($bulkSuggestions) > 0): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\DistributorItem::class)): ?>
                <button type="button" wire:click="openBulkModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold shadow-2xs transition cursor-pointer shrink-0 whitespace-nowrap"
                        title="Tinjau dan setujui pemetaan rekomendasi dengan 1 klik">
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Tinjau & Setujui Saran (<?php echo e(count($bulkSuggestions)); ?> Item)</span>
                </button>
                <?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\DistributorItem::class)): ?>
            <button type="button" wire:click="openCreate"
                    class="inline-flex items-center gap-1.5 text-xs bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold rounded-xl px-3.5 py-2 cursor-pointer transition shadow-2xs shrink-0 whitespace-nowrap">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Mapping Item</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table Data -->
    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5">Distributor</th>
                    <th class="px-5 py-3.5">Nama Item (Distributor)</th>
                    <th class="px-5 py-3.5 w-28 text-center">Satuan</th>
                    <th class="px-5 py-3.5">Produk Netsuite / Rekomendasi Cerdas</th>
                    <th class="px-5 py-3.5 w-44 text-center">Status</th>
                    <th class="px-5 py-3.5 w-36 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-3.5 text-slate-700">
                            <span class="font-bold text-slate-900 text-xs"><?php echo e($item->distributor->name); ?></span>
                            <span class="block text-[10px] font-mono text-slate-400 mt-0.5"><?php echo e($item->distributor->distributor_code); ?></span>
                        </td>
                        <td class="px-5 py-3.5 font-bold text-slate-900 text-xs">
                            <?php echo e($item->item_name); ?>

                        </td>
                        <td class="px-5 py-3.5 text-center font-mono">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px] border border-slate-200/60">
                                <?php echo e($item->satuan ?: '—'); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->isMapped()): ?>
                                <span class="font-mono text-[10px] text-slate-500"><?php echo e($item->netsuiteItem->netsuite_id); ?></span>
                                <div class="text-xs font-bold text-slate-900 leading-tight"><?php echo e($item->netsuiteItem->netsuite_name); ?></div>
                            <?php else: ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($suggestions[$item->id])): ?>
                                    <?php
                                        $sug = $suggestions[$item->id];
                                        $score = $sug['score'];
                                        $isHigh = $score >= 70;
                                        $isMedium = $score >= 45 && $score < 70;
                                        $isLow = $score < 45;
                                    ?>
                                    <div class="p-3 rounded-xl max-w-md border <?php echo e($isHigh ? 'bg-emerald-50/70 border-emerald-200' : ($isMedium ? 'bg-teal-50/70 border-teal-200' : 'bg-amber-50/70 border-amber-200')); ?> shadow-2xs">
                                        <div class="flex items-center justify-between gap-1 mb-1.5">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold <?php echo e($isHigh ? 'text-emerald-900' : ($isMedium ? 'text-teal-900' : 'text-amber-900')); ?>">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isLow): ?>
                                                    <span>🔍 Kandidat Terdekat (Skor <?php echo e($score); ?>%)</span>
                                                <?php else: ?>
                                                    <span>💡 Rekomendasi (Skor <?php echo e($score); ?>%)</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sug['match_type'] === 'historical'): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-800">Riwayat Cabang Lain</span>
                                            <?php elseif($isHigh): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">Smart Attribute</span>
                                            <?php elseif($isMedium): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-100 text-teal-800">Kemiripan Nama</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800">Skor Rendah</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                        <div class="font-mono text-[10px] text-slate-500"><?php echo e($sug['best_match']->netsuite_id); ?></div>
                                        <div class="text-xs font-bold text-slate-900 leading-tight mb-2.5"><?php echo e($sug['best_match']->netsuite_name); ?></div>

                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $item)): ?>
                                        <button type="button"
                                                wire:click="approveMapping(<?php echo e($item->id); ?>, <?php echo e($sug['best_match']->id); ?>)"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold text-xs shadow-2xs transition cursor-pointer disabled:opacity-50"
                                                title="Setujui dan petakan produk ini secara instan">
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>1-Click Setujui</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-xs text-slate-400 italic">
                                        Belum ada rekomendasi yang cocok
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->isMapped()): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    ✓ Ter-mapping
                                </span>
                            <?php else: ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($suggestions[$item->id])): ?>
                                    <?php $sScore = $suggestions[$item->id]['score']; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sScore >= 70): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Saran Tinggi (<?php echo e($sScore); ?>%)
                                        </span>
                                    <?php elseif($sScore >= 45): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            Ada Saran (<?php echo e($sScore); ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Kandidat (<?php echo e($sScore); ?>%)
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Belum Mapping
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-1.5">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $item)): ?>
                            <button type="button" wire:click="openEdit(<?php echo e($item->id); ?>)"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 transition shadow-2xs cursor-pointer"
                                    title="Edit Mapping (<?php echo e($item->item_name); ?>)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $item)): ?>
                            <button type="button" wire:click="delete(<?php echo e($item->id); ?>)" wire:confirm="Hapus mapping item ini?"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shadow-2xs cursor-pointer"
                                    title="Hapus Mapping">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center text-slate-400">
                            <div class="text-3xl mb-2">🔍</div>
                            <div class="font-bold text-slate-700 text-sm">Tidak ada data mapping item</div>
                            <div class="text-xs text-slate-400 mt-0.5">Silakan sesuaikan kata kunci pencarian atau pilih distributor lain.</div>
                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-2">
        <?php echo e($items->links('livewire::tailwind')); ?>

    </div>

    <!-- Modal 1: Bulk Approval Review Modal -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showBulkModal): ?>
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showBulkModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Tinjau & Setujui Rekomendasi Mapping Massal</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Sistem mendeteksi <b><?php echo e(count($bulkSuggestions)); ?> item</b> dengan rekomendasi kecocokan yang siap disetujui dalam 1 klik.</p>
                </div>
                <button type="button" wire:click="$set('showBulkModal', false)" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">✕</button>
            </div>

            <div class="overflow-y-auto p-6 flex-1 divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($bulkSuggestions) === 0): ?>
                    <div class="text-center py-8 text-slate-400 text-xs">
                        Tidak ada item unmapped yang memiliki rekomendasi dengan skor kepercayaan mencukupi.
                    </div>
                <?php else: ?>
                    <div class="flex items-center justify-between pb-3 text-xs text-slate-600 font-medium">
                        <div>
                            Pilih item yang ingin disetujui pemetaannya (<b><?php echo e(count($selectedBulkIds)); ?></b> dari <?php echo e(count($bulkSuggestions)); ?> terpilih):
                        </div>
                        <button type="button" wire:click="toggleAllBulk(<?php echo e(json_encode(array_keys($bulkSuggestions))); ?>)" class="text-xs font-bold text-[#0d6d5f] hover:underline cursor-pointer">
                            <?php echo e(count($selectedBulkIds) === count($bulkSuggestions) ? 'Batal Pilih Semua' : 'Pilih Semua'); ?>

                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-2xl overflow-hidden text-xs">
                        <table class="w-full">
                            <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-3 w-10 text-center">Pilih</th>
                                    <th class="py-3 px-3 text-left">Item Distributor</th>
                                    <th class="py-3 px-3 text-left">Rekomendasi Netsuite</th>
                                    <th class="py-3 px-3 text-center w-24">Skor</th>
                                    <th class="py-3 px-3 text-right w-28">Aksi 1-Click</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $bulkSuggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemId => $res): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $distItem = $allUnmapped->firstWhere('id', $itemId); ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($distItem): ?>
                                        <tr class="hover:bg-emerald-50/40 transition">
                                            <td class="py-3 px-3 text-center">
                                                <input type="checkbox" wire:model.live="selectedBulkIds" value="<?php echo e($itemId); ?>" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] cursor-pointer">
                                            </td>
                                            <td class="py-3 px-3 font-semibold text-slate-900">
                                                <?php echo e($distItem->item_name); ?>

                                                <span class="block text-[10px] text-slate-400 font-mono font-normal mt-0.5"><?php echo e($distItem->distributor->name); ?> (<?php echo e($distItem->distributor->distributor_code); ?>)</span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="font-mono text-[10px] text-slate-500"><?php echo e($res['best_match']->netsuite_id); ?></span>
                                                <div class="font-bold text-slate-900 text-xs"><?php echo e($res['best_match']->netsuite_name); ?></div>
                                            </td>
                                            <td class="py-3 px-3 text-center font-mono">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($res['score'] >= 70): ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px]"><?php echo e($res['score']); ?>%</span>
                                                <?php elseif($res['score'] >= 45): ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 font-bold text-[11px]"><?php echo e($res['score']); ?>%</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold text-[10px]"><?php echo e($res['score']); ?>%</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-right">
                                                <button type="button" wire:click="approveMapping(<?php echo e($itemId); ?>, <?php echo e($res['best_match']->id); ?>)"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#0d6d5f] hover:bg-[#07352d] text-white font-semibold text-xs cursor-pointer transition shadow-2xs"
                                                        title="1-Click Setujui item ini">
                                                    <span>Setujui</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['bulk'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-rose-600 mt-2 font-medium"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <button type="button" wire:click="$set('showBulkModal', false)" class="text-xs font-semibold text-slate-600 px-4 py-2.5 rounded-xl hover:bg-slate-200 transition cursor-pointer">
                    Tutup
                </button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($bulkSuggestions) > 0): ?>
                <button type="button" wire:click="approveSelectedBulk"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 text-xs bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold px-5 py-2.5 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>1-Click Setujui Semua (<?php echo e(count($selectedBulkIds)); ?> Item)</span>
                </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Modal 2: Edit/Create Modal -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showModal): ?>
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto border border-slate-200">
            <h3 class="text-base font-bold text-slate-900 mb-1"><?php echo e($editingId ? 'Edit Mapping Item' : 'Tambah Mapping Item Baru'); ?></h3>
            <p class="text-xs text-slate-500 mb-5 font-mono"><?php echo e($editingId ? $item_name : 'Daftarkan nama item versi distributor'); ?></p>

            <form wire:submit="save" class="space-y-4 text-xs">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $editingId): ?>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Distributor</label>
                    <select wire:model="distributor_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        <option value="">— Pilih Distributor —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $distributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?> (<?php echo e($d->distributor_code); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['distributor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama Item (Versi Distributor)</label>
                    <input type="text" wire:model.live.debounce.400ms="item_name" placeholder="Contoh: RINGER LACTATE 500 mL" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['item_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Satuan (Distributor)</label>
                    <input type="text" wire:model="satuan" placeholder="Contoh: BOTOL, BOX, PCS" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['satuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Rekomendasi Cerdas Sistem -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($modalSuggestions)): ?>
                <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-900">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Rekomendasi Cerdas dari Sistem:</span>
                    </div>
                    <div class="space-y-1.5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $modalSuggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div wire:click="selectSuggestion(<?php echo e($rec['item']->id); ?>)"
                                 class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer transition <?php echo e($netsuite_item_id === $rec['item']->id ? 'bg-emerald-100 border-emerald-500 shadow-2xs' : 'bg-white border-slate-200 hover:bg-emerald-50/50'); ?>">
                                <div class="flex-1 pr-2">
                                    <div class="font-mono text-[10px] text-slate-500"><?php echo e($rec['item']->netsuite_id); ?></div>
                                    <div class="text-xs font-bold text-slate-900 leading-tight"><?php echo e($rec['item']->netsuite_name); ?></div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="inline-block font-mono font-bold text-[11px] px-2 py-0.5 rounded-full <?php echo e($rec['score'] >= 70 ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : ($rec['score'] >= 45 ? 'bg-teal-100 text-teal-900 border border-teal-300' : 'bg-amber-100 text-amber-900 border border-amber-300')); ?>">
                                        <?php echo e($rec['score']); ?>%
                                    </span>
                                    <span class="block text-[9px] text-emerald-800 font-bold mt-0.5">
                                        <?php echo e($netsuite_item_id === $rec['item']->id ? '✓ Terpilih' : 'Klik Gunakan'); ?>

                                    </span>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Produk Netsuite (Pilihan Manual)</label>
                    <select wire:model="netsuite_item_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        <option value="">— Belum ter-mapping —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $netsuiteItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ns): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($ns->id); ?>"><?php echo e($ns->netsuite_id); ?> — <?php echo e($ns->netsuite_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['netsuite_item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showModal', false)" class="text-xs font-semibold text-slate-600 px-4 py-2.5 rounded-xl hover:bg-slate-100 cursor-pointer">Batal</button>
                    <button type="submit" class="text-xs bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold px-5 py-2.5 rounded-xl shadow-2xs cursor-pointer transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/distributor-items/index.blade.php ENDPATH**/ ?>