<div>
    <div class="flex items-center justify-between gap-4 mb-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama produk..."
               class="w-80 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\NetsuiteItem::class)): ?>
        <button wire:click="openCreate" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2">
            + Tambah Produk
        </button>
        <?php endif; ?>
    </div>

    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-500 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Netsuite ID</th>
                    <th class="text-left px-4 py-2.5">Nama Produk</th>
                    <th class="text-left px-4 py-2.5">Satuan Default</th>
                    <th class="text-right px-4 py-2.5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono text-xs"><?php echo e($item->netsuite_id); ?></td>
                        <td class="px-4 py-2.5"><?php echo e($item->netsuite_name); ?></td>
                        <td class="px-4 py-2.5 text-gray-500"><?php echo e($item->default_satuan ?: '—'); ?></td>
                        <td class="px-4 py-2.5 text-right space-x-3">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $item)): ?>
                            <button wire:click="openEdit(<?php echo e($item->id); ?>)" class="text-brand hover:underline text-xs font-medium">Edit</button>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $item)): ?>
                            <button wire:click="delete(<?php echo e($item->id); ?>)" wire:confirm="Hapus produk <?php echo e($item->netsuite_name); ?>?" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada produk.</td></tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($items->links()); ?></div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showModal): ?>
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-base font-semibold mb-4"><?php echo e($editingId ? 'Edit Produk' : 'Tambah Produk'); ?></h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Netsuite ID</label>
                    <input type="text" wire:model="netsuite_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['netsuite_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama Produk</label>
                    <input type="text" wire:model="netsuite_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['netsuite_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Satuan Default</label>
                    <input type="text" wire:model="default_satuan" placeholder="mis. BOTOL" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
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
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/netsuite-items/index.blade.php ENDPATH**/ ?>