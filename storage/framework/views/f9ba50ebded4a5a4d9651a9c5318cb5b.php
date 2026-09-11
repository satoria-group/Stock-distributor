<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
    <div x-data="{ show: true }" x-show="show" class="mb-5 rounded-2xl border px-4 py-3.5 flex items-center justify-between gap-3 text-xs font-semibold shadow-2xs bg-rose-50 text-rose-800 border-rose-200 transition">
        <div class="flex items-center gap-3">
            <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span><?php echo e(session('error')); ?></span>
        </div>
        <button type="button" @click="show = false" class="text-rose-400 hover:text-rose-700 cursor-pointer p-1 rounded-lg hover:bg-rose-100/60 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status') || session('success')): ?>
    <?php
        $statusMsg = session('status') ?? session('success');
        $isError = str_starts_with($statusMsg, 'Tidak bisa') || str_contains($statusMsg, 'gagal');
    ?>
    <div x-data="{ show: true }" x-show="show" class="mb-5 rounded-2xl border px-4 py-3.5 flex items-center justify-between gap-3 text-xs font-semibold shadow-2xs transition <?php echo e($isError ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'); ?>">
        <div class="flex items-center gap-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isError): ?>
                <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            <?php else: ?>
                <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0;" class="text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <span><?php echo e($statusMsg); ?></span>
        </div>
        <button type="button" @click="show = false" class="<?php echo e($isError ? 'text-rose-400 hover:text-rose-700 hover:bg-rose-100/60' : 'text-emerald-400 hover:text-emerald-700 hover:bg-emerald-100/60'); ?> cursor-pointer p-1 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/partials/flash-alert.blade.php ENDPATH**/ ?>