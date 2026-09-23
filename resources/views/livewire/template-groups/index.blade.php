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
            <span>Tambah Grup Template</span>
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
                    <th class="px-5 py-3.5 w-32 text-center">Distributor</th>
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
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold {{ $group->distributors_count > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                {{ $group->distributors_count }} distributor
                            </span>
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
                    <h3 class="text-base font-bold text-slate-900">{{ $editingId ? 'Edit Grup Template' : 'Tambah Grup Template' }}</h3>
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

                <!-- Berkas contoh -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Berkas Contoh <span class="font-medium normal-case text-slate-400">(opsional, tidak disimpan)</span></label>
                        @if ($sampleHeaders)
                            <button type="button" wire:click="clearSample" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 cursor-pointer">Bersihkan</button>
                        @endif
                    </div>
                    <input type="file" wire:model="sampleFile" accept=".xlsx,.xls"
                           class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#e6f4f1] file:text-[#0d6d5f] hover:file:bg-[#0d6d5f] hover:file:text-white file:cursor-pointer cursor-pointer">
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Unggah satu berkas Excel asli dari grup ini. Judul kolomnya menjadi pilihan dropdown di bawah, dan beberapa baris pertamanya dipakai untuk pratinjau.
                    </p>
                    <div wire:loading wire:target="sampleFile" class="text-[11px] font-semibold text-[#0d6d5f] mt-1.5">Membaca berkas contoh…</div>
                    @error('sampleFile') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    @if ($sampleInfo)
                        <div class="mt-2.5 text-[11px] font-semibold rounded-xl px-3 py-2 border {{ $sampleOk ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                            {{ $sampleInfo }}
                        </div>
                    @endif
                </div>

                <!-- Resep tiap kolom sistem -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Susunan Kolom</label>
                    @error('columnMap') <p class="text-xs text-rose-600 mb-2">{{ $message }}</p> @enderror

                    <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100">
                        @foreach ($definitions as $canonical => $def)
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
                                        <div class="text-[11px] text-slate-400 mt-0.5 font-mono">{{ $canonical }} · contoh: {{ $def['example'] }}</div>
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
                                    </div>
                                </div>

                                @php $parts = $columnMap[$canonical]['parts'] ?? []; @endphp

                                @if ($parts)
                                    <div class="space-y-2">
                                        @foreach ($parts as $i => $part)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold text-slate-400 w-10 shrink-0">{{ $i === 0 ? 'mulai' : '+' }}</span>

                                                @if (($part['type'] ?? 'column') === 'column')
                                                    @if ($sampleHeaders)
                                                        <select wire:model.live="columnMap.{{ $canonical }}.parts.{{ $i }}.value"
                                                                class="flex-1 rounded-xl border border-teal-200 bg-teal-50/40 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer">
                                                            <option value="">— pilih kolom berkas —</option>
                                                            @foreach ($sampleHeaders as $h)
                                                                <option value="{{ $h }}">{{ $h }}</option>
                                                            @endforeach
                                                            @if (($part['value'] ?? '') !== '' && ! in_array($part['value'], $sampleHeaders, true))
                                                                <option value="{{ $part['value'] }}">{{ $part['value'] }} (tidak ada di berkas contoh)</option>
                                                            @endif
                                                        </select>
                                                    @else
                                                        <input type="text" wire:model="columnMap.{{ $canonical }}.parts.{{ $i }}.value" placeholder="judul kolom pada berkas, mis. SOH_QTY"
                                                               class="flex-1 rounded-xl border border-teal-200 bg-teal-50/40 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                                    @endif
                                                @else
                                                    <input type="text" wire:model="columnMap.{{ $canonical }}.parts.{{ $i }}.value" placeholder="teks tetap, mis. UDC"
                                                           class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400">
                                                @endif

                                                <span class="text-[10px] font-semibold px-2 py-1 rounded-md shrink-0 {{ ($part['type'] ?? 'column') === 'column' ? 'bg-teal-50 text-[#0d6d5f] border border-teal-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                                    {{ ($part['type'] ?? 'column') === 'column' ? 'kolom' : 'teks' }}
                                                </span>

                                                <button type="button" wire:click="removePart('{{ $canonical }}', {{ $i }})"
                                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shrink-0 cursor-pointer"
                                                        title="Hapus bagian ini">
                                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @endforeach

                                        <div class="flex items-center gap-4 pt-1 pl-12">
                                            <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 cursor-pointer">
                                                <input type="checkbox" wire:model="columnMap.{{ $canonical }}.nospace" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                                <span>Buang semua spasi</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 cursor-pointer">
                                                <input type="checkbox" wire:model="columnMap.{{ $canonical }}.upper" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                                <span>Jadikan huruf besar</span>
                                            </label>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-400 italic pl-12">Kosong — judul kolomnya dikenali otomatis oleh sistem.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Setelan pembacaan -->
                <div class="rounded-2xl border border-slate-200 p-4 space-y-4">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-600">Setelan Pembacaan</div>

                    @php
                        // Resep Tanggal yang merangkai tiga kolom atau lebih sudah
                        // menyatakan maksudnya sendiri: hari, bulan, tahun.
                        $tanggalParts = collect($columnMap['Tanggal']['parts'] ?? [])
                            ->filter(fn ($p) => ($p['type'] ?? 'column') === 'column' && trim((string) ($p['value'] ?? '')) !== '');
                        $tanggalDariBanyakKolom = $tanggalParts->count() >= 3;
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Format Tanggal Snapshot</label>

                            @if ($tanggalDariBanyakKolom)
                                <div class="w-full rounded-xl border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs text-emerald-900 font-semibold">
                                    Dirakit dari {{ $tanggalParts->count() }} kolom: {{ $tanggalParts->pluck('value')->implode(', ') }}
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                                    Terbaca sendiri dari susunan kolom Tanggal di atas — berurutan sebagai hari, bulan, lalu tahun. Ubah susunannya bila urutannya tidak begitu.
                                </p>
                            @else
                                <input type="text" wire:model.live.debounce.500ms="date_format" placeholder="kosongkan = deteksi otomatis, mis. dd/mm/yyyy"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                <x-format-hint :format="$date_format" month-rule="akhir bulan" />
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                    Tulis bentuknya seperti yang tertulis di berkas: <code>dd</code> hari, <code>mm</code> bulan, <code>yyyy</code> tahun. Isi hanya bila deteksi otomatis meleset atau bentuknya ambigu (mis. <code>01/02/2027</code>). Format tanpa hari dipakai <b>hari terakhir</b> bulan itu.
                                </p>
                            @endif
                            @error('date_format') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Format Tanggal Kedaluwarsa (ED)</label>
                            <input type="text" wire:model.live.debounce.500ms="ed_format" placeholder="kosongkan = deteksi otomatis, mis. mm/yyyy"
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                            <x-format-hint :format="$ed_format" month-rule="tanggal 1" />
                            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                Tulis bentuknya seperti di berkas, mis. <code>mm/yyyy</code> untuk <code>12/2027</code>. Format tanpa hari dipakai <b>tanggal 1</b> bulan itu — memajukan kedaluwarsa lebih aman untuk FEFO daripada mengundurkannya.
                            </p>
                            @error('ed_format') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Batch pengganti <span class="font-medium text-slate-400">(opsional)</span></label>
                            <input type="text" wire:model="default_batch" placeholder="mis. NO-BATCH"
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                                Isi bila berkas grup ini tidak punya kolom batch. Nomor batch adalah bagian identitas baris stok, jadi tanpa nilai pengganti berkasnya akan ditolak. Konsekuensinya: peringatan kedaluwarsa (FEFO) tidak berlaku untuk grup ini.
                            </p>
                            @error('default_batch') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Sheet yang dibaca</label>
                                @if ($sampleSheets)
                                    <select wire:model="sheet_name"
                                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer">
                                        <option value="">Otomatis (sheet Template / sheet pertama)</option>
                                        @foreach ($sampleSheets as $s)
                                            <option value="{{ $s }}">{{ $s }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" wire:model="sheet_name" placeholder="kosongkan = sheet Template / sheet pertama"
                                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                @endif
                                @error('sheet_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Baris header <span class="font-medium text-slate-400">(opsional)</span></label>
                                <input type="number" min="1" max="200" wire:model="header_row" placeholder="kosongkan = deteksi otomatis, mis. dd/mm/yyyy"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                                @error('header_row') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <label class="flex items-start gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" wire:model="skip_nonpositive_qty" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] mt-0.5">
                        <span>
                            Abaikan baris dengan kuantitas ≤ 0
                            <span class="block font-normal text-[11px] text-slate-500 mt-0.5">Untuk grup yang mengirim seluruh isi gudang termasuk stok kosong.</span>
                        </span>
                    </label>
                </div>

                <!-- Pratinjau hasil pembacaan -->
                @if ($preview)
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50/90 px-4 py-2.5 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                            Pratinjau hasil pembacaan berkas contoh
                        </div>
                        <table class="w-full text-[11px] text-left">
                            <thead class="text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-2 font-bold">Kode terbentuk</th>
                                    <th class="px-4 py-2 font-bold">Tanggal</th>
                                    <th class="px-4 py-2 font-bold">Contoh item</th>
                                    <th class="px-4 py-2 font-bold text-right">Qty</th>
                                    <th class="px-4 py-2 font-bold">Batch</th>
                                    <th class="px-4 py-2 font-bold">Master Distributor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($preview as $p)
                                    <tr>
                                        <td class="px-4 py-2 font-mono font-bold text-slate-800">{{ $p['code'] }}</td>
                                        <td class="px-4 py-2 font-mono {{ $p['tanggal'] ? 'text-slate-600' : 'text-rose-600 font-bold' }}">
                                            {{ $p['tanggal'] ?: 'tidak terbaca' }}
                                        </td>
                                        <td class="px-4 py-2 text-slate-600">{{ \Illuminate\Support\Str::limit($p['item'], 32) }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-slate-600">{{ rtrim(rtrim(number_format($p['qty'], 2, ',', '.'), '0'), ',') }}</td>
                                        <td class="px-4 py-2 font-mono text-slate-600">{{ $p['batch'] ?: '—' }}</td>
                                        <td class="px-4 py-2">
                                            @if ($p['known'])
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">
                                                    ✓ {{ $p['distributor'] }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 font-semibold">
                                                    ✕ belum terdaftar
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="px-4 py-2.5 text-[11px] text-slate-500 bg-slate-50/60 border-t border-slate-100 leading-relaxed">
                            Kode yang terbentuk harus sama persis dengan kode di Master Distributor. Bila bertanda merah, perbaiki susunan kolomnya atau tambahkan distributor tersebut di Master Distributor.
                        </div>
                    </div>
                @endif

                <!-- Keanggotaan distributor -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Distributor yang Memakai Template Ini</label>
                    <div class="rounded-2xl border border-slate-200 max-h-56 overflow-y-auto divide-y divide-slate-100">
                        @forelse ($allDistributors as $d)
                            @php $otherGroup = $d->template_group_id && $d->template_group_id !== $editingId; @endphp
                            <label class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50/80 transition cursor-pointer">
                                <input type="checkbox" value="{{ $d->id }}" wire:model="selectedDistributors"
                                       class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                                <span class="font-mono text-[11px] text-slate-500 w-32 shrink-0">{{ $d->distributor_code }}</span>
                                <span class="text-xs font-semibold text-slate-800">{{ $d->name }}</span>
                                @if ($otherGroup)
                                    <span class="ml-auto text-[10px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">sudah di grup lain</span>
                                @endif
                            </label>
                        @empty
                            <div class="px-4 py-6 text-center text-[11px] text-slate-400 italic">Belum ada distributor terdaftar.</div>
                        @endforelse
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Satu distributor hanya boleh berada di satu grup; mencentangnya di sini otomatis memindahkannya dari grup sebelumnya.
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
                                    Sheet <b>{{ $testReport['sheet'] }}</b> · baris header <b>{{ $testReport['header_row'] }}</b> ·
                                    <b>{{ number_format($testReport['total_rows'], 0, ',', '.') }}</b> baris terbaca ·
                                    <b>{{ count($testReport['branches']) }}</b> cabang
                                    @if ($testReport['skipped_zero'] > 0)
                                        · {{ $testReport['skipped_zero'] }} baris berstok ≤ 0 dilewati
                                    @endif
                                </div>

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
