@props([
    // Format yang sedang diketik operator, dalam notasi dd/mm/yyyy (boleh kosong).
    'format' => null,
    // Hari yang dipakai bila formatnya tidak menyebut hari — berbeda antara
    // tanggal snapshot ('akhir bulan') dan ED ('tanggal 1').
    'monthRule' => 'akhir bulan',
])

@php
    use App\Models\DistributorTemplateGroup as TemplateGroup;

    $format = trim((string) $format);
    $valid = $format !== '' && TemplateGroup::formatIsValid($format);
    $monthOnly = $valid && TemplateGroup::formatIsMonthOnly($format);

    // Contoh hasil dihitung dari format yang sedang diketik, supaya operator
    // melihat akibat ketikannya sebelum menyimpan — bukan setelah impor gagal.
    $example = null;
    if ($valid) {
        try {
            $example = \Carbon\Carbon::create(2027, 12, 31)?->format(TemplateGroup::toPhpFormat($format));
        } catch (\Throwable) {
            $example = null;
        }
    }
@endphp

@if ($format === '')
    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
        <span class="font-semibold text-slate-600">Contoh:</span>
        @foreach (TemplateGroup::FORMAT_EXAMPLES as $fmt => $sample)
            <span class="whitespace-nowrap">
                <code class="font-mono text-slate-700">{{ $fmt }}</code>
                <span class="text-slate-400">→</span> {{ $sample }}
            </span>
        @endforeach
    </div>
@elseif ($valid)
    <div class="mt-1.5 text-[11px] font-semibold rounded-lg px-2.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200">
        Terbaca sebagai <span class="font-mono">{{ $example }}</span>
        @if ($monthOnly)
            <span class="font-normal">— tidak menyebut hari, jadi dipakai <b>{{ $monthRule }}</b>.</span>
        @endif
    </div>
@else
    <div class="mt-1.5 text-[11px] rounded-lg px-2.5 py-1.5 bg-rose-50 text-rose-700 border border-rose-200">
        <span class="font-semibold">Format <span class="font-mono">{{ $format }}</span> tidak dikenali.</span>
        Tulis <code class="font-mono">dd</code> untuk hari, <code class="font-mono">mm</code> bulan,
        <code class="font-mono">yyyy</code> tahun — mis. <code class="font-mono">dd/mm/yyyy</code> atau
        <code class="font-mono">mm/yyyy</code>. Bulan dan tahun wajib ada.
    </div>
@endif
