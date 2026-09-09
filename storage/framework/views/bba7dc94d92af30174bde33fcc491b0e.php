<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasPages()): ?>
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            <span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->onFirstPage()): ?>
                    <span class="relative inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-400 bg-white border border-slate-200 cursor-default leading-5 rounded-xl">
                        <?php echo __('pagination.previous'); ?>

                    </span>
                <?php else: ?>
                    <button type="button" wire:click="previousPage('<?php echo e($paginator->getPageName()); ?>')" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 leading-5 rounded-xl hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
                        <?php echo __('pagination.previous'); ?>

                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </span>

            <span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasMorePages()): ?>
                    <button type="button" wire:click="nextPage('<?php echo e($paginator->getPageName()); ?>')" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-semibold text-slate-700 bg-white border border-slate-200 leading-5 rounded-xl hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
                        <?php echo __('pagination.next'); ?>

                    </button>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-semibold text-slate-400 bg-white border border-slate-200 cursor-default leading-5 rounded-xl">
                        <?php echo __('pagination.next'); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </span>
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium">
                    <span><?php echo __('Menampilkan'); ?></span>
                    <span class="font-bold text-slate-700"><?php echo e($paginator->firstItem() ?? 0); ?></span>
                    <span><?php echo __('sampai'); ?></span>
                    <span class="font-bold text-slate-700"><?php echo e($paginator->lastItem() ?? 0); ?></span>
                    <span><?php echo __('dari'); ?></span>
                    <span class="font-bold text-slate-700"><?php echo e($paginator->total()); ?></span>
                    <span><?php echo __('data'); ?></span>
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-xl shadow-2xs -space-x-px">
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->onFirstPage()): ?>
                        <span aria-disabled="true" aria-label="<?php echo e(__('pagination.previous')); ?>">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-300 bg-white border border-slate-200 cursor-default rounded-l-xl leading-5" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    <?php else: ?>
                        <button type="button" wire:click="previousPage('<?php echo e($paginator->getPageName()); ?>')" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-l-xl leading-5 hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none transition cursor-pointer" aria-label="<?php echo e(__('pagination.previous')); ?>">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_string($element)): ?>
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-400 bg-white border border-slate-200 cursor-default leading-5"><?php echo e($element); ?></span>
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_array($element)): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span wire:key="paginator-<?php echo e($paginator->getPageName()); ?>-page<?php echo e($page); ?>">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($page == $paginator->currentPage()): ?>
                                        <span aria-current="page">
                                            <span class="relative inline-flex items-center px-3.5 py-2 text-xs font-bold text-white border border-[#0d6d5f] cursor-default leading-5" style="background-color: #0d6d5f;"><?php echo e($page); ?></span>
                                        </span>
                                    <?php else: ?>
                                        <button type="button" wire:click="gotoPage(<?php echo e($page); ?>, '<?php echo e($paginator->getPageName()); ?>')" class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 leading-5 hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none transition cursor-pointer" aria-label="<?php echo e(__('Go to page :page', ['page' => $page])); ?>">
                                            <?php echo e($page); ?>

                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasMorePages()): ?>
                        <button type="button" wire:click="nextPage('<?php echo e($paginator->getPageName()); ?>')" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-r-xl leading-5 hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none transition cursor-pointer" aria-label="<?php echo e(__('pagination.next')); ?>">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    <?php else: ?>
                        <span aria-disabled="true" aria-label="<?php echo e(__('pagination.next')); ?>">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-300 bg-white border border-slate-200 cursor-default rounded-r-xl leading-5" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </span>
            </div>
        </div>
    </nav>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/vendor/livewire/tailwind.blade.php ENDPATH**/ ?>