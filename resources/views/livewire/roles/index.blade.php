<div>
    @include('partials.flash-alert')

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <p class="text-xs text-slate-500 leading-relaxed max-w-2xl">
            <b class="text-slate-700">Lihat</b> = boleh membuka halaman. <b class="text-slate-700">Kelola</b> = boleh menambah, mengubah, dan menghapus data di halaman itu.
            Role <b class="text-slate-700">Admin</b> selalu punya akses penuh dan tidak bisa diubah, supaya pengaturan ini tidak pernah terkunci.
        </p>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center gap-2 bg-[#0d6d5f] hover:bg-[#07352d] text-white text-xs font-bold rounded-xl px-4 py-2.5 transition shadow-2xs cursor-pointer shrink-0 disabled:opacity-60">
            <svg wire:loading wire:target="save" class="animate-spin" width="13" height="13" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">Simpan Hak Akses</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </button>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-x-auto shadow-xs">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50/90 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5" rowspan="2">Halaman</th>
                    <th class="px-5 py-2.5 text-center border-l border-slate-200" colspan="2">Admin</th>
                    @foreach ($roles as $role)
                        <th class="px-5 py-2.5 text-center border-l border-slate-200" colspan="2">{{ ucfirst($role->name) }}</th>
                    @endforeach
                </tr>
                <tr class="text-[10px]">
                    <th class="px-3 py-2 text-center border-l border-slate-200 font-semibold">Lihat</th>
                    <th class="px-3 py-2 text-center font-semibold">Kelola</th>
                    @foreach ($roles as $role)
                        <th class="px-3 py-2 text-center border-l border-slate-200 font-semibold">Lihat</th>
                        <th class="px-3 py-2 text-center font-semibold">Kelola</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($pages as $page)
                    @php
                        $viewKey = \App\Livewire\Roles\Index::key($page['view']);
                        $manageKey = $page['manage'] ? \App\Livewire\Roles\Index::key($page['manage']) : null;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-3 font-bold text-slate-900">{{ $page['label'] }}</td>

                        <td class="px-3 py-3 text-center border-l border-slate-100">
                            <input type="checkbox" checked disabled class="rounded text-slate-400 cursor-not-allowed" title="Admin selalu punya akses">
                        </td>
                        <td class="px-3 py-3 text-center">
                            @if ($manageKey)
                                <input type="checkbox" checked disabled class="rounded text-slate-400 cursor-not-allowed" title="Admin selalu punya akses">
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>

                        @foreach ($roles as $role)
                            <td class="px-3 py-3 text-center border-l border-slate-100">
                                <input type="checkbox" wire:model.live="matrix.{{ $role->name }}.{{ $viewKey }}"
                                       class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] cursor-pointer">
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($manageKey)
                                    <input type="checkbox" wire:model.live="matrix.{{ $role->name }}.{{ $manageKey }}"
                                           class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f] cursor-pointer">
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
