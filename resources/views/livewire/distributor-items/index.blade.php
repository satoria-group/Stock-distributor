<div>
    <!-- Top Filter Bar -->
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama item..."
               class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        <select wire:model.live="distributorFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            <option value="">Semua Distributor</option>
            @foreach ($distributors as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </select>

        <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
            <button type="button" wire:click="$set('mappingFilter', 'all')" class="px-3 py-2 cursor-pointer transition {{ $mappingFilter === 'all' ? 'bg-brand text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}" style="{{ $mappingFilter === 'all' ? 'background-color: #0d6d5f; color: #ffffff;' : '' }}">Semua</button>
            <button type="button" wire:click="$set('mappingFilter', 'mapped')" class="px-3 py-2 border-l border-gray-300 cursor-pointer transition {{ $mappingFilter === 'mapped' ? 'bg-brand text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}" style="{{ $mappingFilter === 'mapped' ? 'background-color: #0d6d5f; color: #ffffff;' : '' }}">Ter-mapping</button>
            <button type="button" wire:click="$set('mappingFilter', 'unmapped')" class="px-3 py-2 border-l border-gray-300 cursor-pointer transition {{ $mappingFilter === 'unmapped' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                Belum ter-mapping @if($unmappedCount) ({{ $unmappedCount }}) @endif
            </button>
        </div>

        @if (count($bulkSuggestions) > 0)
            @can('create', \App\Models\DistributorItem::class)
            <button type="button" wire:click="openBulkModal"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-semibold shadow-2xs transition cursor-pointer"
                    title="Tinjau dan setujui pemetaan rekomendasi dengan 1 klik">
                <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Tinjau & Setujui Saran ({{ count($bulkSuggestions) }} Item)</span>
            </button>
            @endcan
        @endif

        <div class="flex-1"></div>

        @can('create', \App\Models\DistributorItem::class)
        <button type="button" wire:click="openCreate" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium rounded-lg px-4 py-2 cursor-pointer transition" style="background-color: #0d6d5f; color: #ffffff;">
            + Tambah Item Mapping
        </button>
        @endcan
    </div>

    <!-- Table Data -->
    <div class="bg-white border border-[#e7e9e3] rounded-xl overflow-hidden shadow-xs">
        <table class="w-full text-sm">
            <thead class="bg-[#eef1ea] text-[11px] uppercase tracking-wide text-gray-600 font-mono">
                <tr>
                    <th class="text-left px-4 py-2.5">Distributor</th>
                    <th class="text-left px-4 py-2.5">Nama Item (Distributor)</th>
                    <th class="text-left px-4 py-2.5 w-24">Satuan</th>
                    <th class="text-left px-4 py-2.5">Produk Netsuite / Rekomendasi Cerdas</th>
                    <th class="text-left px-4 py-2.5 w-36">Status</th>
                    <th class="text-right px-4 py-2.5 w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e7e9e3]">
                @forelse ($items as $item)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="px-4 py-3 text-gray-700">
                            <span class="font-medium text-xs">{{ $item->distributor->name }}</span>
                            <span class="block text-[10px] font-mono text-gray-400">{{ $item->distributor->distributor_code }}</span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900 text-xs">
                            {{ $item->item_name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                            {{ $item->satuan ?: '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($item->isMapped())
                                <span class="font-mono text-[11px] text-gray-500">{{ $item->netsuiteItem->netsuite_id }}</span>
                                <div class="text-xs font-semibold text-gray-900">{{ $item->netsuiteItem->netsuite_name }}</div>
                            @else
                                @if (isset($suggestions[$item->id]))
                                    @php
                                        $sug = $suggestions[$item->id];
                                        $score = $sug['score'];
                                        $isHigh = $score >= 70;
                                        $isMedium = $score >= 45 && $score < 70;
                                        $isLow = $score < 45;
                                    @endphp
                                    <div class="p-2.5 rounded-lg max-w-md border {{ $isHigh ? 'bg-emerald-50/80 border-emerald-200/90' : ($isMedium ? 'bg-teal-50/70 border-teal-200/90' : 'bg-amber-50/60 border-amber-200/90') }}">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold font-mono {{ $isHigh ? 'text-emerald-900' : ($isMedium ? 'text-teal-900' : 'text-amber-900') }}">
                                                @if ($isLow)
                                                    <span>🔍 Kandidat Terdekat (Skor {{ $score }}%)</span>
                                                @else
                                                    <span>💡 Rekomendasi (Skor {{ $score }}%)</span>
                                                @endif
                                            </span>
                                            @if ($sug['match_type'] === 'historical')
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800 font-mono">Riwayat Cabang Lain</span>
                                            @elseif ($isHigh)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 font-mono">Smart Attribute</span>
                                            @elseif ($isMedium)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-teal-100 text-teal-800 font-mono">Kemiripan Nama</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 font-mono">Skor Rendah</span>
                                            @endif
                                        </div>
                                        <div class="font-mono text-[10px] text-gray-600">{{ $sug['best_match']->netsuite_id }}</div>
                                        <div class="text-xs font-bold text-gray-900 leading-tight mb-2">{{ $sug['best_match']->netsuite_name }}</div>

                                        @can('update', $item)
                                        <button type="button"
                                                wire:click="approveMapping({{ $item->id }}, {{ $sug['best_match']->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-white font-medium text-xs shadow-2xs transition cursor-pointer disabled:opacity-50"
                                                style="background-color: #0d6d5f; color: #ffffff;"
                                                title="Setujui dan petakan produk ini secara instan">
                                            <svg width="13" height="13" style="width: 13px; height: 13px; min-width: 13px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>1-Click Setujui</span>
                                        </button>
                                        @endcan
                                    </div>
                                @else
                                    <div class="text-xs text-gray-400 italic">
                                        Belum ada rekomendasi yang cocok
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($item->isMapped())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-brand-soft text-brand-dark font-bold" style="background-color: #dcece7; color: #07352d;">
                                    Ter-mapping
                                </span>
                            @else
                                @if (isset($suggestions[$item->id]))
                                    @php $sScore = $suggestions[$item->id]['score']; @endphp
                                    @if ($sScore >= 70)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-emerald-100 text-emerald-800 font-bold border border-emerald-200">
                                            Saran Tinggi ({{ $sScore }}%)
                                        </span>
                                    @elseif ($sScore >= 45)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-teal-100 text-teal-800 font-bold border border-teal-200">
                                            Ada Saran ({{ $sScore }}%)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-amber-100 text-amber-800 font-medium border border-amber-200">
                                            Kandidat ({{ $sScore }}%)
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-amber-100 text-amber-800 font-medium">
                                        Belum Mapping
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('update', $item)
                            <button type="button" wire:click="openEdit({{ $item->id }})" class="text-brand hover:underline text-xs font-semibold cursor-pointer" style="color: #0d6d5f;">Edit</button>
                            @endcan
                            @can('delete', $item)
                            <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Hapus mapping item ini?" class="text-red-600 hover:underline text-xs font-medium cursor-pointer">Hapus</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    <!-- Modal 1: Bulk Approval Review Modal -->
    @if ($showBulkModal)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showBulkModal', false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden border border-gray-200 flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-[#f8faf9]">
                <div>
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Tinjau & Setujui Rekomendasi Mapping Massal</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Sistem mendeteksi <b>{{ count($bulkSuggestions) }} item</b> dengan rekomendasi kecocokan yang siap disetujui dalam 1 klik.</p>
                </div>
                <button type="button" wire:click="$set('showBulkModal', false)" class="text-gray-400 hover:text-gray-600 text-lg font-bold p-1 cursor-pointer">✕</button>
            </div>

            <div class="overflow-y-auto p-6 flex-1 divide-y divide-gray-100">
                @if (count($bulkSuggestions) === 0)
                    <div class="text-center py-8 text-gray-400 text-sm">
                        Tidak ada item unmapped yang memiliki rekomendasi dengan skor kepercayaan mencukupi.
                    </div>
                @else
                    <div class="flex items-center justify-between pb-3 text-xs text-gray-600 font-medium">
                        <div>
                            Pilih item yang ingin disetujui pemetaannya (<b>{{ count($selectedBulkIds) }}</b> dari {{ count($bulkSuggestions) }} terpilih):
                        </div>
                        <button type="button" wire:click="toggleAllBulk({{ json_encode(array_keys($bulkSuggestions)) }})" class="text-xs font-semibold text-brand hover:underline cursor-pointer" style="color: #0d6d5f;">
                            {{ count($selectedBulkIds) === count($bulkSuggestions) ? 'Batal Pilih Semua' : 'Pilih Semua' }}
                        </button>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden text-xs">
                        <table class="w-full">
                            <thead class="bg-[#eef1ea] text-gray-600 font-mono uppercase text-[10px]">
                                <tr>
                                    <th class="py-2.5 px-3 w-10 text-center">Pilih</th>
                                    <th class="py-2.5 px-3 text-left">Item Distributor</th>
                                    <th class="py-2.5 px-3 text-left">Rekomendasi Netsuite</th>
                                    <th class="py-2.5 px-3 text-center w-20">Skor</th>
                                    <th class="py-2.5 px-3 text-right w-24">Aksi 1-Click</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($bulkSuggestions as $itemId => $res)
                                    @php $distItem = $allUnmapped->firstWhere('id', $itemId); @endphp
                                    @if ($distItem)
                                        <tr class="hover:bg-emerald-50/30 transition">
                                            <td class="py-2.5 px-3 text-center">
                                                <input type="checkbox" wire:model.live="selectedBulkIds" value="{{ $itemId }}" class="rounded text-brand focus:ring-brand cursor-pointer">
                                            </td>
                                            <td class="py-2.5 px-3 font-semibold text-gray-900">
                                                {{ $distItem->item_name }}
                                                <span class="block text-[10px] text-gray-400 font-mono font-normal">{{ $distItem->distributor->name }} ({{ $distItem->distributor->distributor_code }})</span>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="font-mono text-[10px] text-gray-500">{{ $res['best_match']->netsuite_id }}</span>
                                                <div class="font-bold text-gray-900 text-xs">{{ $res['best_match']->netsuite_name }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-mono">
                                                @if ($res['score'] >= 70)
                                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[11px]">{{ $res['score'] }}%</span>
                                                @elseif ($res['score'] >= 45)
                                                    <span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 font-bold text-[11px]">{{ $res['score'] }}%</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold text-[10px]">{{ $res['score'] }}% (Rendah)</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                <button type="button" wire:click="approveMapping({{ $itemId }}, {{ $res['best_match']->id }})"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-[11px] cursor-pointer transition shadow-2xs"
                                                        style="background-color: #0d6d5f; color: #ffffff;"
                                                        title="1-Click Setujui item ini">
                                                    <span>Setujui</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @error('bulk') <p class="text-xs text-red-600 mt-2 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <button type="button" wire:click="$set('showBulkModal', false)" class="text-xs font-semibold text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200 transition cursor-pointer">
                    Tutup
                </button>
                @if (count($bulkSuggestions) > 0)
                <button type="button" wire:click="approveSelectedBulk"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 text-xs bg-brand hover:bg-brand-dark text-white font-bold px-4 py-2.5 rounded-lg shadow transition cursor-pointer disabled:opacity-50"
                        style="background-color: #0d6d5f; color: #ffffff;">
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>1-Click Setujui Semua ({{ count($selectedBulkIds) }} Item)</span>
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Modal 2: Edit/Create Modal -->
    @if ($showModal)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4" wire:click.self="$set('showModal', false)">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-semibold mb-1">{{ $editingId ? 'Edit Mapping Item' : 'Tambah Mapping Item Baru' }}</h3>
            <p class="text-xs text-gray-500 mb-4 font-mono">{{ $editingId ? $item_name : 'Daftarkan nama item versi distributor' }}</p>

            <form wire:submit="save" class="space-y-4">
                @if (! $editingId)
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Distributor</label>
                    <select wire:model="distributor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Pilih Distributor —</option>
                        @foreach ($distributors as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->distributor_code }})</option>
                        @endforeach
                    </select>
                    @error('distributor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Nama Item (Versi Distributor)</label>
                    <input type="text" wire:model.live.debounce.400ms="item_name" placeholder="Contoh: RINGER LACTATE 500 mL" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('item_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Satuan (Distributor)</label>
                    <input type="text" wire:model="satuan" placeholder="Contoh: BOTOL, BOX, PCS" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                    @error('satuan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Rekomendasi Cerdas Sistem -->
                @if (! empty($modalSuggestions))
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-900">
                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0; color: #0d6d5f;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Rekomendasi Cerdas dari Sistem:</span>
                    </div>
                    <div class="space-y-1.5">
                        @foreach ($modalSuggestions as $rec)
                            <div wire:click="selectSuggestion({{ $rec['item']->id }})"
                                 class="flex items-center justify-between p-2 rounded-lg border cursor-pointer transition {{ $netsuite_item_id === $rec['item']->id ? 'bg-emerald-100 border-emerald-500 shadow-xs' : 'bg-white border-gray-200 hover:bg-emerald-50' }}">
                                <div class="flex-1 pr-2">
                                    <div class="font-mono text-[10px] text-gray-500">{{ $rec['item']->netsuite_id }}</div>
                                    <div class="text-xs font-semibold text-gray-900 leading-tight">{{ $rec['item']->netsuite_name }}</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="inline-block font-mono font-bold text-[11px] px-1.5 py-0.5 rounded {{ $rec['score'] >= 70 ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : ($rec['score'] >= 45 ? 'bg-teal-100 text-teal-900 border border-teal-300' : 'bg-amber-100 text-amber-900 border border-amber-300') }}">
                                        {{ $rec['score'] }}%
                                    </span>
                                    <span class="block text-[9px] text-emerald-800 font-semibold mt-0.5">
                                        {{ $netsuite_item_id === $rec['item']->id ? '✓ Terpilih' : 'Klik Gunakan' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-gray-500 mb-1.5">Produk Netsuite (Pilihan Manual)</label>
                    <select wire:model="netsuite_item_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Belum ter-mapping —</option>
                        @foreach ($netsuiteItems as $ns)
                            <option value="{{ $ns->id }}">{{ $ns->netsuite_id }} — {{ $ns->netsuite_name }}</option>
                        @endforeach
                    </select>
                    @error('netsuite_item_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100 cursor-pointer">Batal</button>
                    <button type="submit" class="text-sm bg-brand hover:bg-brand-dark text-white font-medium px-4 py-2 rounded-lg cursor-pointer" style="background-color: #0d6d5f; color: #ffffff;">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
