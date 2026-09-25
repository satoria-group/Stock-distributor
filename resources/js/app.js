import './bootstrap';

import { createGrid, ModuleRegistry, ClientSideRowModelModule, CommunityFeaturesModule } from 'ag-grid-community';
import Chart from 'chart.js/auto';

import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

ModuleRegistry.registerModules([ClientSideRowModelModule, CommunityFeaturesModule]);

// Exposed on window so Blade views can use them from plain <script> tags
// inside Livewire components (wire:ignore islands) without needing their
// own bundle entrypoints.
window.agGridCreateGrid = createGrid;
window.Chart = Chart;
window.flatpickr = flatpickr;
window.flatpickrIndonesian = Indonesian;

function createDatePicker(config) {
    return {
        value: config.value || '',
        fp: null,
        init() {
            const self = this;
            const checkFlatpickr = () => {
                if (typeof window.flatpickr === 'function') {
                    const el = self.$refs.inputEl;
                    self.fp = window.flatpickr(el, {
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: (config.inputClass || '') + ' pr-8 cursor-pointer font-medium tracking-wide placeholder:text-slate-400 focus:bg-white',
                        allowInput: true,
                        defaultDate: self.value || null,
                        locale: window.flatpickrIndonesian || 'default',
                        onReady: (selectedDates, dateStr, instance) => {
                            if (!instance.calendarContainer.querySelector('.flatpickr-footer-actions')) {
                                const footer = document.createElement('div');
                                footer.className = 'flatpickr-footer-actions';

                                const btnClear = document.createElement('button');
                                btnClear.type = 'button';
                                btnClear.className = 'flatpickr-action-btn flatpickr-btn-clear';
                                btnClear.textContent = 'Hapus';
                                btnClear.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    instance.clear();
                                    self.value = '';
                                    instance.close();
                                });

                                const btnToday = document.createElement('button');
                                btnToday.type = 'button';
                                btnToday.className = 'flatpickr-action-btn flatpickr-btn-today';
                                btnToday.textContent = 'Hari Ini';
                                btnToday.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    instance.setDate(new Date(), true);
                                    instance.close();
                                });

                                footer.appendChild(btnClear);
                                footer.appendChild(btnToday);
                                instance.calendarContainer.appendChild(footer);
                            }
                        },
                        onChange: (selectedDates, dateStr) => {
                            self.value = dateStr || '';
                        }
                    });

                    self.$watch('value', (newVal) => {
                        if (self.fp && newVal !== self.fp.input.value) {
                            self.fp.setDate(newVal || '', false);
                        }
                    });
                } else {
                    setTimeout(checkFlatpickr, 50);
                }
            };
            checkFlatpickr();
        }
    };
}

window.datePicker = createDatePicker;

/*
 * Dialog aplikasi: pengganti alert() / confirm() bawaan browser.
 * Markup-nya ada di resources/views/partials/app-dialog.blade.php.
 *
 *   await appConfirm({ title, message, variant: 'danger'|'warning'|'info', confirmText, cancelText })  -> true/false
 *   await appAlert({ title, message, variant: 'info'|'success'|'error' })
 */
const dialogState = {
    open: false,
    mode: 'confirm', // 'confirm' | 'alert'
    variant: 'warning',
    title: '',
    message: '',
    confirmText: 'Ya, lanjutkan',
    cancelText: 'Batal',
    _resolve: null,

    show(mode, opts) {
        // Dialog lama yang masih terbuka dianggap dibatalkan.
        this._resolve?.(false);
        Object.assign(this, {
            mode,
            variant: opts.variant ?? (mode === 'alert' ? 'info' : 'warning'),
            title: opts.title ?? (mode === 'alert' ? 'Informasi' : 'Konfirmasi'),
            message: opts.message ?? '',
            confirmText: opts.confirmText ?? (mode === 'alert' ? 'Mengerti' : 'Ya, lanjutkan'),
            cancelText: opts.cancelText ?? 'Batal',
            open: true,
        });

        return new Promise((resolve) => { this._resolve = resolve; });
    },

    close(result) {
        this.open = false;
        const resolve = this._resolve;
        this._resolve = null;
        resolve?.(result);
    },
};

const toOpts = (o) => (typeof o === 'string' ? { message: o } : (o || {}));

window.appConfirm = (opts) => window.Alpine.store('dialog').show('confirm', toOpts(opts));
window.appAlert = (opts) => window.Alpine.store('dialog').show('alert', toOpts(opts));

/*
 * Toast: notifikasi singkat di pojok kanan atas yang hilang sendiri.
 * Markup: resources/views/partials/app-toast.blade.php.
 *
 *   appToast({ title, message, variant: 'success'|'warning'|'error'|'info', duration })
 *   Livewire (PHP): $this->dispatch('toast', title: '...', message: '...', variant: 'success');
 */
const toastState = {
    items: [],
    _id: 0,

    push(opts) {
        const id = ++this._id;
        const item = {
            id,
            variant: opts.variant || 'success',
            title: opts.title || '',
            message: opts.message || '',
        };
        this.items.push(item);
        // Tetap maksimal 4 toast di layar.
        if (this.items.length > 4) this.items.shift();
        setTimeout(() => this.dismiss(id), opts.duration ?? 6000);
    },

    dismiss(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
};

window.appToast = (opts) => window.Alpine.store('toast').push(toOpts(opts));

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('datePicker', createDatePicker);
        window.Alpine.store('dialog', dialogState);
        window.Alpine.store('toast', toastState);
    }
});

/*
 * wire:confirm memakai dialog aplikasi, bukan confirm() bawaan browser.
 *
 * Opsional per tombol:
 *   data-confirm-title="Hapus produk?"  data-confirm-button="Ya, hapus"
 *   data-confirm-variant="danger|warning|info"
 * Tanpa atribut itu, tampilan dipilih dari kata pertama pesan (CONFIRM_PRESETS).
 */
// Kata pertama pesan wire:confirm -> tampilan dialognya.
const CONFIRM_PRESETS = {
    hapus: { title: 'Hapus data?', variant: 'danger', button: 'Ya, hapus' },
    nonaktifkan: { title: 'Nonaktifkan?', variant: 'warning', button: 'Ya, nonaktifkan' },
    aktifkan: { title: 'Aktifkan?', variant: 'info', button: 'Ya, aktifkan' },
    lewati: { title: 'Lewati tanpa menyimpan?', variant: 'warning', button: 'Ya, lewati' },
    pindah: { title: 'Pindah cabang?', variant: 'warning', button: 'Ya, pindah' },
    kembali: { title: 'Kembali ke cabang sebelumnya?', variant: 'warning', button: 'Ya, kembali' },
    buat: { title: 'Buat grup?', variant: 'info', button: 'Ya, buat' },
    default: { title: 'Konfirmasi', variant: 'warning', button: 'Ya, lanjutkan' },
};

document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (payload) => {
        window.appToast(Array.isArray(payload) ? payload[0] : payload);
    });

    // Livewire mengabaikan Livewire.directive() untuk nama yang sudah ada,
    // jadi 'confirm' tidak bisa didaftarkan ulang. Hook ini berjalan SETELAH
    // handler bawaan dan menimpa fungsi konfirmasinya pada elemen yang sama.
    window.Livewire.hook('directive.init', ({ el, directive }) => {
        if (directive.value !== 'confirm' || directive.modifiers.includes('prompt')) return;

        let message = directive.expression.replaceAll('\\n', '\n') || 'Apakah Anda yakin?';

        el.__livewire_confirm = (action, instead) => {
            // Klik asli dihentikan sekarang; aksinya dijalankan nanti bila dikonfirmasi.
            instead();

            const verb = (message.match(/^\s*(\w+)/) || [])[1]?.toLowerCase();
            const preset = CONFIRM_PRESETS[verb] || CONFIRM_PRESETS.default;

            window.appConfirm({
                title: el.dataset.confirmTitle || preset.title,
                message,
                variant: el.dataset.confirmVariant || preset.variant,
                confirmText: el.dataset.confirmButton || preset.button,
            }).then((ok) => ok && action());
        };
    });
});



