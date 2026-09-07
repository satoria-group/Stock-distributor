<div>
    <div class="flex items-center justify-between gap-4 mb-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama distributor..."
               class="w-80 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        @can('create', \App\Models\Distributor::class)
        <button wire:click="openCreate" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2">
            + Tambah Distributor
        </button>
        @endcan
    </div>

    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-500 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Kode</th>
                    <th class="text-left px-4 py-2.5">Nama Distributor</th>
                    <th class="text-left px-4 py-2.5">Status</th>
                    <th class="text-right px-4 py-2.5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                @forelse ($distributors as $distributor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono text-xs">{{ $distributor->distributor_code }}</td>
                        <td class="px-4 py-2.5">{{ $distributor->name }}</td>
                        <td class="px-4 py-2.5">
                            @if ($distributor->is_active)
                                <span class="text-xs font-mono uppercase bg-brand-soft text-brand-dark rounded-full px-2 py-0.5">Aktif</span>
                            @else
                                <span class="text-xs font-mono uppercase bg-gray-100 text-gray-500 rounded-full px-2 py-0.5">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right space-x-3">
                            @can('update', $distributor)
                            <button wire:click="openEdit({{ $distributor->id }})" class="text-brand hover:underline text-xs font-medium">Edit</button>
                            @endcan
                            @can('delete', $distributor)
                            <button wire:click="delete({{ $distributor->id }})" wire:confirm="Hapus distributor {{ $distributor->name }}?" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada distributor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $distributors->links() }}</div>

    @if ($showModal)
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-base font-semibold mb-4">{{ $editingId ? 'Edit Distributor' : 'Tambah Distributor' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Kode Distributor</label>
                    <input type="text" wire:model="distributor_code" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('distributor_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama Distributor</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-brand focus:ring-brand">
                    Aktif
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100">Batal</button>
                    <button type="submit" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
