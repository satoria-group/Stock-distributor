<div>
    <div class="flex items-center justify-between gap-4 mb-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama produk..."
               class="w-80 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        @can('create', \App\Models\NetsuiteItem::class)
        <button wire:click="openCreate" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2">
            + Tambah Produk
        </button>
        @endcan
    </div>

    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-500 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Netsuite ID</th>
                    <th class="text-left px-4 py-2.5">Nama Produk</th>
                    <th class="text-left px-4 py-2.5">Satuan Default</th>
                    <th class="text-right px-4 py-2.5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                @forelse ($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono text-xs">{{ $item->netsuite_id }}</td>
                        <td class="px-4 py-2.5">{{ $item->netsuite_name }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $item->default_satuan ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right space-x-3">
                            @can('update', $item)
                            <button wire:click="openEdit({{ $item->id }})" class="text-brand hover:underline text-xs font-medium">Edit</button>
                            @endcan
                            @can('delete', $item)
                            <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus produk {{ $item->netsuite_name }}?" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    @if ($showModal)
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-base font-semibold mb-4">{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Netsuite ID</label>
                    <input type="text" wire:model="netsuite_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('netsuite_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama Produk</label>
                    <input type="text" wire:model="netsuite_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('netsuite_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Satuan Default</label>
                    <input type="text" wire:model="default_satuan" placeholder="mis. BOTOL" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
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
