<div>
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama item..."
               class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        <select wire:model.live="distributorFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            <option value="">Semua Distributor</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $distributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>

        <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
            <button wire:click="$set('mappingFilter', 'all')" class="px-3 py-2 <?php echo e($mappingFilter === 'all' ? 'bg-brand text-white' : 'bg-white text-gray-600'); ?>">Semua</button>
            <button wire:click="$set('mappingFilter', 'mapped')" class="px-3 py-2 border-l border-gray-300 <?php echo e($mappingFilter === 'mapped' ? 'bg-brand text-white' : 'bg-white text-gray-600'); ?>">Ter-mapping</button>
            <button wire:click="$set('mappingFilter', 'unmapped')" class="px-3 py-2 border-l border-gray-300 <?php echo e($mappingFilter === 'unmapped' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600'); ?>">
                Belum ter-mapping <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($unmappedCount): ?> (<?php echo e($unmappedCount); ?>) <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </button>
        </div>

        <div class="flex-1"></div>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\DistributorItem::class)): ?>
        <button wire:click="openCreate" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium rounded-lg px-4 py-2">
            + Tambah Item Mapping
        </button>
        <?php endif; ?>
    </div>

    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-500 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Distributor</th>
                    <th class="text-left px-4 py-2.5">Nama Item (Distributor)</th>
                    <th class="text-left px-4 py-2.5">Satuan</th>
                    <th class="text-left px-4 py-2.5">Produk Netsuite</th>
                    <th class="text-left px-4 py-2.5">Status</th>
                    <th class="text-right px-4 py-2.5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-600"><?php echo e($item->distributor->name); ?></td>
                        <td class="px-4 py-2.5"><?php echo e($item->item_name); ?></td>
                        <td class="px-4 py-2.5 text-gray-500"><?php echo e($item->satuan ?: '—'); ?></td>
                        <td class="px-4 py-2.5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->netsuiteItem): ?>
                                <span class="font-mono text-xs text-gray-500"><?php echo e($item->netsuiteItem->netsuite_id); ?></span>
                                <div class="text-xs"><?php echo e($item->netsuiteItem->netsuite_name); ?></div>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->isMapped()): ?>
                                <span class="text-xs font-mono uppercase bg-brand-soft text-brand-dark rounded-full px-2 py-0.5">Ter-mapping</span>
                            <?php else: ?>
                                <span class="text-xs font-mono uppercase bg-amber-100 text-amber-700 rounded-full px-2 py-0.5">Belum ter-mapping</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-right space-x-2">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $item)): ?>
                            <button wire:click="openEdit(<?php echo e($item->id); ?>)" class="text-brand hover:underline text-xs font-medium">Edit</button>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $item)): ?>
                            <button wire:click="delete(<?php echo e($item->id); ?>)" wire:confirm="Hapus mapping item ini?" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada data.</td></tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($items->links()); ?></div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showModal): ?>
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-base font-semibold mb-1"><?php echo e($editingId ? 'Edit Mapping Item' : 'Tambah Mapping Item Baru'); ?></h3>
            <p class="text-xs text-gray-500 mb-4 font-mono"><?php echo e($editingId ? $item_name : 'Daftarkan nama item versi distributor'); ?></p>
            <form wire:submit="save" class="space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $editingId): ?>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Distributor</label>
                    <select wire:model="distributor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Pilih Distributor —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $distributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?> (<?php echo e($d->distributor_code); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['distributor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama Item (Versi Distributor)</label>
                    <input type="text" wire:model="item_name" placeholder="Contoh: RINGER LACTATE 500 mL" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['item_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Satuan (Distributor)</label>
                    <input type="text" wire:model="satuan" placeholder="Contoh: BOTOL, BOX, PCS" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['satuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Produk Netsuite (Opsional)</label>
                    <select wire:model="netsuite_item_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Belum ter-mapping —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $netsuiteItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ns): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($ns->id); ?>"><?php echo e($ns->netsuite_id); ?> — <?php echo e($ns->netsuite_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['netsuite_item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100">Batal</button>
                    <button type="submit" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/distributor-items/index.blade.php ENDPATH**/ ?>