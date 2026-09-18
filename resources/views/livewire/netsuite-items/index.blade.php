<div>
    @include('partials.flash-alert')

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama produk..."
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
            </div>

            <!-- Segmented Price Status Filter -->
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs shadow-2xs shrink-0">
                <button type="button" wire:click="setPriceFilter('all')"
                        class="px-3 py-1.5 rounded-lg cursor-pointer transition font-semibold {{ $priceFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua
                    <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded-md {{ $priceFilter === 'all' ? 'bg-slate-100 text-slate-700' : 'bg-white/80 text-slate-500' }} font-mono font-bold">{{ $totalCount }}</span>
                </button>
                <button type="button" wire:click="setPriceFilter('with_price')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg cursor-pointer transition font-semibold {{ $priceFilter === 'with_price' ? 'bg-[#0d6d5f] text-white shadow-2xs font-bold' : 'text-emerald-700 hover:bg-emerald-50' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Sudah Ada Harga
                    <span class="text-[11px] px-1.5 py-0.5 rounded-md {{ $priceFilter === 'with_price' ? 'bg-white/20 text-white' : 'bg-emerald-100/80 text-emerald-800' }} font-mono font-bold">{{ $withPriceCount }}</span>
                </button>
                <button type="button" wire:click="setPriceFilter('without_price')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg cursor-pointer transition font-semibold {{ $priceFilter === 'without_price' ? 'bg-amber-600 text-white shadow-2xs font-bold' : 'text-amber-800 hover:bg-amber-50' }}">
                    Belum Ada Harga
                    <span class="text-[11px] px-1.5 py-0.5 rounded-md {{ $priceFilter === 'without_price' ? 'bg-white/20 text-white' : 'bg-amber-100/80 text-amber-800' }} font-mono font-bold">{{ $withoutPriceCount }}</span>
                </button>
            </div>

            <!-- Reset Filter Button (Persegi) -->
            @php
                $isNetsuiteFiltered = $priceFilter !== 'all' || !empty($search) || $sortBy !== 'netsuite_name' || $sortDirection !== 'asc';
            @endphp
            <button type="button"
                    wire:click="resetFilters"
                    wire:loading.attr="disabled"
                    title="{{ $isNetsuiteFiltered ? 'Reset semua filter, pencarian, dan sortir ke default' : 'Filter dalam posisi default' }}"
                    class="w-[36px] h-[36px] flex items-center justify-center rounded-xl border transition cursor-pointer shrink-0 {{ $isNetsuiteFiltered ? 'bg-emerald-50 hover:bg-emerald-100 text-[#0d6d5f] border-emerald-300 shadow-2xs hover:scale-105 active:scale-95' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-400 border-slate-200' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        @can('create', \App\Models\NetsuiteItem::class)
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer shrink-0">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Produk</span>
        </button>
        @endcan
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200 select-none">
                <tr>
                    <th wire:click="sortByColumn('netsuite_id')" class="px-5 py-3.5 w-40 cursor-pointer hover:text-slate-800 transition">
                        <div class="inline-flex items-center gap-1">
                            <span>Netsuite ID</span>
                            @if ($sortBy === 'netsuite_id')
                                <span class="text-[#0d6d5f] font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="text-slate-300">↕</span>
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortByColumn('netsuite_name')" class="px-5 py-3.5 cursor-pointer hover:text-slate-800 transition">
                        <div class="inline-flex items-center gap-1">
                            <span>Nama Produk</span>
                            @if ($sortBy === 'netsuite_name')
                                <span class="text-[#0d6d5f] font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="text-slate-300">↕</span>
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortByColumn('price')" class="px-5 py-3.5 w-40 text-right cursor-pointer hover:text-slate-800 transition">
                        <div class="inline-flex items-center justify-end gap-1 w-full">
                            <span>Harga DPL (Rp)</span>
                            @if ($sortBy === 'price')
                                <span class="text-[#0d6d5f] font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="text-slate-300">↕</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-5 py-3.5 w-32 text-center">Satuan Default</th>
                    <th class="px-5 py-3.5 w-28 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($items as $item)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-3.5 font-mono text-slate-700 font-semibold text-xs">{{ $item->netsuite_id }}</td>
                        <td class="px-5 py-3.5 font-bold text-slate-900 text-xs">{{ $item->netsuite_name }}</td>
                        <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-slate-800">
                            @if ($item->unit_price > 0)
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            @else
                                <span class="text-slate-400 font-normal italic">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center font-mono">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px] border border-slate-200/60">
                                {{ $item->default_satuan ?: '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-1.5">
                            @can('update', $item)
                            <button type="button" wire:click="openEdit({{ $item->id }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-[#0d6d5f] bg-[#e6f4f1] hover:bg-[#0d6d5f] hover:text-white border border-teal-200/60 transition shadow-2xs cursor-pointer"
                                    title="Edit Produk ({{ $item->netsuite_name }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @endcan
                            @can('delete', $item)
                            <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Hapus produk {{ $item->netsuite_name }}?"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/60 transition shadow-2xs cursor-pointer"
                                    title="Hapus Produk">
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
                            <div class="text-3xl mb-2">📦</div>
                            <div class="font-bold text-slate-700 text-sm">Belum ada data produk</div>
                            <div class="text-xs text-slate-400 mt-0.5">Produk master belum terdaftar atau tidak cocok dengan filter.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-2">
        {{ $items->links('livewire::tailwind') }}
    </div>

    @if ($showModal)
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 border border-slate-200">
            <h3 class="text-base font-bold text-slate-900 mb-4">{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</h3>
            <form wire:submit="save" class="space-y-4 text-xs">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Netsuite ID</label>
                    <input type="text" wire:model="netsuite_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('netsuite_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama Produk</label>
                    <input type="text" wire:model="netsuite_name" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    @error('netsuite_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Harga Acuan DPL (Rp)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">Rp</span>
                        <input type="number" step="any" wire:model="price" placeholder="mis. 14324" class="w-full rounded-xl border border-slate-200 pl-10 pr-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                    </div>
                    @error('price') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Satuan Default</label>
                    <input type="text" wire:model="default_satuan" placeholder="mis. BOTOL, PCS, BOX" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f]">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
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

