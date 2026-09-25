{{--
    Dialog aplikasi (pengganti alert/confirm bawaan browser).
    Dipanggil lewat window.appConfirm() / window.appAlert() atau wire:confirm — lihat resources/js/app.js.
--}}
<div x-data x-cloak
     x-show="$store.dialog.open"
     x-on:keydown.escape.window="$store.dialog.open && $store.dialog.close(false)"
     class="fixed inset-0 z-[100] flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="app-dialog-title">

    <div x-show="$store.dialog.open"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs"
         @click="$store.dialog.close(false)"></div>

    <div x-show="$store.dialog.open"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         x-trap.noscroll="$store.dialog.open"
         class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 border border-slate-200 text-center">

        {{-- Ikon per jenis --}}
        <div class="w-12 h-12 rounded-2xl mx-auto mb-4 flex items-center justify-center"
             :class="{
                'bg-rose-100 text-rose-600': ['danger', 'error'].includes($store.dialog.variant),
                'bg-amber-100 text-amber-600': $store.dialog.variant === 'warning',
                'bg-emerald-100 text-emerald-600': $store.dialog.variant === 'success',
                'bg-teal-50 text-[#0d6d5f]': $store.dialog.variant === 'info',
             }">
            {{-- danger: tempat sampah --}}
            <svg x-show="$store.dialog.variant === 'danger'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            {{-- warning / error: segitiga --}}
            <svg x-show="['warning', 'error'].includes($store.dialog.variant)" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{-- success: centang --}}
            <svg x-show="$store.dialog.variant === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            {{-- info: i --}}
            <svg x-show="$store.dialog.variant === 'info'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>

        <h3 id="app-dialog-title" class="text-base font-bold text-slate-900" x-text="$store.dialog.title"></h3>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed whitespace-pre-line" x-text="$store.dialog.message"></p>

        <div class="flex gap-2 mt-6">
            <button type="button" x-show="$store.dialog.mode === 'confirm'"
                    @click="$store.dialog.close(false)"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                    x-text="$store.dialog.cancelText"></button>
            <button type="button" x-ref="okBtn"
                    x-effect="$store.dialog.open && $nextTick(() => $refs.okBtn.focus())"
                    @click="$store.dialog.close(true)"
                    class="flex-1 px-4 py-2.5 rounded-xl text-xs font-bold text-white shadow-2xs transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="{
                        'bg-rose-600 hover:bg-rose-700 focus:ring-rose-300': ['danger', 'error'].includes($store.dialog.variant),
                        'bg-amber-600 hover:bg-amber-700 focus:ring-amber-300': $store.dialog.variant === 'warning',
                        'bg-[#0d6d5f] hover:bg-[#07352d] focus:ring-teal-300': ['info', 'success'].includes($store.dialog.variant),
                    }"
                    x-text="$store.dialog.confirmText"></button>
        </div>
    </div>
</div>
