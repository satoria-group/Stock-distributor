<div>
    <?php echo $__env->make('partials.flash-alert', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 mb-6 shadow-xs">
        <!-- Tab Navigation -->
        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs mb-5 shadow-2xs">
            <button type="button" wire:click="$set('activeTab', 'import')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition cursor-pointer <?php echo e($activeTab === 'import' ? 'bg-[#0d6d5f] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'); ?>">
                <svg width="15" height="15" style="width: 15px; height: 15px; min-width: 15px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <span>Import File Excel (Otomatis)</span>
            </button>
            <button type="button" wire:click="$set('activeTab', 'manual')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition cursor-pointer <?php echo e($activeTab === 'manual' ? 'bg-[#0d6d5f] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'); ?>">
                <svg width="15" height="15" style="width: 15px; height: 15px; min-width: 15px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Muat Data Manual / Riwayat</span>
            </button>
        </div>

        <!-- Tab 1: Import Excel -->
        <div class="<?php echo e($activeTab === 'import' ? '' : 'hidden'); ?>"
             x-data="{ isUploading: false, progress: 0, uploadError: false }"
             x-on:livewire-upload-start="isUploading = true; uploadError = false"
             x-on:livewire-upload-finish="isUploading = false; uploadError = false"
             x-on:livewire-upload-error="isUploading = false; uploadError = true"
             x-on:livewire-upload-progress="progress = $event.detail.progress">
            <div class="space-y-4">
                <!-- Top Card: File Input & Download Action -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 p-4 rounded-xl bg-[#f8faf9] border border-[#e2e8e5]">
                    <!-- Left: Upload Input -->
                    <div class="flex-1 max-w-xl">
                        <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            <svg width="15" height="15" style="width: 15px; height: 15px; min-width: 15px; max-width: 15px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span>Pilih File Excel Template (.xlsx / .xls)</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="file" wire:model="file" accept=".xlsx,.xls"
                                   x-ref="fileInput"
                                   x-on:file-imported.window="$refs.fileInput.value = ''"
                                   wire:key="upload-file-input"
                                   class="block w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-white file:text-gray-700 hover:file:bg-gray-100 border border-gray-300 rounded-lg cursor-pointer bg-white shadow-2xs">
                            <button type="button" wire:click="importFile"
                                    :disabled="isUploading"
                                    wire:loading.attr="disabled"
                                    wire:target="file,importFile"
                                    class="inline-flex items-center gap-1.5 shrink-0 px-4 py-2 rounded-lg bg-brand hover:bg-brand-dark text-white font-medium text-xs shadow transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    style="background-color: #0d6d5f; color: #ffffff;">
                                <svg wire:loading.remove wire:target="importFile" width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <svg wire:loading wire:target="importFile" class="animate-spin" width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-show="isUploading">Mengunggah...</span>
                                <span x-show="!isUploading" wire:loading.remove wire:target="importFile">Import File</span>
                                <span wire:loading wire:target="importFile">Membaca File...</span>
                            </button>
                        </div>
                        <div x-show="uploadError" class="mt-2 text-xs text-rose-600 font-semibold flex items-center gap-1.5" style="display: none;">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Gagal mengunggah file ke server. Periksa koneksi internet atau ukuran file.</span>
                        </div>
                    </div>

                    <!-- Right: Download Template Button -->
                    <div class="shrink-0 flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-3 lg:pt-0 border-t lg:border-t-0 lg:border-l border-gray-200 lg:pl-6">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-gray-800">Format Resmi Excel</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">.XLSX</span>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5">Sudah dilengkapi Sheet Template, Daftar Kode Distributor, & Panduan.</p>
                        </div>
                        <button type="button" wire:click="downloadTemplate"
                                wire:loading.attr="disabled"
                                wire:target="downloadTemplate"
                                class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg border border-emerald-600/30 bg-white hover:bg-emerald-50 text-emerald-800 text-xs font-semibold shadow-xs hover:shadow transition cursor-pointer disabled:opacity-50"
                                title="Download template format Excel resmi (.xlsx) untuk upload stok harian">
                            <svg wire:loading.remove wire:target="downloadTemplate" class="text-emerald-700" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <svg wire:loading wire:target="downloadTemplate" class="animate-spin text-emerald-700" width="14" height="14" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="downloadTemplate">Unduh Template Excel</span>
                            <span wire:loading wire:target="downloadTemplate">Menyiapkan...</span>
                        </button>
                    </div>
                </div>

                <!-- Status Unggah Berkas -->
                <div x-show="isUploading" class="flex items-center gap-2 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg animate-pulse">
                    <svg class="animate-spin" width="14" height="14" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Sedang mengunggah berkas ke server (<span x-text="progress + '%'"></span>)... Mohon tunggu sebentar.</span>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($file && ! $errors->has('file')): ?>
                    <div class="flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg font-medium">
                        <svg width="14" height="14" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Berkas siap diproses. Klik tombol <b>Import File</b> untuk memuat data.</span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Instructions Badges -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-emerald-50/50 border border-emerald-200/60 text-[11px] text-emerald-950">
                        <div class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-700 font-bold text-xs mt-0.5">1</div>
                        <div>
                            <span class="font-bold block text-emerald-900">Sheet 'Template' (Data Utama)</span>
                            <span class="text-emerald-800">Isi baris data stok. Kolom wajib: <i>Tanggal</i>, <i>ID DISTRIBUTOR</i>, <i>Distributor Item Name</i>, & <i>Quantity</i>.</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-blue-50/50 border border-blue-200/60 text-[11px] text-blue-950">
                        <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 text-blue-700 font-bold text-xs mt-0.5">2</div>
                        <div>
                            <span class="font-bold block text-blue-900">Sheet 'Daftar Distributor'</span>
                            <span class="text-blue-800">Pastikan <code>ID DISTRIBUTOR</code> sama persis dengan kode pada sheet referensi (contoh: <code>SDLSURABAYA</code>, <code>KFTDJAKARTA1</code>).</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50/50 border border-amber-200/60 text-[11px] text-amber-950">
                        <div class="w-6 h-6 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 text-amber-700 font-bold text-xs mt-0.5">3</div>
                        <div>
                            <span class="font-bold block text-amber-900">Deteksi & Batch Otomatis</span>
                            <span class="text-amber-800">Distributor & Tanggal terdeteksi otomatis. Kolom <i>Batch No</i> & <i>ED</i> disarankan untuk pelacakan fisik gudang.</span>
                        </div>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1 font-medium"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: Muat Data Manual -->
        <div class="<?php echo e($activeTab === 'manual' ? '' : 'hidden'); ?>">
            <div class="space-y-3">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tanggal Snapshot</label>
                        <input type="date" wire:model="tanggal" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Distributor</label>
                        <select wire:model="distributorId" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs w-64 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                            <option value="">— Pilih Distributor —</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $distributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?> (<?php echo e($d->distributor_code); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>
                    <button wire:click="loadExisting" class="text-sm border border-gray-300 hover:bg-gray-50 rounded-lg px-4 py-2 font-medium cursor-pointer">
                        Muat Data
                    </button>
                </div>
                <div class="text-[11px] text-gray-500 flex items-center gap-1.5">
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; flex-shrink: 0; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Pilih tanggal & distributor untuk melihat snapshot stock tersimpan atau menambah baris secara manual.</span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['load'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($recentDates->isNotEmpty()): ?>
                    <div class="pt-2 text-xs text-gray-500 border-t border-gray-100">
                        Riwayat tanggal tersimpan untuk distributor ini:
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $recentDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button type="button" wire:click="setDateAndLoad('<?php echo e($d->toDateString()); ?>')" class="font-mono underline mr-2 text-brand hover:text-brand-dark cursor-pointer"><?php echo e($d->format('d M Y')); ?></button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($skippedItems)): ?>
            <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50/90 p-5 shadow-sm">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">
                            <?php echo e(count($skippedItems)); ?> Nama Item Tidak Dikenali di Master Distributor
                        </h4>
                        <p class="text-xs text-gray-600 mt-0.5">
                            Item berikut terdeteksi dari file Excel namun belum terdaftar di Master Mapping. Klik <b>"Ajukan Semua Item Ini ke Admin"</b> agar otomatis didaftarkan (status: <i>Belum ter-mapping</i>) dan langsung dimuat ke grid stock tanpa perlu input manual.
                        </p>
                    </div>
                </div>

                <!-- List Tabel Item yang Belum Dikenali -->
                <div class="bg-white border border-amber-200 rounded-lg overflow-hidden my-3 shadow-xs">
                    <table class="w-full text-xs">
                        <thead class="bg-[#f7f5ed] text-amber-900 font-bold uppercase text-[11px] tracking-wider border-b border-amber-200">
                            <tr>
                                <th class="text-center px-3 py-2 w-10">No</th>
                                <th class="text-left px-3 py-2">Nama Item (Dari File)</th>
                                <th class="text-left px-3 py-2 w-24">Satuan</th>
                                <th class="text-right px-3 py-2 w-24">Qty di File</th>
                                <th class="text-left px-3 py-2 w-44">ED / Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                                $displayRows = ! empty($skippedRowsData)
                                    ? $skippedRowsData
                                    : array_map(fn($n) => ['item_name' => $n, 'satuan' => 'PCS', 'quantity' => 0, 'expired_date' => null, 'batch_no' => null], $skippedItems);
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $displayRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-amber-50/50">
                                    <td class="text-center px-3 py-2 text-gray-400 font-mono"><?php echo e($idx + 1); ?></td>
                                    <td class="px-3 py-2 font-medium text-gray-900"><?php echo e($item['item_name']); ?></td>
                                    <td class="px-3 py-2 font-mono text-gray-600"><?php echo e($item['satuan'] ?? 'PCS'); ?></td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold text-gray-900"><?php echo e(number_format($item['quantity'] ?? 0, 0, ',', '.')); ?></td>
                                    <td class="px-3 py-2 font-mono text-gray-500">
                                        <?php echo e($item['expired_date'] ?? '—'); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($item['batch_no'])): ?>
                                            <span class="text-[10px] text-gray-400">(<?php echo e($item['batch_no']); ?>)</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tombol Ajukan Langsung -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <span class="text-xs text-amber-900 font-medium">
                        Total item yang akan diajukan: <b><?php echo e(count($skippedItems)); ?> item</b>
                    </span>
                    <button type="button" wire:click="submitRequestMapping" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white font-medium text-xs shadow transition cursor-pointer"
                            style="background-color: #0d6d5f; color: #ffffff;">
                        <svg wire:loading.remove wire:target="submitRequestMapping" width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px; flex-shrink: 0;" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <svg wire:loading wire:target="submitRequestMapping" class="animate-spin" width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="submitRequestMapping">
                            Ajukan Semua Item Ini ke Admin & Masukkan ke Grid (<?php echo e(count($skippedItems)); ?> Item)
                        </span>
                        <span wire:loading wire:target="submitRequestMapping">
                            Sedang Memproses & Memuat ke Grid...
                        </span>
                    </button>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Grid Card Utama -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs">
        <!-- Header Grid: Title, Realtime Status Pills, & Primary Save Action -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-gray-900">
                        Grid Stock — <?php echo e($distributorId ? $distributors->firstWhere('id', $distributorId)?->name : 'Pilih distributor dulu'); ?>

                    </h3>
                    <span class="font-medium text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                        <?php echo e($tanggal); ?>

                    </span>
                </div>
                <!-- Real-time Live Counters -->
                <div class="flex items-center gap-2 flex-wrap mt-2" wire:ignore id="grid-summary-bar">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700" id="stat-total-rows">
                        0 Baris
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700" id="stat-total-qty">
                        Total Qty: 0
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200" id="stat-mapped">
                        ✓ 0 Ter-mapping
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200" id="stat-unmapped" style="display:none;">
                        ⚠ 0 Belum Ter-mapping
                    </span>
                </div>
            </div>

            <!-- Tombol Simpan Batch -->
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="window.saveGridStock && window.saveGridStock()"
                        class="inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-semibold text-xs rounded-lg px-4 py-2.5 shadow transition cursor-pointer"
                        style="background-color: #0d6d5f; color: #ffffff;">
                    <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    <span>Simpan Batch Stock</span>
                    <span id="save-btn-count" class="text-[11px] font-bold bg-emerald-800 px-1.5 py-0.5 rounded opacity-90"></span>
                </button>
            </div>
        </div>

        <!-- Integrated Toolbar: Search & Add Row -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 py-3">
            <!-- Quick Filter / Search inside grid -->
            <div class="relative w-full md:w-72">
                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                    <svg width="14" height="14" style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="grid-search-box"
                       oninput="window.onGridFilter && window.onGridFilter(this.value)"
                       placeholder="Cari item atau batch di grid..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand">
            </div>

            <!-- Tambah Baris Manual ke Grid -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($distributorId): ?>
            <div class="flex items-center gap-2 flex-1 justify-end flex-wrap">
                <div class="flex-1 max-w-md">
                    <select id="add-item-select"
                            class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— + Pilih Item Master untuk Ditambah —</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $availableItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ai): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($ai->id); ?>"
                                    data-name="<?php echo e($ai->item_name); ?>"
                                    data-satuan="<?php echo e($ai->satuan); ?>"
                                    data-mapped="<?php echo e($ai->isMapped() ? '1' : '0'); ?>">
                                <?php echo e($ai->item_name); ?> (<?php echo e($ai->satuan); ?>) <?php echo e($ai->isMapped() ? '✓' : '⚠ Belum Ter-mapping'); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>
                <button type="button" onclick="window.addNewGridRow && window.addNewGridRow()"
                        class="inline-flex items-center gap-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium px-3 py-1.5 rounded-lg border border-gray-300 shadow-xs transition cursor-pointer shrink-0">
                    <svg width="14" height="14" style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Baris
                </button>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <!-- Panduan Visual Grid (Micro-hint) -->
        <div class="bg-[#fcfdfc] border border-emerald-100 rounded-lg px-3 py-2 text-xs text-gray-600 flex items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
                <span class="text-emerald-700 font-semibold flex items-center gap-1">
                    <svg width="14" height="14" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Panduan Edit:
                </span>
                <span>Klik langsung sel dengan tanda <b>✎ (Qty, Satuan, ED, Batch No)</b> untuk mengubah nilai. Tekan <b>Tab</b> untuk pindah sel. Klik <b>🗑️</b> di kolom Aksi untuk menghapus baris.</span>
            </div>
            <span class="text-[11px] font-mono text-gray-400 shrink-0 hidden sm:inline">Tekan Enter saat selesai edit</span>
        </div>

        <!-- AG Grid Container -->
        <div wire:ignore>
            <div id="stock-grid" class="ag-theme-quartz rounded-lg overflow-hidden border border-gray-200 shadow-xs" style="height: 500px; width: 100%;"></div>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showSuccessModal): ?>
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-in fade-in duration-200" wire:click.self="$set('showSuccessModal', false)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-center border border-[#e7e9e3]">
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg width="28" height="28" style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h3 class="text-base font-bold text-gray-900 mb-1">Batch Stock Berhasil Disimpan!</h3>
            <p class="text-xs text-gray-500 mb-5">Snapshot posisi stock telah berhasil dicatat ke database.</p>

            <div class="bg-[#f8faf8] border border-[#e7e9e3] rounded-xl p-4 text-left space-y-2.5 text-xs mb-6">
                <div class="flex justify-between items-start gap-2">
                    <span class="text-gray-500 shrink-0">Distributor:</span>
                    <span class="font-semibold text-gray-800 text-right truncate max-w-[220px]"><?php echo e($saveSummary['distributor_name'] ?? '—'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tanggal Snapshot:</span>
                    <span class="font-mono font-medium text-gray-800"><?php echo e(isset($saveSummary['tanggal']) ? \Illuminate\Support\Carbon::parse($saveSummary['tanggal'])->format('d M Y') : '—'); ?></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                    <span class="text-gray-500 font-medium">Total Item Tersimpan:</span>
                    <span class="font-bold text-sm text-gray-900"><?php echo e($saveSummary['total'] ?? 0); ?> item</span>
                </div>
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono bg-emerald-100 text-emerald-800">
                        ✓ <?php echo e($saveSummary['mapped'] ?? 0); ?> Ter-mapping
                    </span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($saveSummary['unmapped'] ?? 0) > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono bg-amber-100 text-amber-800">
                        ⚠ <?php echo e($saveSummary['unmapped'] ?? 0); ?> Belum ter-mapping
                    </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($saveSummary['deleted'] ?? 0) > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono bg-rose-100 text-rose-800">
                        🗑 <?php echo e($saveSummary['deleted']); ?> Baris dihapus
                    </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="flex gap-2.5">
                <button type="button" wire:click="$set('showSuccessModal', false)"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
                    Tetap di Halaman Ini
                </button>
                <a href="<?php echo e(route('dashboard')); ?>"
                   class="flex-1 px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-xs font-medium transition text-center inline-flex items-center justify-center gap-1.5 shadow-sm">
                    Lihat Dashboard
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <style>
        /* Indikasi jelas sel editable pada AG Grid dengan Font Plus Jakarta Sans */
        .ag-theme-quartz {
            --ag-font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            --ag-font-size: 12px;
        }
        .ag-theme-quartz .ag-root-wrapper,
        .ag-theme-quartz .ag-cell {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        .ag-theme-quartz .editable-cell {
            cursor: text !important;
            transition: background-color 0.15s ease, outline 0.15s ease;
        }
        .ag-theme-quartz .editable-cell:hover {
            background-color: #f0fdf4 !important; /* Soft mint highlight */
            outline: 1.5px dashed #0d6d5f !important;
            outline-offset: -2px;
        }
        .ag-theme-quartz .ag-cell-focus.editable-cell {
            outline: 2px solid #0d6d5f !important;
            outline-offset: -2px;
            background-color: #ffffff !important;
        }
        .ag-theme-quartz .ag-header-cell-label {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .ag-theme-quartz .ag-row:hover {
            background-color: #fcfdfc;
        }
        .ag-theme-quartz .delete-btn:hover svg {
            transform: scale(1.18);
        }
    </style>

        <?php
        $__scriptKey = '3880225432-0';
        ob_start();
    ?>
    <script>
        let gridApi = null;

        function statusRenderer(params) {
            if (params.value) {
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase bg-[#dcece7] text-[#07352d] tracking-wider">Ter-mapping</span>`;
            }
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase bg-[#fef3c7] text-[#92400e] tracking-wider">Belum ter-mapping</span>`;
        }

        function actionRenderer(params) {
            if (!params.data || !params.data.distributor_item_id) return '';
            const itemId = params.data.distributor_item_id;
            return `
                <div class="flex items-center justify-center h-full">
                    <button type="button"
                            onclick="window.deleteGridRow(${itemId})"
                            title="Hapus baris ini dari grid"
                            class="delete-btn inline-flex items-center justify-center text-red-500 hover:text-red-700 hover:bg-red-50 p-1.5 rounded transition cursor-pointer">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:15px;height:15px;flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            `;
        }

        function updateGridSummary() {
            if (!gridApi) return;
            let totalRows = 0;
            let totalQty = 0;
            let mappedCount = 0;
            let unmappedCount = 0;

            gridApi.forEachNode(node => {
                if (node.data) {
                    totalRows++;
                    totalQty += Number(node.data.quantity) || 0;
                    if (node.data.mapped) {
                        mappedCount++;
                    } else {
                        unmappedCount++;
                    }
                }
            });

            const elRows = document.getElementById('stat-total-rows');
            const elQty = document.getElementById('stat-total-qty');
            const elMapped = document.getElementById('stat-mapped');
            const elUnmapped = document.getElementById('stat-unmapped');
            const elSaveCount = document.getElementById('save-btn-count');

            if (elRows) elRows.textContent = `${totalRows} Baris`;
            if (elQty) elQty.textContent = `Total Qty: ${totalQty.toLocaleString('id-ID')}`;
            if (elMapped) elMapped.textContent = `✓ ${mappedCount} Ter-mapping`;
            if (elUnmapped) {
                elUnmapped.textContent = `⚠ ${unmappedCount} Belum Ter-mapping`;
                elUnmapped.style.display = unmappedCount > 0 ? 'inline-flex' : 'none';
            }
            if (elSaveCount) {
                elSaveCount.textContent = totalRows > 0 ? `(${totalRows})` : '';
            }
        }

        function initGrid(rows) {
            const gridEl = document.getElementById('stock-grid');
            if (!gridEl) return;

            const columnDefs = [
                {
                    headerName: 'No',
                    valueGetter: 'node.rowIndex + 1',
                    width: 60,
                    minWidth: 50,
                    maxWidth: 70,
                    pinned: 'left',
                    editable: false,
                    sortable: false,
                    filter: false,
                    cellClass: 'text-center font-medium text-slate-400 text-xs'
                },
                {
                    field: 'item_name',
                    headerName: 'Item (Distributor)',
                    editable: false,
                    flex: 2,
                    minWidth: 240,
                    cellClass: 'font-semibold text-slate-900'
                },
                {
                    field: 'quantity',
                    headerName: 'Qty ✎',
                    editable: true,
                    type: 'numericColumn',
                    width: 120,
                    cellClass: 'editable-cell text-right font-bold text-slate-900',
                    valueFormatter: p => (p.value != null ? Number(p.value).toLocaleString('id-ID') : '0'),
                    valueParser: p => Number(p.newValue) || 0
                },
                {
                    field: 'satuan',
                    headerName: 'Satuan ✎',
                    editable: true,
                    width: 105,
                    cellClass: 'editable-cell font-medium text-slate-700',
                    valueParser: p => (p.newValue ? String(p.newValue).toUpperCase() : '')
                },
                {
                    field: 'expired_date',
                    headerName: 'ED (YYYY-MM-DD) ✎',
                    editable: true,
                    width: 160,
                    cellClass: 'editable-cell font-medium text-slate-700'
                },
                {
                    field: 'batch_no',
                    headerName: 'Batch No ✎',
                    editable: true,
                    width: 140,
                    cellClass: 'editable-cell font-medium text-slate-700'
                },
                {
                    field: 'mapped',
                    headerName: 'Status Mapping',
                    editable: false,
                    width: 160,
                    cellRenderer: statusRenderer
                },
                {
                    headerName: 'Aksi',
                    width: 75,
                    minWidth: 65,
                    maxWidth: 85,
                    pinned: 'right',
                    editable: false,
                    sortable: false,
                    filter: false,
                    cellRenderer: actionRenderer
                },
                { field: 'distributor_item_id', hide: true },
            ];

            if (!gridApi && window.agGridCreateGrid) {
                gridApi = window.agGridCreateGrid(gridEl, {
                    columnDefs,
                    rowData: rows || [],
                    defaultColDef: { resizable: true, sortable: true },
                    singleClickEdit: true,
                    stopEditingWhenCellsLoseFocus: true,
                    onCellValueChanged: function(params) {
                        updateGridSummary();
                    },
                    overlayNoRowsTemplate: '<div class="p-8 text-center text-gray-400 text-sm"><div class="text-2xl mb-1">📋</div>Belum ada baris data. Silakan import file Excel atau pilih distributor & muat data.</div>',
                });
                updateGridSummary();
            } else if (gridApi) {
                applyRowData(rows);
            }
        }

        function applyRowData(rows) {
            if (!gridApi) return;
            const data = Array.isArray(rows) ? rows : [];
            if (typeof gridApi.setGridOption === 'function') {
                gridApi.setGridOption('rowData', data);
            } else if (typeof gridApi.setRowData === 'function') {
                gridApi.setRowData(data);
            }
            updateGridSummary();
        }

        window.onGridFilter = function(val) {
            if (!gridApi) return;
            if (typeof gridApi.setGridOption === 'function') {
                gridApi.setGridOption('quickFilterText', val);
            } else if (typeof gridApi.setQuickFilter === 'function') {
                gridApi.setQuickFilter(val);
            }
        };

        window.addNewGridRow = function() {
            if (!gridApi) return;
            const selectEl = document.getElementById('add-item-select');
            if (!selectEl || !selectEl.value) {
                alert('Silakan pilih item terlebih dahulu dari dropdown.');
                return;
            }

            const itemId = Number(selectEl.value);
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            const itemName = selectedOpt.getAttribute('data-name');
            const satuan = selectedOpt.getAttribute('data-satuan') || 'PCS';
            const isMapped = selectedOpt.getAttribute('data-mapped') === '1';

            // Check if already in grid
            let exists = false;
            gridApi.forEachNode(node => {
                if (node.data && Number(node.data.distributor_item_id) === itemId) {
                    exists = true;
                }
            });

            if (exists) {
                alert('Item ini sudah ada di dalam grid.');
                return;
            }

            const newRow = {
                distributor_item_id: itemId,
                item_name: itemName,
                satuan: satuan,
                quantity: 0,
                expired_date: null,
                batch_no: null,
                mapped: isMapped
            };

            if (typeof gridApi.applyTransaction === 'function') {
                gridApi.applyTransaction({ add: [newRow] });
            }
            updateGridSummary();

            // Sync with Livewire so rows property is up to date & availableItems dropdown is refreshed
            const currentRows = [];
            gridApi.forEachNode(node => currentRows.push(node.data));
            $wire.set('rows', currentRows);

            selectEl.value = '';
        };

        window.deleteGridRow = function(distributorItemId) {
            if (!gridApi) return;
            let rowToDelete = null;
            gridApi.forEachNode(node => {
                if (node.data && Number(node.data.distributor_item_id) === Number(distributorItemId)) {
                    rowToDelete = node.data;
                }
            });

            if (rowToDelete) {
                if (typeof gridApi.applyTransaction === 'function') {
                    gridApi.applyTransaction({ remove: [rowToDelete] });
                }
                updateGridSummary();

                // Catat penghapusan di server. saveRows() memakai daftar ini
                // untuk benar-benar menghapus StockEntry-nya; tanpa itu baris
                // hanya hilang dari grid lalu muncul lagi setelah disimpan.
                // markRowRemoved juga membuang baris dari $rows sehingga
                // dropdown "tambah item" kembali memunculkannya.
                $wire.call('markRowRemoved', Number(distributorItemId));
            }
        };

        function extractRows(event) {
            if (!event) return null;
            if (Array.isArray(event)) {
                if (event.length > 0 && event[0]?.rows && Array.isArray(event[0].rows)) {
                    return event[0].rows;
                }
                if (event.length > 0 && typeof event[0] === 'object' && event[0]?.distributor_item_id !== undefined) {
                    return event;
                }
            }
            if (Array.isArray(event.rows)) return event.rows;
            if (event.detail && Array.isArray(event.detail.rows)) return event.detail.rows;
            if (Array.isArray(event.detail)) return event.detail;
            return null;
        }

        function handleRowsLoaded(payload) {
            const rows = extractRows(payload) ?? $wire.get('rows') ?? [];
            applyRowData(rows);
        }

        initGrid(<?php echo \Illuminate\Support\Js::from($rows)->toHtml() ?>);

        $wire.on('rows-loaded', (event) => {
            handleRowsLoaded(event);
        });

        window.addEventListener('rows-loaded', (event) => {
            handleRowsLoaded(event);
        });

        // CATATAN: jangan menambahkan hook 'morph.updated' yang mendorong
        // $wire.get('rows') ke dalam grid.
        //
        // Editan sel tidak pernah dikirim ke server (onCellValueChanged hanya
        // memperbarui ringkasan), sehingga $rows di server selalu tertinggal.
        // Mendorongnya kembali ke grid pada setiap morph akan menimpa editan
        // yang belum disimpan — cukup dengan berpindah tab Import/Manual.
        //
        // Hook semacam itu juga tidak diperlukan: container grid sudah dibungkus
        // wire:ignore sehingga Livewire tidak pernah menyentuh DOM-nya, dan
        // seluruh jalur sah server->grid sudah memakai event 'rows-loaded'.

        window.saveGridStock = function () {
            if (!gridApi) return;
            const rows = [];
            gridApi.forEachNode(node => rows.push(node.data));
            if (rows.length === 0) {
                alert('Tidak ada data di dalam grid untuk disimpan.');
                return;
            }
            $wire.call('saveRows', rows);
        };
    </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
</div>
<?php /**PATH C:\Users\Najmi\Documents\satoria\Stock-distributor\resources\views/livewire/stock/upload.blade.php ENDPATH**/ ?>