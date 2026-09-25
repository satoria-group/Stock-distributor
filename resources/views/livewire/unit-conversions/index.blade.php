<div>
    @include('partials.flash-alert')

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <p class="text-xs text-slate-500 leading-relaxed max-w-2xl">
            Saat berkas diimpor, qty bersatuan di kolom kiri dikalikan faktornya dan disimpan dalam satuan tujuan.
            Nilai asli dari berkas tetap tercatat. Huruf besar/kecil tidak dibedakan. Perubahan hanya berlaku untuk impor berikutnya.
        </p>
        @can('unit-conversions.manage')
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer shrink-0">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Konversi</span>
        </button>
        @endcan
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5">Satuan dari Berkas</th>
                    <th class="px-5 py-3.5">Dikonversi ke</th>
                    <th class="px-5 py-3.5 text-right">Faktor</th>
                    <th class="px-5 py-3.5">Contoh</th>
                    <th class="px-5 py-3.5 w-28 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($conversions as $c)
                    @php $f = rtrim(rtrim(number_format($c->factor, 4, ',', '.'), '0'), ','); @endphp
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-3.5 font-mono font-bold text-slate-900">{{ $c->from_unit }}</td>
                        <td class="px-5 py-3.5 font-mono font-semibold text-slate-700">{{ $c->to_unit }}</td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900">× {{ $f }}</td>
                        <td class="px-5 py-3.5 text-slate-500">1 {{ $c->from_unit }} = {{ $f }} {{ $c->to_unit }}</td>
                        <td class="px-5 py-3.5 text-right">
                            @can('unit-conversions.manage')
                            <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                <button type="button" wire:click="openEdit({{ $c->id }})"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 transition shadow-2xs cursor-pointer"
                                        title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Hapus konversi {{ $c->from_unit }}?"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shadow-2xs cursor-pointer"
                                        title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center text-slate-400">
                            <div class="font-bold text-slate-700 text-sm">Belum ada aturan konversi</div>
                            <div class="text-xs text-slate-400 mt-0.5">Semua satuan disimpan apa adanya sesuai berkas.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 border border-slate-200">
            <h3 class="text-base font-bold text-slate-900 mb-4">{{ $editingId ? 'Edit Konversi' : 'Tambah Konversi' }}</h3>
            <form wire:submit="save" class="space-y-4 text-xs">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Satuan dari Berkas</label>
                    <input type="text" wire:model="from_unit" placeholder="mis. BOX"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('from_unit') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Faktor</label>
                        <input type="number" step="any" min="0" wire:model="factor" placeholder="mis. 50"
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        @error('factor') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Dikonversi ke</label>
                        <select wire:model="to_unit" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                            @foreach (\App\Models\NetsuiteItem::UNITS as $unit)
                                <option value="{{ $unit }}">{{ $unit }}</option>
                            @endforeach
                        </select>
                        @error('to_unit') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
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
