<div>
    @include('partials.flash-alert')

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <p class="text-xs text-slate-500 leading-relaxed max-w-2xl">
            Distributor yang punya sistem sendiri mengirim snapshot stok ke
            <span class="font-mono font-bold text-slate-700">POST {{ $endpoint }}</span>
            dengan header <span class="font-mono font-bold text-slate-700">Authorization: Bearer &lt;token&gt;</span>.
            Token hanya berlaku untuk cabang-cabang grup usahanya. Kiriman baru mengganti snapshot tanggal yang sama.
        </p>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer shrink-0">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Akses API</span>
        </button>
    </div>

    @if ($plainToken)
    <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-4" x-data="{ copied: false }">
        <div class="text-xs font-bold text-amber-900 mb-2">Token API — salin sekarang, tidak akan ditampilkan lagi</div>
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $plainToken }}" x-ref="token"
                   class="flex-1 rounded-xl border border-amber-200 bg-white px-3.5 py-2.5 text-xs font-mono text-slate-800">
            <button type="button" @click="navigator.clipboard.writeText($refs.token.value); copied = true"
                    class="text-xs font-bold px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white cursor-pointer">
                <span x-show="!copied">Salin</span><span x-show="copied">Tersalin</span>
            </button>
            <button type="button" wire:click="dismissToken" class="text-xs font-semibold text-amber-900 px-4 py-2.5 rounded-xl hover:bg-amber-100 cursor-pointer">Selesai</button>
        </div>
    </div>
    @endif

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-x-auto shadow-xs mb-8">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Grup Usaha</th>
                    <th class="px-5 py-3.5">IP Diizinkan</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5">Terakhir Dipakai</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($clients as $c)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-3.5 font-bold text-slate-900">{{ $c->name }}</td>
                        <td class="px-5 py-3.5 text-slate-700">{{ $c->group?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 font-mono text-slate-600">{{ $c->allowed_ips ?: 'Semua IP' }}</td>
                        <td class="px-5 py-3.5">
                            @if ($c->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold">Aktif</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 font-bold">Non-aktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-slate-500">{{ $c->last_used_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <button type="button" wire:click="openEdit({{ $c->id }})" class="text-xs font-bold text-[#0d6d5f] px-2 py-1 rounded-lg hover:bg-[#e6f4f1] cursor-pointer">Edit</button>
                            <button type="button" wire:click="regenerate({{ $c->id }})" wire:confirm="Buat token baru untuk {{ $c->name }}? Token lama langsung tidak berlaku dan sistem distributor harus diperbarui."
                                    class="text-xs font-bold text-amber-700 px-2 py-1 rounded-lg hover:bg-amber-50 cursor-pointer">Token Baru</button>
                            <button type="button" wire:click="toggleActive({{ $c->id }})" class="text-xs font-bold text-slate-600 px-2 py-1 rounded-lg hover:bg-slate-100 cursor-pointer">{{ $c->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Hapus akses API {{ $c->name }}?"
                                    class="text-xs font-bold text-rose-600 px-2 py-1 rounded-lg hover:bg-rose-50 cursor-pointer">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center">
                            <div class="font-bold text-slate-700 text-sm">Belum ada akses API</div>
                            <div class="text-xs text-slate-400 mt-0.5">Semua distributor mengirim stok lewat Excel / email.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h3 class="text-sm font-bold text-slate-900 mb-3">Log Kiriman API (50 terakhir)</h3>
    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-x-auto shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5">Diterima</th>
                    <th class="px-5 py-3.5">Klien</th>
                    <th class="px-5 py-3.5">Request ID</th>
                    <th class="px-5 py-3.5">Tgl Stok</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Cabang</th>
                    <th class="px-5 py-3.5 text-right">Disimpan</th>
                    <th class="px-5 py-3.5 text-right">Belum Mapping</th>
                    <th class="px-5 py-3.5">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($logs as $log)
                    @php
                        $color = match ($log->status) {
                            'success' => 'bg-emerald-50 text-emerald-700',
                            'partial_unmapped' => 'bg-amber-50 text-amber-700',
                            'processing' => 'bg-sky-50 text-sky-700',
                            default => 'bg-rose-50 text-rose-700',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition align-top">
                        <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3.5 text-slate-700">{{ $log->client?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 font-mono text-slate-700">{{ $log->request_id }}</td>
                        <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">{{ $log->tanggal_snapshot?->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5"><span class="inline-flex px-2 py-0.5 rounded-lg font-bold {{ $color }}">{{ $log->status }}</span></td>
                        <td class="px-5 py-3.5 text-right font-mono">{{ $log->branch_count }}</td>
                        <td class="px-5 py-3.5 text-right font-mono">{{ $log->imported_rows }}</td>
                        <td class="px-5 py-3.5 text-right font-mono">{{ $log->skipped_rows }}</td>
                        <td class="px-5 py-3.5 text-slate-500 max-w-md">{{ $log->error_message }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-slate-400">Belum ada kiriman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 border border-slate-200">
            <h3 class="text-base font-bold text-slate-900 mb-4">{{ $editingId ? 'Edit Akses API' : 'Tambah Akses API' }}</h3>
            <form wire:submit="save" class="space-y-4 text-xs">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama</label>
                    <input type="text" wire:model="name" placeholder="mis. SDL - Sistem ERP"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Grup Usaha</label>
                    <select wire:model="distributor_group_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        <option value="">— Pilih grup —</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Token hanya boleh mengirim stok untuk cabang di grup ini.</p>
                    @error('distributor_group_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">IP yang Diizinkan (opsional)</label>
                    <input type="text" wire:model="allowed_ips" placeholder="mis. 203.0.113.10, 203.0.113.11"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    <p class="text-[11px] text-slate-400 mt-1">Pisahkan dengan koma. Kosongkan untuk mengizinkan semua IP.</p>
                    @error('allowed_ips') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showModal', false)" class="text-xs font-semibold text-slate-600 px-4 py-2.5 rounded-xl hover:bg-slate-100 cursor-pointer">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex items-center gap-2 text-xs bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold px-5 py-2.5 rounded-xl shadow-2xs cursor-pointer transition disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
