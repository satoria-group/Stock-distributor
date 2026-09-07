<div>
    <div class="flex items-center justify-end mb-5">
        <button wire:click="openCreate" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2">
            + Tambah User
        </button>
    </div>

    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-500 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Nama</th>
                    <th class="text-left px-4 py-2.5">Email</th>
                    <th class="text-left px-4 py-2.5">Role</th>
                    <th class="text-right px-4 py-2.5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                @foreach ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5">{{ $user->name }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-2.5">
                            <span class="text-xs font-mono uppercase bg-brand-soft text-brand-dark rounded-full px-2 py-0.5">
                                {{ $user->getRoleNames()->first() ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right space-x-3">
                            <button wire:click="openEdit({{ $user->id }})" class="text-brand hover:underline text-xs font-medium">Edit</button>
                            @if ($user->id !== auth()->id())
                            <button wire:click="delete({{ $user->id }})" wire:confirm="Hapus user {{ $user->name }}?" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    @if ($showModal)
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-base font-semibold mb-4">{{ $editingId ? 'Edit User' : 'Tambah User' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Email</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">
                        Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}
                    </label>
                    <input type="password" wire:model="password" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Role</label>
                    <select wire:model="role" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="admin">Admin</option>
                        <option value="sales">Sales</option>
                        <option value="logistik">Logistik</option>
                    </select>
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
