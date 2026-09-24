<div>
    @include('partials.flash-alert')

    <!-- Tab: daftar distributor vs grup usahanya -->
    <div class="flex items-center gap-1.5 mb-5 p-1.5 bg-slate-100/70 rounded-2xl w-fit">
        <button type="button" wire:click="setTab('distributors')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $tab === 'distributors' ? 'bg-white text-[#07352d] shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
            Distributor
        </button>
        <button type="button" wire:click="setTab('groups')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2 {{ $tab === 'groups' ? 'bg-white text-[#07352d] shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
            <span>Grup Distributor</span>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-slate-200 text-slate-600">{{ $groups->count() }}</span>
        </button>
    </div>

    @if ($ungroupedCount > 0)
        <!-- Sisa pekerjaan ditampilkan terang-terangan: tanpa ini, distributor
             tak bergrup akan diam-diam menumpuk di kantong "Lainnya". -->
        <div class="mb-5 bg-amber-50/80 border border-amber-200 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white shrink-0 mt-0.5 bg-amber-500">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="text-xs text-slate-700">
                    <h4 class="font-bold text-amber-900">{{ number_format($ungroupedCount, 0, ',', '.') }} distributor belum punya grup</h4>
                    <p class="text-amber-800/90 mt-0.5 leading-relaxed">
                        Mereka terhitung sebagai <b>Lainnya</b> di dashboard. Distributor yang memang berdiri sendiri boleh dibiarkan begini.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" wire:click="$set('groupFilter', 'none')"
                        class="text-[11px] font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 px-3 py-1.5 rounded-xl transition cursor-pointer">
                    Lihat daftarnya
                </button>
                <button type="button" wire:click="suggestGroups"
                        class="text-[11px] font-bold text-white bg-amber-600 hover:bg-amber-700 px-3 py-1.5 rounded-xl transition cursor-pointer shadow-2xs">
                    Sarankan grup dari kode
                </button>
            </div>
        </div>
    @endif

    @if ($suggestions)
        <!-- Usulan ditinjau manusia dulu; awalan kode tidak pernah jadi aturan sistem -->
        <div class="mb-5 bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h4 class="font-bold text-slate-900 text-xs">Usulan grup dari awalan kode</h4>
                    <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                        Tinjau dulu, lalu buat yang memang benar. Setelah dibuat, keanggotaannya tersimpan sebagai data — awalan kode tidak lagi berpengaruh.
                    </p>
                </div>
                <button type="button" wire:click="dismissSuggestions" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 cursor-pointer shrink-0">Tutup</button>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $s)
                    <button type="button" wire:click="applySuggestion('{{ $s['prefix'] }}')"
                            wire:confirm="Buat grup {{ $s['name'] }} berisi {{ $s['count'] }} distributor berawalan kode {{ $s['prefix'] }}?"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-[#0d6d5f] hover:text-white hover:border-[#0d6d5f] transition cursor-pointer group">
                        <span class="font-mono font-bold text-xs">{{ $s['name'] }}</span>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md bg-white/80 text-slate-600 group-hover:text-slate-800">{{ $s['count'] }} distributor</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($tab === 'distributors')
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
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-96">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, atau email whitelist..."
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
            </div>

            <button type="button"
                    wire:click="resetFilters"
                    wire:loading.attr="disabled"
                    title="{{ $this->filtersActive ? 'Reset pencarian dan penyaring' : 'Penyaring dalam posisi default' }}"
                    class="w-[38px] h-[38px] flex items-center justify-center rounded-xl border transition cursor-pointer shrink-0 {{ $this->filtersActive ? 'bg-emerald-50 hover:bg-emerald-100 text-[#0d6d5f] border-emerald-300 shadow-2xs hover:scale-105 active:scale-95' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-400 border-slate-200' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            <select wire:model.live="groupFilter"
                    class="w-48 rounded-xl border border-slate-200 px-3 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer shrink-0">
                <option value="">Semua grup</option>
                <option value="none">Belum bergrup ({{ $ungroupedCount }})</option>
                @foreach ($groupOptions as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter"
                    class="w-44 rounded-xl border border-slate-200 px-3 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer shrink-0">
                <option value="">Semua status</option>
                <option value="active">Aktif ({{ $activeCount }})</option>
                <option value="inactive">Nonaktif ({{ $inactiveCount }})</option>
            </select>
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

    @if ($selected)
        <!-- Batang penetapan massal: 289 distributor mustahil diurus satu per satu -->
        <div class="mb-4 bg-[#07352d] rounded-2xl p-3.5 flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <span class="text-xs font-bold text-white">{{ count($selected) }} distributor dipilih</span>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model="bulkGroupId"
                        class="rounded-xl border-0 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-white/40 cursor-pointer">
                    <option value="">— lepas dari grup —</option>
                    @foreach ($groupOptions as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="assignSelectedToGroup"
                        class="text-xs font-bold text-[#07352d] bg-white hover:bg-emerald-50 px-4 py-2 rounded-xl transition cursor-pointer">
                    Tetapkan grup
                </button>

                <span class="w-px h-6 bg-white/25 mx-1"></span>

                <button type="button" wire:click="bulkSetActive(true)"
                        wire:confirm="Aktifkan {{ count($selected) }} distributor yang dipilih?"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-50 bg-emerald-600/80 hover:bg-emerald-500 px-3.5 py-2 rounded-xl transition cursor-pointer">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Aktifkan
                </button>
                <button type="button" wire:click="bulkSetActive(false)"
                        wire:confirm="Nonaktifkan {{ count($selected) }} distributor yang dipilih? Pengunggahan data stok mereka akan ditolak, termasuk lewat otomasi email."
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-200 bg-white/15 hover:bg-white/25 px-3.5 py-2 rounded-xl transition cursor-pointer">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Nonaktifkan
                </button>

                <button type="button" wire:click="clearSelection"
                        class="text-xs font-semibold text-emerald-100 hover:text-white px-2 py-2 cursor-pointer">
                    Batal
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3.5 w-10">
                        <button type="button" wire:click="selectAllOnPage({{ json_encode($distributors->pluck('id')->all()) }})"
                                class="text-[10px] font-bold text-slate-500 hover:text-[#0d6d5f] cursor-pointer" title="Centang semua di halaman ini">
                            Semua
                        </button>
                    </th>
                    <th class="px-5 py-3.5 w-36">Kode</th>
                    <th class="px-5 py-3.5">Nama Distributor</th>
                    <th class="px-5 py-3.5">Email Whitelist</th>
                    <th class="px-5 py-3.5 w-40">Grup Usaha</th>
                    <th class="px-5 py-3.5 w-44">Bentuk Berkas</th>
                    <th class="px-5 py-3.5 w-28 text-center">Status</th>
                    <th class="px-5 py-3.5 w-32 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($distributors as $distributor)
                    <tr class="hover:bg-slate-50/80 transition {{ in_array($distributor->id, $selected) ? 'bg-emerald-50/50' : '' }}">
                        <td class="px-4 py-3.5">
                            <input type="checkbox" value="{{ $distributor->id }}" wire:model.live="selected"
                                   class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] cursor-pointer">
                        </td>
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
                        <td class="px-5 py-3.5 text-xs">
                            @if ($distributor->group)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-50 text-slate-700 border border-slate-200 text-[11px] font-semibold">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $distributor->group->colorOrDefault() }};"></span>
                                    {{ $distributor->group->name }}
                                </span>
                            @else
                                <span class="text-[11px] text-amber-700 italic">Belum bergrup</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs">
                            @if ($distributor->templateGroup)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-teal-50 text-[#0d6d5f] border border-teal-200/80 text-[11px] font-semibold shadow-2xs">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                    {{ $distributor->templateGroup->name }}
                                </span>
                            @elseif ($distributor->group?->templateGroup)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-50 text-slate-500 border border-slate-200 text-[11px]">
                                    {{ $distributor->group->templateGroup->name }}
                                    <span class="text-slate-400">(dari grup)</span>
                                </span>
                            @else
                                <span class="text-[11px] text-slate-400 italic">Deteksi otomatis</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if ($distributor->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                            @can('update', $distributor)
                            <button type="button" wire:click="toggleDistributorActive({{ $distributor->id }})"
                                    wire:confirm="{{ $distributor->is_active ? 'Nonaktifkan '.$distributor->name.'? Pengunggahan data stoknya akan ditolak, termasuk lewat otomasi email.' : 'Aktifkan kembali '.$distributor->name.'?' }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl border transition shadow-2xs cursor-pointer {{ $distributor->is_active ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white border-emerald-200/60' : 'text-slate-500 bg-slate-100 hover:bg-slate-600 hover:text-white border-slate-200' }}"
                                    title="{{ $distributor->is_active ? 'Nonaktifkan distributor ini' : 'Aktifkan distributor ini' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $distributor->is_active ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' }}"/>
                                </svg>
                            </button>
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
                        <td colspan="8" class="px-5 py-14 text-center text-slate-400">
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

    @endif

    @if ($tab === 'groups')
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <p class="text-xs text-slate-500 leading-relaxed max-w-2xl">
                Grup usaha menentukan pengelompokan angka di <b>Dashboard</b>, dan sekaligus menentukan <b>bentuk berkas Excel</b> yang dipakai seluruh cabangnya. Satu cabang yang formatnya menyimpang tetap bisa diatur sendiri lewat form distributornya.
            </p>
            @can('create', \App\Models\Distributor::class)
            <button wire:click="openGroupCreate" class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer shrink-0">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Grup</span>
            </button>
            @endcan
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 w-16 text-center">Urutan</th>
                        <th class="px-5 py-3.5">Nama Grup</th>
                        <th class="px-5 py-3.5 w-48">Bentuk Berkas Excel</th>
                        <th class="px-5 py-3.5 w-32 text-center">Anggota</th>
                        <th class="px-5 py-3.5 w-28 text-center">Status</th>
                        <th class="px-5 py-3.5 w-36 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($groups as $g)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3.5 text-center font-mono text-slate-400">{{ $g->sort_order }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full shrink-0 border border-slate-200" style="background: {{ $g->colorOrDefault() }};"></span>
                                    <span class="font-bold text-xs {{ $g->is_active ? 'text-slate-900' : 'text-slate-400' }}">{{ $g->name }}</span>
                                </div>
                                @if ($g->notes)
                                    <div class="text-[11px] text-slate-500 mt-0.5 ml-5">{{ $g->notes }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($g->templateGroup)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-teal-50 text-[#0d6d5f] border border-teal-200/80 text-[11px] font-semibold">
                                        {{ $g->templateGroup->name }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Deteksi otomatis</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button type="button" wire:click="setTab('distributors')" x-on:click="$wire.set('groupFilter', '{{ $g->id }}')"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold transition cursor-pointer {{ $g->distributors_count > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}"
                                        title="Lihat anggotanya">
                                    {{ $g->distributors_count }} distributor
                                </button>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($g->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                @can('create', \App\Models\Distributor::class)
                                <button type="button" wire:click="toggleGroupActive({{ $g->id }})"
                                        wire:confirm="{{ $g->is_active ? 'Nonaktifkan grup '.$g->name.'? Anggotanya akan terhitung sebagai Lainnya di dashboard.' : 'Aktifkan kembali grup '.$g->name.'?' }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl border transition shadow-2xs cursor-pointer {{ $g->is_active ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white border-emerald-200/60' : 'text-slate-500 bg-slate-100 hover:bg-slate-600 hover:text-white border-slate-200' }}"
                                        title="{{ $g->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $g->is_active ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' }}"/>
                                    </svg>
                                </button>
                                <button type="button" wire:click="openGroupEdit({{ $g->id }})"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 transition shadow-2xs cursor-pointer"
                                        title="Edit grup">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button type="button" wire:click="deleteGroup({{ $g->id }})"
                                        wire:confirm="Hapus grup {{ $g->name }}? {{ $g->distributors_count }} distributor anggotanya tidak ikut terhapus, hanya dilepas dari grup."
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shadow-2xs cursor-pointer"
                                        title="Hapus grup">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center text-slate-400">
                                <div class="text-3xl mb-2">🏷️</div>
                                <div class="font-bold text-slate-700 text-sm">Belum ada grup distributor</div>
                                <div class="text-xs text-slate-400 mt-0.5">Tanpa grup, seluruh distributor terhitung sebagai "Lainnya" di dashboard.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif


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
                @php
                    $chosenGroup = $distributor_group_id ? $groups->firstWhere('id', (int) $distributor_group_id) : null;
                    $inheritedTemplate = $chosenGroup?->templateGroup?->name;
                @endphp

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
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Email Whitelist <span class="font-medium normal-case text-slate-400">(pengecualian, opsional)</span></label>
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
                        @if ($chosenGroup?->sender_email)
                            Biarkan kosong untuk mengikuti grup <b>{{ $chosenGroup->name }}</b> — <code>{{ $chosenGroup->sender_email }}</code>. Isi hanya bila cabang ini mengirim dari alamat yang berbeda dari grupnya.
                        @else
                            Alamat email resmi yang diizinkan mengirim laporan stok cabang ini. Bila seluruh cabang segrup memakai alamat yang sama, lebih baik didaftarkan sekali di <b>grup</b>-nya.
                        @endif
                    </p>
                    @error('sender_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Grup Usaha</label>
                    <select wire:model.live="distributor_group_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer">
                        <option value="">Belum bergrup</option>
                        @foreach ($groupOptions as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Menentukan pengelompokan di <b>Dashboard</b>, sekaligus bentuk berkas Excel yang dipakai — keduanya ikut grup.
                    </p>
                    @error('distributor_group_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Bentuk Berkas Excel <span class="font-medium normal-case text-slate-400">(pengecualian, opsional)</span>
                    </label>
                    <select wire:model="template_group_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer">
                        <option value="">
                            @if ($inheritedTemplate)
                                Ikut grup {{ $chosenGroup->name }} — {{ $inheritedTemplate }}
                            @elseif ($chosenGroup)
                                Ikut grup {{ $chosenGroup->name }} — deteksi otomatis
                            @else
                                Deteksi otomatis
                            @endif
                        </option>
                        @foreach ($templateGroups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Biarkan mengikuti grup. Isi hanya bila cabang ini mengirim berkas dengan susunan kolom yang <b>berbeda dari grupnya</b>. Susunan kolomnya diatur di menu <b>Format Berkas Excel</b>.
                    </p>
                    @error('template_group_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
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
    @if ($showGroupModal)
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showGroupModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white shrink-0" style="background: {{ $group_color ?: '#0d6d5f' }};">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">{{ $editingGroupId ? 'Edit Grup Distributor' : 'Tambah Grup Distributor' }}</h3>
                </div>
                <button type="button" wire:click="$set('showGroupModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1 rounded-lg">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveGroup" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama Grup</label>
                        <input type="text" wire:model="group_name" placeholder="contoh: UDC"
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        <p class="text-[11px] text-slate-500 mt-1.5">Nama ini yang muncul sebagai filter dan label di dashboard.</p>
                        @error('group_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Urutan</label>
                        <input type="number" min="0" max="999" wire:model="group_sort_order" placeholder="0"
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                        <p class="text-[11px] text-slate-500 mt-1.5">Urutan tampil di chart.</p>
                        @error('group_sort_order') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Warna di Chart</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\DistributorGroup::COLORS as $hex => $label)
                            <button type="button" wire:click="$set('group_color', '{{ $hex }}')"
                                    title="{{ $label }}"
                                    class="w-8 h-8 rounded-xl border-2 transition cursor-pointer {{ $group_color === $hex ? 'border-slate-900 scale-110' : 'border-slate-200 hover:scale-105' }}"
                                    style="background: {{ $hex }};"></button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5">Warna tetap grup ini di seluruh chart dashboard.</p>
                    @error('group_color') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Bentuk Berkas Excel</label>
                    <select wire:model="group_template_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] cursor-pointer">
                        <option value="">Deteksi otomatis</option>
                        @foreach ($templateGroups as $tg)
                            <option value="{{ $tg->id }}">{{ $tg->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Berlaku untuk <b>seluruh cabang</b> grup ini — satu kali setel, tidak perlu diulang per cabang. Susunan kolomnya diatur di menu <b>Format Berkas Excel</b>.
                    </p>
                    @error('group_template_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Email Whitelist Pengirim</label>
                    <input type="text" wire:model="group_sender_email" placeholder="laporan@kftd.co.id atau @kftd.co.id"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Berlaku untuk <b>seluruh cabang</b> grup ini. Satu berkas berisi banyak cabang selalu datang dari satu alamat, jadi cukup didaftarkan sekali di sini. Boleh beberapa email dipisah koma, atau domain wildcard seperti <code>@kftd.co.id</code>.
                    </p>
                    @error('group_sender_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Catatan <span class="font-medium normal-case text-slate-400">(opsional)</span></label>
                    <input type="text" wire:model="group_notes" placeholder="contoh: United Dico Citas"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('group_notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" wire:model="group_is_active" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] mt-0.5">
                    <span>
                        Aktif
                        <span class="block font-normal text-[11px] text-slate-500 mt-0.5">Grup nonaktif tidak muncul sebagai filter, dan anggotanya terhitung sebagai "Lainnya" di dashboard.</span>
                    </span>
                </label>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('showGroupModal', false)" class="text-xs font-semibold text-slate-600 px-4 py-2.5 rounded-xl hover:bg-slate-100 cursor-pointer">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveGroup"
                            class="inline-flex items-center gap-2 text-xs bg-[#0d6d5f] hover:bg-[#07352d] text-white font-bold px-5 py-2.5 rounded-xl shadow-2xs cursor-pointer transition disabled:opacity-60 disabled:cursor-wait">
                        <span wire:loading.remove wire:target="saveGroup">Simpan</span>
                        <span wire:loading wire:target="saveGroup">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
