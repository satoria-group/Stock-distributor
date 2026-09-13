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

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('datePicker', createDatePicker);
    }
});



