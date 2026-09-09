<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title ?? 'Dashboard'); ?> — <?php echo e(config('app.name')); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="bg-[#f6f5f1] text-[#17211f] font-sans antialiased">
    <div class="min-h-screen flex">
        <aside class="w-64 shrink-0 bg-white border-r border-[#e7e9e3] flex flex-col">
            <div class="px-5 py-5 border-b border-[#e7e9e3]">
                <div class="text-[11px] font-mono uppercase tracking-wider text-brand">Satoria Group</div>
                <div class="text-sm font-semibold mt-0.5">Stock Distributor</div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <a href="<?php echo e(route('dashboard')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('dashboard') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Dashboard
                </a>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', \App\Models\Distributor::class)): ?>
                <a href="<?php echo e(route('distributors.index')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('distributors.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Master Distributor
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', \App\Models\NetsuiteItem::class)): ?>
                <a href="<?php echo e(route('netsuite-items.index')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('netsuite-items.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Master Produk (Netsuite)
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', \App\Models\DistributorItem::class)): ?>
                <a href="<?php echo e(route('distributor-items.index')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('distributor-items.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Mapping Item Distributor
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\StockEntry::class)): ?>
                <a href="<?php echo e(route('stock.upload')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('stock.upload') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Upload Stock Harian
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', \App\Models\StockEntry::class)): ?>
                <a href="<?php echo e(route('stock.history')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('stock.history') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Riwayat Stok
                </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.manage')): ?>
                <a href="<?php echo e(route('users.index')); ?>"
                   class="block px-3 py-2 rounded-lg <?php echo e(request()->routeIs('users.*') ? 'bg-brand-soft text-brand-dark font-medium' : 'text-gray-600 hover:bg-gray-100'); ?>">
                    Manajemen User
                </a>
                <?php endif; ?>
            </nav>

            <div class="px-4 py-4 border-t border-[#e7e9e3]">
                <div class="text-sm font-medium truncate"><?php echo e(auth()->user()->name); ?></div>
                <div class="text-xs text-gray-500 truncate"><?php echo e(auth()->user()->getRoleNames()->first() ?? '—'); ?></div>
                <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-2">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-xs font-mono uppercase tracking-wide text-red-600 hover:underline">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            <header class="bg-white border-b border-[#e7e9e3] px-8 py-4">
                <h1 class="text-lg font-semibold"><?php echo e($title ?? 'Dashboard'); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($subtitle)): ?>
                    <p class="text-sm text-gray-500 mt-0.5"><?php echo e($subtitle); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </header>

            <main class="p-8">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
                    <div class="mb-4 rounded-lg bg-brand-soft text-brand-dark text-sm px-4 py-3">
                        <?php echo e(session('status')); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php echo e($slot); ?>

            </main>
        </div>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/layouts/app.blade.php ENDPATH**/ ?>