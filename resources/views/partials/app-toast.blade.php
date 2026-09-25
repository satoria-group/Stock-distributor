{{-- Toast global — dipanggil lewat window.appToast() atau $this->dispatch('toast', ...). Lihat resources/js/app.js. --}}
<div x-data x-cloak
     class="fixed top-5 right-5 z-[90] flex flex-col gap-2.5 w-[calc(100vw-2.5rem)] max-w-sm pointer-events-none"
     aria-live="polite">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="pointer-events-auto bg-white border rounded-2xl shadow-xl p-4 flex items-start gap-3"
             :class="{
                'border-emerald-200': t.variant === 'success',
                'border-amber-200': t.variant === 'warning',
                'border-rose-200': t.variant === 'error',
                'border-slate-200': t.variant === 'info',
             }">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 :class="{
                    'bg-emerald-100 text-emerald-600': t.variant === 'success',
                    'bg-amber-100 text-amber-600': t.variant === 'warning',
                    'bg-rose-100 text-rose-600': t.variant === 'error',
                    'bg-teal-50 text-[#0d6d5f]': t.variant === 'info',
                 }">
                <svg x-show="t.variant === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="['warning', 'error'].includes(t.variant)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <svg x-show="t.variant === 'info'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-slate-900 truncate" x-text="t.title" :title="t.title"></div>
                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed whitespace-pre-line" x-text="t.message"></div>
            </div>
            <button type="button" @click="$store.toast.dismiss(t.id)"
                    class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer shrink-0" title="Tutup">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
