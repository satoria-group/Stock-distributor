<div>
    @include('partials.flash-alert')

    <!-- Banner Info Whitelist Otomasi -->
    <div class="mb-5 bg-teal-50/70 border border-teal-200/80 rounded-2xl p-4 flex items-start gap-3 shadow-2xs">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white shrink-0 mt-0.5" style="background: #0d6d5f;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div class="text-xs text-slate-700">
            <h4 class="font-bold text-[#07352d]">Whitelist Email Pengirim Otomasi Laporan Stok</h4>
            <p class="text-slate-600 mt-0.5 leading-relaxed">
                Background Worker otomatis memverifikasi pengirim email dengan <b>Email Whitelist</b> sebelum memproses lampiran Excel ke database. Anda dapat mendaftarkan satu email, beberapa email dipisah koma (<code>,</code>), atau domain wildcard seperti <code>@kftd.co.id</code>.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="relative w-full sm:w-96">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, atau email whitelist..."
                   class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
        </div>

        @can('create', \App\Models\Distributor::class)
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Distributor</span>
        </button>
        @endcan
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5 w-36">Kode</th>
                    <th class="px-5 py-3.5">Nama Distributor</th>
                    <th class="px-5 py-3.5">Email Whitelist</th>
                    <th class="px-5 py-3.5 w-28 text-center">Status</th>
                    <th class="px-5 py-3.5 w-32 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($distributors as $distributor)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-3.5 font-mono text-slate-700 font-semibold text-xs">{{ $distributor->distributor_code }}</td>
                        <td class="px-5 py-3.5 font-bold text-slate-900 text-xs">{{ $distributor->name }}</td>
                        <td class="px-5 py-3.5 text-xs">
                            @if (! empty($distributor->sender_email))
                                <div class="flex flex-wrap gap-1.5 items-center max-w-md">
                                    @foreach(array_filter(array_map('trim', explode(',', $distributor->sender_email))) as $emailItem)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-teal-50 text-[#0d6d5f] border border-teal-200/80 text-[11px] font-mono shadow-2xs font-semibold">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ $emailItem }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-400 text-[11px]">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span class="italic">Belum diatur (bebas)</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if ($distributor->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-1.5">
                            @can('update', $distributor)
                            <button type="button" wire:click="openEdit({{ $distributor->id }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 transition shadow-2xs cursor-pointer"
                                    title="Edit Distributor ({{ $distributor->name }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @endcan
                            @can('delete', $distributor)
                            <button type="button" wire:click="delete({{ $distributor->id }})" wire:confirm="Hapus distributor {{ $distributor->name }}?"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shadow-2xs cursor-pointer"
                                    title="Hapus Distributor">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center text-slate-400">
                            <div class="text-3xl mb-2">🏢</div>
                            <div class="font-bold text-slate-700 text-sm">Belum ada data distributor</div>
                            <div class="text-xs text-slate-400 mt-0.5">Distributor mitra belum terdaftar dalam sistem.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-2">
        {{ $distributors->links('livewire::tailwind') }}
    </div>

    @if ($showModal)
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white shrink-0" style="background: #0d6d5f;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">{{ $editingId ? 'Edit Distributor' : 'Tambah Distributor' }}</h3>
                </div>
                <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1 rounded-lg">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4 text-xs">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Kode Distributor</label>
                    <input type="text" wire:model="distributor_code" placeholder="contoh: KFTD-BDG" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('distributor_code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama Distributor</label>
                    <input type="text" wire:model="name" placeholder="contoh: PT Kimia Farma Trading & Distribution Bandung" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Email Whitelist Pengirim</label>
                        <span class="text-[10px] font-semibold text-[#0d6d5f] bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">Otomasi Email</span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model="sender_email" placeholder="laporan@kftd.co.id atau @kftd.co.id"
                               class="w-full rounded-xl border border-slate-200 pl-10 pr-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Alamat email resmi yang diizinkan mengirim file laporan stok harian. Bisa masukkan satu email, beberapa email dipisah koma (<code>,</code>), atau domain wildcard seperti <code>@kftd.co.id</code>.
                    </p>
                    @error('sender_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer pt-1">
                    <input type="checkbox" wire:model="is_active" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                    <span>Aktif</span>
                </label>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('showModal', false)" class="text-xs font-semibold text-slate-600 px-4 py-2.5 rounded-xl hover:bg-slate-100 cursor-pointer">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex items-center gap-2 text-xs bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold px-5 py-2.5 rounded-xl shadow-2xs cursor-pointer transition disabled:opacity-60 disabled:cursor-wait">
                        <svg wire:loading wire:target="save" class="animate-spin" width="13" height="13" style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
