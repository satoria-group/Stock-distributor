{{-- Tab dalam satu halaman. $tabs = [kunci => label]; komponen induk wajib punya properti publik $tab. --}}
@if (count($tabs) > 1)
<div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1 text-xs mb-6 shadow-2xs">
    @foreach ($tabs as $key => $label)
        <button type="button" wire:click="$set('tab', '{{ $key }}')"
                class="px-4 py-2 rounded-lg font-semibold transition cursor-pointer {{ $tab === $key ? 'bg-[#0d6d5f] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            {{ $label }}
        </button>
    @endforeach
</div>
@endif
