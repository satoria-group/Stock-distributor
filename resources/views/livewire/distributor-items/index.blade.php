<div>
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama item..."
               class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        <select wire:model.live="distributorFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            <option value="">Semua Distributor</option>
            @foreach ($distributors as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </select>

        <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
            <button wire:click="$set('mappingFilter', 'all')" class="px-3 py-2 {{ $mappingFilter === 'all' ? 'bg-brand text-white' : 'bg-white text-gray-600' }}">Semua</button>
            <button wire:click="$set('mappingFilter', 'mapped')" class="px-3 py-2 border-l border-gray-300 {{ $mappingFilter === 'mapped' ? 'bg-brand text-white' : 'bg-white text-gray-600' }}">Ter-mapping</button>
            <button wire:click="$set('mappingFilter', 'unmapped')" class="px-3 py-2 border-l border-gray-300 {{ $mappingFilter === 'unmapped' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600' }}">
                Belum ter-mapping @if($unmappedCount) ({{ $unmappedCount }}) @endif
            </button>
        </div>

        <div class="flex-1"></div>

        @can('create', \App\Models\DistributorItem::class)
        <button wire:click="openCreate" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium rounded-lg px-4 py-2">
            + Tambah Item Mapping
        </button>
        @endcan
    </div>

    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-500 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Distributor</th>
                    <th class="text-left px-4 py-2.5">Nama Item (Distributor)</th>
                    <th class="text-left px-4 py-2.5">Satuan</th>
                    <th class="text-left px-4 py-2.5">Produk Netsuite</th>
                    <th class="text-left px-4 py-2.5">Status</th>
                    <th class="text-right px-4 py-2.5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                @forelse ($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-600">{{ $item->distributor->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->item_name }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $item->satuan ?: '—' }}</td>
                        <td class="px-4 py-2.5">
                            @if ($item->netsuiteItem)
                                <span class="font-mono text-xs text-gray-500">{{ $item->netsuiteItem->netsuite_id }}</span>
                                <div class="text-xs">{{ $item->netsuiteItem->netsuite_name }}</div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            @if ($item->isMapped())
                                <span class="text-xs font-mono uppercase bg-brand-soft text-brand-dark rounded-full px-2 py-0.5">Ter-mapping</span>
                            @else
                                <span class="text-xs font-mono uppercase bg-amber-100 text-amber-700 rounded-full px-2 py-0.5">Belum ter-mapping</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right space-x-2">
                            @can('update', $item)
                            <button wire:click="openEdit({{ $item->id }})" class="text-brand hover:underline text-xs font-medium">Edit</button>
                            @endcan
                            @can('delete', $item)
                            <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus mapping item ini?" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    @if ($showModal)
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-base font-semibold mb-1">{{ $editingId ? 'Edit Mapping Item' : 'Tambah Mapping Item Baru' }}</h3>
            <p class="text-xs text-gray-500 mb-4 font-mono">{{ $editingId ? $item_name : 'Daftarkan nama item versi distributor' }}</p>
            <form wire:submit="save" class="space-y-4">
                @if (! $editingId)
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Distributor</label>
                    <select wire:model="distributor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Pilih Distributor —</option>
                        @foreach ($distributors as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->distributor_code }})</option>
                        @endforeach
                    </select>
                    @error('distributor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama Item (Versi Distributor)</label>
                    <input type="text" wire:model="item_name" placeholder="Contoh: RINGER LACTATE 500 mL" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('item_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Satuan (Distributor)</label>
                    <input type="text" wire:model="satuan" placeholder="Contoh: BOTOL, BOX, PCS" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('satuan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Produk Netsuite (Opsional)</label>
                    <select wire:model="netsuite_item_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Belum ter-mapping —</option>
                        @foreach ($netsuiteItems as $ns)
                            <option value="{{ $ns->id }}">{{ $ns->netsuite_id }} — {{ $ns->netsuite_name }}</option>
                        @endforeach
                    </select>
                    @error('netsuite_item_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100">Batal</button>
                    <button type="submit" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
