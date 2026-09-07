<div>
    <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 mb-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Tanggal</label>
                <input type="date" wire:model="tanggal" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Distributor</label>
                <select wire:model="distributorId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-brand">
                    <option value="">— Pilih Distributor —</option>
                    @foreach ($distributors as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->distributor_code }})</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="loadExisting" class="text-sm border border-gray-300 hover:bg-gray-50 rounded-lg px-4 py-2">
                Muat Data
            </button>

            <div class="flex-1"></div>

            <div class="flex items-end gap-2">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Import Template.xlsx</label>
                    <input type="file" wire:model="file" accept=".xlsx,.xls" class="text-sm">
                </div>
                <button wire:click="importFile" wire:loading.attr="disabled" class="text-sm border border-gray-300 hover:bg-gray-50 rounded-lg px-4 py-2">
                    Import
                </button>
            </div>
        </div>
        @error('file') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
        @error('load') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror

        @if (! empty($skippedItems))
            <div class="mt-3 rounded-lg bg-amber-50 text-amber-800 text-xs px-4 py-3">
                <b>{{ count($skippedItems) }} nama item dari file tidak dikenali</b> (belum ada di Master Mapping distributor ini, minta Admin menambahkan):
                {{ implode(', ', array_slice($skippedItems, 0, 8)) }}{{ count($skippedItems) > 8 ? '…' : '' }}
            </div>
        @endif
    </div>

    @if ($distributorId)
    <div class="bg-white border border-[#e7e9e3] rounded-xl p-5 mb-5">
        <div class="flex items-end gap-2">
            <div class="flex-1">
                <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">+ Tambah Baris (item yang sudah ada di Master Mapping)</label>
                <select wire:model="addItemId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    <option value="">— Pilih item —</option>
                    @foreach ($availableItems as $ai)
                        <option value="{{ $ai->id }}">{{ $ai->item_name }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="addRow" class="text-sm border border-gray-300 hover:bg-gray-50 rounded-lg px-4 py-2">Tambah</button>
        </div>

        @if ($recentDates->isNotEmpty())
            <div class="mt-3 text-xs text-gray-500">
                Riwayat tanggal:
                @foreach ($recentDates as $d)
                    <button wire:click="setDateAndLoad('{{ $d->toDateString() }}')" class="font-mono underline mr-2 hover:text-brand">{{ $d->format('d M Y') }}</button>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold">Grid Stock — {{ $distributorId ? $distributors->firstWhere('id', $distributorId)?->name : 'Pilih distributor dulu' }} · {{ $tanggal }}</h3>
            <button id="save-stock-btn" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium rounded-lg px-4 py-2">
                Simpan Batch
            </button>
        </div>

        <div wire:ignore>
            <div id="stock-grid" class="ag-theme-quartz" style="height: 480px; width: 100%;"></div>
        </div>
    </div>

    @script
    <script>
        let wireComponent = @this;
        let gridApi = null;

        function statusRenderer(params) {
            if (params.value) {
                return `<span class="text-xs font-mono uppercase" style="background:#dcece7;color:#07352d;border-radius:999px;padding:2px 8px;">Ter-mapping</span>`;
            }
            return `<span class="text-xs font-mono uppercase" style="background:#fef3c7;color:#92400e;border-radius:999px;padding:2px 8px;">Belum ter-mapping</span>`;
        }

        function initGrid(rows) {
            const columnDefs = [
                { field: 'item_name', headerName: 'Item (Distributor)', editable: false, flex: 2, minWidth: 220 },
                { field: 'quantity', headerName: 'Qty', editable: true, type: 'numericColumn', width: 120,
                  valueParser: p => Number(p.newValue) || 0 },
                { field: 'satuan', headerName: 'Satuan', editable: true, width: 110 },
                { field: 'expired_date', headerName: 'ED (YYYY-MM-DD)', editable: true, width: 150 },
                { field: 'batch_no', headerName: 'Batch No', editable: true, width: 140 },
                { field: 'mapped', headerName: 'Status Mapping', editable: false, width: 160, cellRenderer: statusRenderer },
                { field: 'distributor_item_id', hide: true },
            ];

            gridApi = window.agGridCreateGrid(document.getElementById('stock-grid'), {
                columnDefs,
                rowData: rows || [],
                defaultColDef: { resizable: true, sortable: true },
                singleClickEdit: true,
                stopEditingWhenCellsLoseFocus: true,
            });
        }

        initGrid(@js($rows));

        wireComponent.on('rows-loaded', (event) => {
            const rows = event.rows ?? event[0]?.rows ?? [];
            if (gridApi) {
                gridApi.setGridOption('rowData', rows);
            }
        });

        document.getElementById('save-stock-btn').addEventListener('click', () => {
            if (!gridApi) return;
            const rows = [];
            gridApi.forEachNode(node => rows.push(node.data));
            wireComponent.call('saveRows', rows);
        });
    </script>
    @endscript
</div>
