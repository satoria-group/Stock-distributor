<div>
    @include('partials.flash-alert')

    <!-- Banner penjelasan konsep grup template -->
    <div class="mb-5 bg-teal-50/70 border border-teal-200/80 rounded-2xl p-4 flex items-start gap-3 shadow-2xs">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white shrink-0 mt-0.5" style="background: #0d6d5f;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
        </div>
        <div class="text-xs text-slate-700">
            <h4 class="font-bold text-[#07352d]">Distributor Tidak Wajib Memakai Template Baku</h4>
            <p class="text-slate-600 mt-0.5 leading-relaxed">
                Daftarkan satu <b>Grup Template</b> per grup usaha (mis. <b>UDC</b> — seluruh cabangnya memakai bentuk berkas yang sama), lalu susun tiap kolom sistem dari kolom berkas mereka. Satu kolom sistem boleh dirangkai dari <b>beberapa kolom</b> dan <b>teks tetap</b> sekaligus — misalnya kode distributor <code>UDC</code> + kolom <code>CUST_NAME</code>, atau tanggal dari kolom <code>TGL</code>, <code>BLN</code>, dan <code>TAHUN</code>.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-96">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama grup atau catatan..."
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
            </div>

            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled"
                    title="{{ $search ? 'Reset pencarian' : 'Pencarian dalam posisi default' }}"
                    class="w-[38px] h-[38px] flex items-center justify-center rounded-xl border transition cursor-pointer shrink-0 {{ $search ? 'bg-emerald-50 hover:bg-emerald-100 text-[#0d6d5f] border-emerald-300 shadow-2xs hover:scale-105 active:scale-95' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-400 border-slate-200' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        @can('create', \App\Models\DistributorTemplateGroup::class)
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Format Berkas</span>
        </button>
        @endcan
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5 w-52">Nama Grup</th>
                    <th class="px-5 py-3.5">Pemetaan Kolom</th>
                    <th class="px-5 py-3.5 w-52">Setelan Pembacaan</th>
                    <th class="px-5 py-3.5 w-36 text-center">Dipakai</th>
                    <th class="px-5 py-3.5 w-32 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($groups as $group)
                    <tr class="hover:bg-slate-50/80 transition align-top">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs {{ $group->is_active ? 'text-slate-900' : 'text-slate-400' }}">{{ $group->name }}</span>
                                @if ($group->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Nonaktif</span>
                                @endif
                            </div>
                            @if ($group->notes)
                                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{{ $group->notes }}</div>
                            @endif
                            <div class="text-[11px] text-slate-400 mt-1 font-mono">
                                sheet: {{ $group->sheet_name ?: 'otomatis' }} · baris header: {{ $group->header_row ?: 'otomatis' }}
                            </div>
                            @if ($group->codeMap())
                                <div class="text-[11px] text-slate-400 mt-0.5 font-mono">{{ count($group->codeMap()) }} kode distributor dipetakan ulang</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @php $recipes = $group->recipes(); @endphp
                            @if ($recipes)
                                <div class="space-y-1">
                                    @foreach ($recipes as $canonical => $recipe)
                                        <div class="flex items-start gap-1.5 flex-wrap">
                                            <span class="text-[11px] font-bold text-slate-700 w-40 shrink-0">{{ $canonical }}</span>
                                            <span class="text-slate-300">=</span>
                                            @foreach ($recipe->parts as $part)
                                                @if (isset($part['column']))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-teal-50 text-[#0d6d5f] border border-teal-200/80 text-[11px] font-mono font-semibold">{{ $part['column'] }}</span>
                                                @elseif (isset($part['cell']))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-mono font-semibold">sel {{ $part['cell'] }}</span>
                                                @elseif (isset($part['sheet']))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-semibold">nama sheet</span>
                                                @elseif (isset($part['file']))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-semibold">nama berkas</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 text-[11px] font-mono">"{{ $part['text'] }}"</span>
                                                @endif
                                                @if (! $loop->last)<span class="text-slate-400 text-[11px]">+</span>@endif
                                            @endforeach
                                            @if ($recipe->nospace || $recipe->upper)
                                                <span class="text-[10px] text-slate-400">({{ trim(($recipe->nospace ? 'tanpa spasi' : '').($recipe->nospace && $recipe->upper ? ', ' : '').($recipe->upper ? 'huruf besar' : '')) }})</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400 text-[11px] italic">Belum ada resep — mengandalkan deteksi otomatis</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex flex-col gap-1">
                                @foreach ($group->settingLabels() as $label)
                                    <span class="text-[11px] text-slate-600">• {{ $label }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold {{ $group->distributor_groups_count > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                    {{ $group->distributor_groups_count }} grup
                                </span>
                                @if ($group->distributors_count > 0)
                                    <span class="text-[10px] text-amber-700">+{{ $group->distributors_count }} cabang khusus</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                            @can('update', $group)
                            <button type="button" wire:click="toggleActive({{ $group->id }})"
                                    wire:confirm="{{ $group->is_active ? 'Nonaktifkan grup '.$group->name.'? Resepnya tidak lagi dicobakan saat membaca berkas.' : 'Aktifkan kembali grup '.$group->name.'?' }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl border transition shadow-2xs cursor-pointer {{ $group->is_active ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white border-emerald-200/60' : 'text-slate-500 bg-slate-100 hover:bg-slate-600 hover:text-white border-slate-200' }}"
                                    title="{{ $group->is_active ? 'Nonaktifkan grup ini' : 'Aktifkan grup ini' }}">
                                @if ($group->is_active)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                @endif
                            </button>
                            <button type="button" wire:click="openEdit({{ $group->id }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 transition shadow-2xs cursor-pointer"
                                    title="Edit grup {{ $group->name }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @endcan
                            @can('delete', $group)
                            <button type="button" wire:click="delete({{ $group->id }})" wire:confirm="Hapus grup template {{ $group->name }}? Distributor anggotanya akan kembali memakai deteksi otomatis."
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shadow-2xs cursor-pointer"
                                    title="Hapus grup template">
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
                            <div class="text-3xl mb-2">🗂️</div>
                            <div class="font-bold text-slate-700 text-sm">Belum ada grup template</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tambahkan grup untuk memetakan judul kolom berkas Excel distributor.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-2">
        {{ $groups->links('livewire::tailwind') }}
    </div>

    @if ($showModal)
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-start justify-center z-50 p-4 overflow-y-auto" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-6 border border-slate-200 my-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white shrink-0" style="background: #0d6d5f;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">{{ $editingId ? 'Edit Format Berkas' : 'Tambah Format Berkas' }}</h3>
                </div>
                <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1 rounded-lg">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-5 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama Grup</label>
                        <input type="text" wire:model="name" placeholder="contoh: UDC (semua cabang)"
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Catatan <span class="font-medium normal-case text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="notes" placeholder="contoh: berkas ekspor dari sistem gudang UDC"
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        @error('notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-start gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" wire:model.live="is_active" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] mt-0.5">
                    <span>
                        Grup aktif
                        <span class="block font-normal text-[11px] text-slate-500 mt-0.5">
                            Hanya grup aktif yang dicobakan saat membaca berkas masuk. Nonaktifkan selama resepnya masih disusun — resep setengah jadi bisa keliru "berhasil" membaca berkas milik grup lain. Menonaktifkan tidak menghapus apa pun.
                        </span>
                    </span>
                </label>

                <!-- Susunan kolom: setelan yang melekat pada sebuah kolom ada DI DALAM
                     baris kolom itu, bukan di panel terpisah jauh di bawah. -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Susunan Kolom</label>
                    @error('columnMap') <p class="text-xs text-rose-600 mb-2">{{ $message }}</p> @enderror

                    <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100">
                        @foreach ($definitions as $canonical => $def)
                            @php
                                $parts = $columnMap[$canonical]['parts'] ?? [];
                                $tanggalKolom = collect($columnMap['Tanggal']['parts'] ?? [])
                                    ->filter(fn ($p) => ($p['type'] ?? 'column') === 'column' && trim((string) ($p['value'] ?? '')) !== '');
                                $tanggalDirakit = $tanggalKolom->count() >= 3;
                            @endphp
                            <div class="p-4 hover:bg-slate-50/60 transition">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                            {{ $def['label'] }}
                                            @if ($def['required'])
                                                <span class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded-md">Wajib</span>
                                            @else
                                                <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded-md">Opsional</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">contoh isi: {{ $def['example'] }}</div>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button type="button" wire:click="addPart('{{ $canonical }}', 'column')"
                                                class="text-[11px] font-bold text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                            + Kolom
                                        </button>
                                        <button type="button" wire:click="addPart('{{ $canonical }}', 'text')"
                                                class="text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-700 hover:text-white border border-slate-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                            + Teks tetap
                                        </button>
                                        <button type="button" wire:click="addPart('{{ $canonical }}', 'cell')"
                                                title="Isi sebuah sel tetap, mis. judul laporan di A3"
                                                class="text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                            + Sel
                                        </button>
                                        <button type="button" wire:click="addPart('{{ $canonical }}', 'sheet')"
                                                title="Nama sheet yang sedang dibaca"
                                                class="text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                            + Nama sheet
                                        </button>
                                        <button type="button" wire:click="addPart('{{ $canonical }}', 'file')"
                                                title="Nama berkas yang diunggah"
                                                class="text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                            + Nama berkas
                                        </button>
                                    </div>
                                </div>

                                @if ($parts)
                                    <div class="space-y-2">
                                        @foreach ($parts as $i => $part)
                                            @php
                                                $partType = $part['type'] ?? 'column';
                                                $partLabel = ['column' => 'kolom', 'text' => 'teks', 'cell' => 'sel', 'sheet' => 'sheet', 'file' => 'berkas'][$partType] ?? 'kolom';
                                                $partStyle = match ($partType) {
                                                    'column' => 'bg-teal-50 text-[#0d6d5f] border border-teal-200',
                                                    'cell', 'sheet', 'file' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                                                    default => 'bg-slate-100 text-slate-600 border border-slate-200',
                                                };
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold text-slate-400 w-10 shrink-0">{{ $i === 0 ? 'mulai' : '+' }}</span>

                                                @if ($partType === 'column')
                                                    <input type="text" wire:model="columnMap.{{ $canonical }}.parts.{{ $i }}.value" placeholder="judul kolom pada berkas, mis. SOH_QTY"
                                                           class="flex-1 rounded-xl border border-teal-200 bg-teal-50/40 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                                @elseif ($partType === 'cell')
                                                    <input type="text" wire:model="columnMap.{{ $canonical }}.parts.{{ $i }}.value" placeholder="alamat sel, mis. A3 atau B1"
                                                           class="flex-1 rounded-xl border border-indigo-200 bg-indigo-50/40 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400">
                                                @elseif ($partType === 'sheet')
                                                    <div class="flex-1 rounded-xl border border-indigo-200 bg-indigo-50/40 px-3 py-2 text-xs text-indigo-900 font-semibold">
                                                        Nama sheet yang sedang dibaca
                                                    </div>
                                                @elseif ($partType === 'file')
                                                    <div class="flex-1 rounded-xl border border-indigo-200 bg-indigo-50/40 px-3 py-2 text-xs text-indigo-900 font-semibold">
                                                        Nama berkas yang diunggah
                                                    </div>
                                                @else
                                                    <input type="text" wire:model="columnMap.{{ $canonical }}.parts.{{ $i }}.value" placeholder="teks tetap, mis. UDC"
                                                           class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400">
                                                @endif

                                                <span class="text-[10px] font-semibold px-2 py-1 rounded-md shrink-0 {{ $partStyle }}">{{ $partLabel }}</span>

                                                <button type="button" wire:click="removePart('{{ $canonical }}', {{ $i }})"
                                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shrink-0 cursor-pointer"
                                                        title="Hapus bagian ini">
                                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @endforeach

                                        <div class="flex flex-wrap items-center gap-4 pt-1 pl-12">
                                            <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 cursor-pointer">
                                                <input type="checkbox" wire:model="columnMap.{{ $canonical }}.nospace" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                                <span>Buang semua spasi</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 cursor-pointer">
                                                <input type="checkbox" wire:model="columnMap.{{ $canonical }}.upper" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                                <span>Jadikan huruf besar</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 cursor-pointer"
                                                   title="Untuk berkas pivot table, yang mengosongkan kolom ini di baris lanjutan">
                                                <input type="checkbox" value="{{ $canonical }}" wire:model="fill_down" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                                <span>Isi turun bila kosong</span>
                                            </label>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-400 italic pl-12">Kosong — tidak dipetakan untuk grup ini. @if($def['required'])<span class="text-rose-500 not-italic font-semibold">Kolom wajib: berkas akan ditolak kalau tetap kosong.</span>@endif</p>
                                @endif

                                {{-- Setelan yang hanya berlaku untuk kolom ini, diletakkan di sini
                                     supaya keputusannya tidak menuntut menggulir ke panel lain. --}}
                                @if ($canonical === 'Tanggal')
                                    <div class="mt-3 pl-12">
                                        @if ($tanggalDirakit)
                                            <div class="text-[11px] rounded-lg px-2.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">
                                                Dirakit dari {{ $tanggalKolom->count() }} kolom: {{ $tanggalKolom->pluck('value')->implode(', ') }} — berurutan hari, bulan, tahun.
                                            </div>
                                        @else
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Format tanggal pada berkas</label>
                                            <input type="text" wire:model.live.debounce.500ms="date_format" placeholder="kosongkan = deteksi otomatis, mis. dd/mm/yyyy"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                            <x-format-hint :format="$date_format" month-rule="akhir bulan" />
                                            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                                Isi hanya bila deteksi otomatis meleset atau bentuknya ambigu (mis. <code>01/02/2027</code>). Format tanpa hari dipakai <b>hari terakhir</b> bulan itu.
                                            </p>
                                            @error('date_format') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                        @endif
                                    </div>
                                @elseif ($canonical === 'Quantity')
                                    <label class="mt-3 ml-12 flex items-start gap-2 text-[11px] font-semibold text-slate-600 cursor-pointer">
                                        <input type="checkbox" wire:model="skip_nonpositive_qty" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] mt-0.5">
                                        <span>
                                            Abaikan baris dengan kuantitas ≤ 0
                                            <span class="block font-normal text-slate-500 mt-0.5">Untuk grup yang mengirim seluruh isi gudang termasuk stok kosong.</span>
                                        </span>
                                    </label>
                                @elseif ($canonical === 'ED')
                                    <div class="mt-3 pl-12">
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Format tanggal kedaluwarsa pada berkas</label>
                                        <input type="text" wire:model.live.debounce.500ms="ed_format" placeholder="kosongkan = deteksi otomatis, mis. mm/yyyy"
                                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                        <x-format-hint :format="$ed_format" month-rule="tanggal 1" />
                                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                            Format tanpa hari dipakai <b>tanggal 1</b> bulan itu — memajukan kedaluwarsa lebih aman untuk FEFO daripada mengundurkannya.
                                        </p>
                                        @error('ed_format') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                @elseif ($canonical === 'Batch No')
                                    <div class="mt-3 pl-12">
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Batch pengganti <span class="font-medium text-slate-400">(bila berkasnya tidak punya kolom batch)</span></label>
                                        <input type="text" wire:model="default_batch" placeholder="mis. NO-BATCH"
                                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                            Nomor batch adalah bagian identitas baris stok, jadi <b>tanpa nilai ini seluruh berkas ditolak</b>. Nilai yang diisi di sini ikut tersimpan sebagai nomor batch, jadi pilih yang terbaca jelas di laporan.
                                        </p>
                                        @error('default_batch') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Sisa setelan: soal LETAK data di dalam berkas, bukan soal satu kolom -->
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">Letak Data di Berkas</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Sheet yang dibaca</label>
                            <input type="text" wire:model="sheet_name" placeholder="nama sheet atau pola, mis. SDL *"
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                                Kosongkan untuk memakai sheet <code>Template</code> atau sheet pertama. Tanda bintang menangkap banyak sheet sekaligus — <code>SDL *</code> untuk berkas yang satu sheet-nya satu cabang.
                            </p>
                            @error('sheet_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Baris header <span class="font-medium text-slate-400">(opsional)</span></label>
                            <input type="number" min="1" max="200" wire:model="header_row" placeholder="kosongkan = deteksi otomatis"
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                                Nomor baris tempat judul kolom berada. Isi bila berkasnya punya kop surat dan deteksi otomatis meleset.
                            </p>
                            @error('header_row') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Kode versi distributor sendiri di kolom ID DISTRIBUTOR, dipetakan ke distributor_code resmi -->
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Pemetaan Kode Distributor <span class="font-medium text-slate-400 normal-case">(opsional)</span></div>
                    <p class="text-[11px] text-slate-500 mb-3 leading-relaxed">
                        Isi hanya bila distributor pada grup ini menulis kode miliknya sendiri di kolom ID DISTRIBUTOR — bukan kode resmi yang terdaftar di Master Distributor. Kode di kiri (dari berkas) dipetakan ke kode resmi di kanan.
                    </p>

                    @if ($codeMapDistributors->isEmpty())
                        <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-2">
                            Pilih dulu Grup Distributor yang memakai format ini di bagian bawah — dropdown kode resmi mengikuti anggota grup usaha yang dicentang.
                        </p>
                    @endif

                    <div class="space-y-2">
                        @foreach ($codeMap as $i => $row)
                            <div class="flex items-center gap-2">
                                <input type="text" wire:model="codeMap.{{ $i }}.alias" placeholder="kode di berkas, mis. D001"
                                       class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                <svg width="14" height="14" class="text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                {{-- Satu distributor boleh punya lebih dari satu kode alias (mis. cabang
                                     yang dikenali dua sistem berbeda), jadi daftar ini TIDAK menyembunyikan
                                     distributor yang sudah dipakai di baris lain. --}}
                                <select wire:model="codeMap.{{ $i }}.official"
                                        class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                    <option value="">— Pilih Distributor Resmi —</option>
                                    @foreach ($codeMapDistributors as $d)
                                        <option value="{{ $d->distributor_code }}">{{ $d->name }} ({{ $d->distributor_code }})</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="removeCodeMapRow({{ $i }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shrink-0 cursor-pointer"
                                        title="Hapus baris ini">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" wire:click="addCodeMapRow" @disabled($codeMapDistributors->isEmpty())
                            class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#0d6d5f] bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-lg transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-emerald-50">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Pemetaan Kode
                    </button>

                    @error('codeMap') <p class="text-xs text-rose-600 mt-2">{{ $message }}</p> @enderror
                </div>


                <!-- Dipakai oleh grup usaha mana, bukan oleh distributor mana satu per satu -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Grup Distributor yang Memakai Format Ini</label>
                    <div class="rounded-2xl border border-slate-200 max-h-56 overflow-y-auto divide-y divide-slate-100">
                        @forelse ($allGroups as $g)
                            @php $usedByOther = $g->template_group_id && $g->template_group_id !== $editingId; @endphp
                            <label class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50/80 transition cursor-pointer">
                                <input type="checkbox" value="{{ $g->id }}" wire:model="selectedGroups"
                                       class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 border border-slate-200" style="background: {{ $g->colorOrDefault() }};"></span>
                                <span class="text-xs font-semibold text-slate-800">{{ $g->name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $g->distributors_count }} cabang</span>
                                @if ($usedByOther)
                                    <span class="ml-auto text-[10px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">memakai format lain</span>
                                @endif
                            </label>
                        @empty
                            <div class="px-4 py-6 text-center text-[11px] text-slate-400 italic">
                                Belum ada grup distributor. Buat dulu di Master Distributor → tab Grup Distributor.
                            </div>
                        @endforelse
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Mencentang satu grup berarti <b>seluruh cabangnya</b> memakai format ini — tidak perlu ditandai satu per satu. Cabang yang formatnya menyimpang diatur sendiri lewat form distributornya.
                    </p>
                </div>

                <!-- Uji coba: membaca berkas sungguhan tanpa menyimpan apa pun -->
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-indigo-900">Uji Coba Pembacaan</label>
                            <p class="text-[11px] text-indigo-800/80 mt-0.5 leading-relaxed">
                                Membaca seluruh isi berkas dengan resep yang sedang disusun di form ini — <b>tidak ada data yang disimpan</b>. Gunakan untuk memastikan hasilnya benar sebelum berkas sungguhan diunggah.
                            </p>
                        </div>
                        @if ($testReport)
                            <button type="button" wire:click="clearTest" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 cursor-pointer shrink-0">Bersihkan</button>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input type="file" wire:model="testFile" accept=".xlsx,.xls"
                               class="flex-1 min-w-48 text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-100 file:text-indigo-900 hover:file:bg-indigo-600 hover:file:text-white file:cursor-pointer cursor-pointer">
                        <button type="button" wire:click="runTest" wire:loading.attr="disabled" wire:target="runTest,testFile"
                                class="inline-flex items-center gap-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl shadow-2xs transition cursor-pointer disabled:opacity-60 disabled:cursor-wait">
                            <svg wire:loading.remove wire:target="runTest" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <svg wire:loading wire:target="runTest" class="animate-spin" width="13" height="13" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="runTest">Jalankan Uji Coba</span>
                            <span wire:loading wire:target="runTest">Membaca…</span>
                        </button>
                    </div>
                    @error('testFile') <p class="text-xs text-rose-600 mt-1.5">{{ $message }}</p> @enderror

                    @if ($testReport)
                        @if (! ($testReport['ok'] ?? false))
                            <div class="mt-3 text-[11px] rounded-xl px-3 py-2.5 bg-rose-50 text-rose-700 border border-rose-200 leading-relaxed">
                                <span class="font-bold">Berkas tidak bisa dibaca dengan resep ini.</span><br>
                                {{ $testReport['error'] }}
                            </div>
                        @else
                            <div class="mt-3 space-y-2">
                                <div class="text-[11px] rounded-xl px-3 py-2 bg-white border border-indigo-200 text-slate-700">
                                    @if ($testReport['file'] ?? null)
                                        <span class="font-mono">{{ $testReport['file'] }}</span> ·
                                    @endif
                                    Sheet <b>{{ $testReport['sheet'] }}</b> · baris header <b>{{ $testReport['header_row'] }}</b> ·
                                    <b>{{ number_format($testReport['total_rows'], 0, ',', '.') }}</b> baris terbaca ·
                                    <b>{{ count($testReport['branches']) }}</b> cabang
                                    @if ($testReport['skipped_zero'] > 0)
                                        · {{ $testReport['skipped_zero'] }} baris berstok ≤ 0 dilewati
                                    @endif
                                </div>

                                {{-- Hasil tiap kolom untuk SATU baris nyata. Inilah jurang yang
                                     paling sering menipu: resep terlihat benar, hasilnya lain. --}}
                                @if ($testReport['columns'] ?? [])
                                    <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                                        <div class="bg-slate-50/90 px-3 py-2 text-[11px] text-slate-500 border-b border-slate-200">
                                            Hasil tiap kolom pada baris pertama
                                            @if ($testReport['sample_code'] ?? null)
                                                cabang <span class="font-mono font-bold text-slate-700">{{ $testReport['sample_code'] }}</span>
                                            @endif
                                        </div>
                                        <table class="w-full text-[11px] text-left">
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($testReport['columns'] as $col)
                                                    <tr>
                                                        <td class="px-3 py-2 w-44 align-top">
                                                            <span class="font-bold text-slate-700">{{ $col['label'] }}</span>
                                                            @if ($col['required'])
                                                                <span class="text-[9px] font-bold text-rose-700">wajib</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 w-52 align-top font-mono text-slate-500">{{ $col['recipe'] }}</td>
                                                        <td class="px-3 py-2 align-top">
                                                            @if ($col['value'] !== null)
                                                                <span class="font-mono font-bold text-emerald-800">{{ $col['value'] }}</span>
                                                            @elseif ($col['required'])
                                                                <span class="font-semibold text-rose-700">tidak terbaca</span>
                                                            @else
                                                                <span class="text-slate-400">kosong</span>
                                                            @endif

                                                            @if ($col['raw'])
                                                                {{-- Isi mentahnya menjelaskan kenapa hasilnya begitu —
                                                                     mis. rentang periode yang diambil tanggal akhirnya. --}}
                                                                <span class="block text-slate-400 mt-0.5">dari: “{{ \Illuminate\Support\Str::limit($col['raw'], 60) }}”</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                                    <table class="w-full text-[11px] text-left">
                                        <thead class="bg-slate-50/90 text-slate-500 border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2 font-bold">Kode</th>
                                                <th class="px-3 py-2 font-bold">Distributor</th>
                                                <th class="px-3 py-2 font-bold">Tanggal</th>
                                                <th class="px-3 py-2 font-bold text-right">Baris</th>
                                                <th class="px-3 py-2 font-bold text-right">Item</th>
                                                <th class="px-3 py-2 font-bold">Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($testReport['branches'] as $b)
                                                <tr>
                                                    <td class="px-3 py-2 font-mono font-bold text-slate-800">{{ $b['code'] }}</td>
                                                    <td class="px-3 py-2">
                                                        @if (! $b['known'])
                                                            <span class="font-semibold text-rose-700">belum terdaftar di Master</span>
                                                        @elseif ($b['inactive'])
                                                            <span class="font-semibold text-amber-700">{{ $b['distributor'] }} (nonaktif)</span>
                                                        @else
                                                            <span class="text-slate-700">{{ $b['distributor'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 font-mono {{ $b['tanggal'] ? 'text-slate-600' : 'text-rose-600 font-bold' }}">
                                                        {{ $b['tanggal'] ?: 'tidak terbaca' }}
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-mono text-slate-600">{{ number_format($b['rows'], 0, ',', '.') }}</td>
                                                    <td class="px-3 py-2 text-right font-mono text-slate-600">{{ $b['item_count'] }}</td>
                                                    <td class="px-3 py-2 text-slate-600">
                                                        <div class="flex flex-col gap-0.5">
                                                            @if (count($b['unmapped']) > 0)
                                                                <span class="text-amber-800">
                                                                    {{ count($b['unmapped']) }} item belum ter-mapping —
                                                                    tidak akan tersimpan
                                                                    <span class="text-slate-500">({{ \Illuminate\Support\Str::limit(implode(', ', array_slice($b['unmapped'], 0, 3)), 60) }})</span>
                                                                </span>
                                                            @endif
                                                            @if ($b['no_tanggal'] > 0)
                                                                <span class="text-rose-700">{{ $b['no_tanggal'] }} baris tanggalnya tidak terbaca</span>
                                                            @endif
                                                            @if ($b['no_batch'] > 0)
                                                                <span class="text-rose-700">{{ $b['no_batch'] }} baris tanpa nomor batch — berkas akan ditolak, isi Batch pengganti</span>
                                                            @endif
                                                            @if ($b['no_ed'] > 0)
                                                                <span class="text-slate-500">{{ $b['no_ed'] }} baris tanpa ED terbaca (tetap tersimpan, di luar jangkauan FEFO)</span>
                                                            @endif
                                                            @if (count($b['unmapped']) === 0 && $b['no_tanggal'] === 0 && $b['no_batch'] === 0 && $b['no_ed'] === 0)
                                                                <span class="text-emerald-700 font-semibold">Siap diimpor</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

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
