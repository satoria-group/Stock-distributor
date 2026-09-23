<?php

namespace App\Livewire\TemplateGroups;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use App\Support\Search;
use App\Support\StockColumnRecipe;
use App\Support\StockHeaderResolver;
use App\Support\StockRowReader;
use App\Support\StockTemplateColumns;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Pengelolaan Grup Template Excel.
 *
 * Satu grup = satu bentuk berkas Excel yang dipakai bersama oleh sekelompok
 * distributor (mis. seluruh cabang UDC). Operator menyusun "resep" tiap kolom
 * sistem dari kolom-kolom berkas milik grup itu, sehingga distributor tidak
 * perlu lagi dipaksa memakai template baku kita.
 *
 * Berkas contoh yang diunggah tidak disimpan — hanya judul kolom dan beberapa
 * baris pertamanya yang ditahan di memori komponen, supaya pratinjau bisa
 * memperlihatkan hasil resep secara langsung sambil diedit.
 */
#[Layout('layouts.app', ['title' => 'Grup Template Excel', 'subtitle' => 'Petakan judul kolom berkas Excel tiap grup distributor ke kolom standar sistem.'])]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    /** Banyaknya baris contoh yang ditahan untuk pratinjau. */
    private const PREVIEW_ROWS = 50;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $notes = null;

    public ?string $sheet_name = null;

    public ?string $header_row = null;

    public ?string $date_format = null;

    public ?string $ed_format = null;

    public ?string $default_batch = null;

    public bool $skip_nonpositive_qty = false;

    /**
     * Resep tiap kolom kanonik, dalam bentuk yang enak diikat ke form.
     *
     * Bagian resep disimpan sebagai {type: column|text, value: ...} di sini,
     * lalu diterjemahkan ke bentuk penyimpanan saat disimpan — Livewire jauh
     * lebih mudah mengikat kunci yang tetap daripada kunci yang berganti nama.
     *
     * @var array<string, array{parts: array<int, array{type: string, value: string}>, nospace: bool, upper: bool}>
     */
    public array $columnMap = [];

    /** @var array<int, int> id distributor yang memakai grup ini */
    public array $selectedDistributors = [];

    /** Berkas contoh untuk membaca judul kolom (tidak ikut disimpan). */
    public $sampleFile = null;

    /** @var array<int, string> judul kolom hasil baca berkas contoh */
    public array $sampleHeaders = [];

    /** @var array<int, string> nama sheet pada berkas contoh */
    public array $sampleSheets = [];

    /** @var array<int, array<int, string>> beberapa baris pertama untuk pratinjau */
    public array $sampleRows = [];

    public ?string $sampleInfo = null;

    public bool $sampleOk = false;

    /** Status aktif grup; grup nonaktif tidak ikut dicobakan saat membaca berkas. */
    public bool $is_active = true;

    /** Berkas untuk uji coba pembacaan (tidak disimpan, tidak mengubah data). */
    public $testFile = null;

    /** @var array<string, mixed> hasil uji coba terakhir */
    public array $testReport = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', DistributorTemplateGroup::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', DistributorTemplateGroup::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $group = DistributorTemplateGroup::findOrFail($id);
        Gate::authorize('update', $group);

        $this->resetForm();
        $this->editingId = $group->id;
        $this->name = $group->name;
        $this->is_active = (bool) $group->is_active;
        $this->notes = $group->notes;
        $this->sheet_name = $group->sheet_name;
        $this->header_row = $group->header_row ? (string) $group->header_row : null;
        $this->date_format = $group->date_format;
        $this->ed_format = $group->ed_format;
        $this->default_batch = $group->default_batch;
        $this->skip_nonpositive_qty = (bool) $group->skip_nonpositive_qty;

        foreach ($group->recipes() as $canonical => $recipe) {
            $parts = [];
            foreach ($recipe->parts as $part) {
                $parts[] = isset($part['column'])
                    ? ['type' => 'column', 'value' => $part['column']]
                    : ['type' => 'text', 'value' => $part['text']];
            }

            $this->columnMap[$canonical] = [
                'parts' => $parts,
                'nospace' => $recipe->nospace,
                'upper' => $recipe->upper,
            ];
        }

        $this->selectedDistributors = $group->distributors()->pluck('distributors.id')->all();
        $this->showModal = true;
    }

    public function addPart(string $canonical, string $type = 'column'): void
    {
        if (! in_array($canonical, StockTemplateColumns::all(), true)) {
            return;
        }

        $this->columnMap[$canonical]['parts'][] = [
            'type' => $type === 'text' ? 'text' : 'column',
            'value' => '',
        ];
    }

    public function removePart(string $canonical, int $index): void
    {
        unset($this->columnMap[$canonical]['parts'][$index]);
        $this->columnMap[$canonical]['parts'] = array_values($this->columnMap[$canonical]['parts']);
    }

    /**
     * Membaca berkas contoh: judul kolom, daftar sheet, dan beberapa baris
     * pertama untuk pratinjau.
     *
     * Pengisian resep otomatis memakai resolver yang sama dengan proses upload,
     * jadi apa yang terlihat di sini persis apa yang nanti dikenali saat impor.
     */
    public function updatedSampleFile(): void
    {
        $this->validate([
            'sampleFile' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ], [], ['sampleFile' => 'berkas contoh']);

        try {
            $spreadsheet = $this->loadSpreadsheet($this->sampleFile);
        } catch (\Throwable $e) {
            $this->addError('sampleFile', 'Berkas contoh gagal dibaca: '.$e->getMessage());
            $this->sampleFile = null;

            return;
        }

        $this->sampleSheets = $spreadsheet->getSheetNames();

        $sheet = ($this->sheet_name ? $spreadsheet->getSheetByName($this->sheet_name) : null)
            ?? $spreadsheet->getSheetByName('Template')
            ?? $spreadsheet->getActiveSheet();

        $data = $sheet->toArray(null, true, false, false);
        $resolved = (new StockHeaderResolver)->resolve($data, $this->previewGroup());

        $this->sampleHeaders = array_values(array_unique(array_filter(
            array_map('trim', $resolved['headers']),
            fn ($h) => $h !== ''
        )));

        if ($this->sampleHeaders === []) {
            $this->addError('sampleFile', 'Tidak ada judul kolom yang terbaca pada berkas contoh.');
            $this->sampleFile = null;

            return;
        }

        // Isi otomatis hanya kolom yang resepnya masih kosong: susunan yang
        // sudah dirakit operator lebih tahu daripada tebakan sinonim.
        foreach ($resolved['col'] as $canonical => $idx) {
            if ($idx < 0 || ($this->columnMap[$canonical]['parts'] ?? []) !== []) {
                continue;
            }

            $header = trim((string) ($resolved['headers'][$idx] ?? ''));
            if ($header !== '') {
                $this->columnMap[$canonical]['parts'] = [['type' => 'column', 'value' => $header]];
            }
        }

        $this->sheet_name = $this->sheet_name ?: $sheet->getTitle();
        $this->header_row = (string) ($resolved['header_row'] + 1);

        // Baris contoh disimpan sebagai teks: pratinjau hanya perlu
        // memperlihatkan hasil, dan array teks aman dibawa bolak-balik
        // Livewire tanpa tipe aneh dari PhpSpreadsheet.
        $this->sampleRows = [];
        foreach (array_slice($data, $resolved['header_row'] + 1, self::PREVIEW_ROWS) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->sampleRows[] = array_map(fn ($v) => $v === null ? '' : (string) $v, $row);
        }

        $missing = $resolved['missing'];
        $this->sampleOk = $missing === [];
        $this->sampleInfo = $this->sampleOk
            ? 'Semua kolom wajib terdeteksi pada baris '.$this->header_row.' sheet "'.$sheet->getTitle().'".'
            : 'Kolom wajib belum terdeteksi: '.implode(', ', $missing).'. Susun resepnya secara manual di bawah.';

        $this->sampleFile = null;
    }

    /** Grup sementara (belum tersimpan) supaya pratinjau memakai isian form saat ini. */
    private function previewGroup(): DistributorTemplateGroup
    {
        $group = new DistributorTemplateGroup;
        $group->name = $this->name !== '' ? $this->name : 'Pratinjau';
        $group->column_map = $this->buildColumnMap();
        // Sheet WAJIB ikut: tanpa ini pratinjau dan uji coba jatuh ke sheet
        // aktif berkas — kerap sheet ringkasan — lalu melaporkan "kolom wajib
        // tidak ditemukan" padahal resepnya sudah benar.
        $group->sheet_name = $this->sheet_name ?: null;
        $group->header_row = $this->header_row ? (int) $this->header_row : null;
        $group->date_format = $this->date_format;
        $group->ed_format = $this->ed_format;
        $group->default_batch = $this->default_batch;
        $group->skip_nonpositive_qty = $this->skip_nonpositive_qty;

        return $group;
    }

    /**
     * Hasil pembacaan baris contoh memakai resep yang sedang disusun.
     *
     * Inilah jawaban atas pertanyaan yang paling mudah salah: kode distributor
     * seperti apa yang sebenarnya terbentuk, dan apakah kode itu ada di Master
     * Distributor. Tanpa pratinjau, selisih seperti UDCSURABAYA vs UDC-SURABAYA
     * baru ketahuan saat upload gagal.
     *
     * @return array<int, array{code: string, tanggal: ?string, item: string, qty: float, batch: ?string, known: bool, distributor: ?string, rows: int}>
     */
    public function getPreviewProperty(): array
    {
        if ($this->sampleRows === [] || $this->sampleHeaders === []) {
            return [];
        }

        $group = $this->previewGroup();

        // Judul kolom contoh disusun ulang jadi bentuk yang sama dengan hasil
        // resolver, supaya resep mengambil index kolom yang benar.
        $indexByHeader = [];
        foreach ($this->sampleHeaders as $idx => $header) {
            $indexByHeader[StockTemplateColumns::normalize($header)] ??= $idx;
        }

        $col = [];
        foreach ($group->recipes() as $canonical => $recipe) {
            $columns = $recipe->columns();
            if ($columns === []) {
                $col[$canonical] = -1;

                continue;
            }
            $idx = $indexByHeader[StockTemplateColumns::normalize($columns[0])] ?? null;
            if ($idx !== null) {
                $col[$canonical] = $idx;
            }
        }

        $reader = new StockRowReader(
            ['col' => $col, 'index_by_header' => $indexByHeader, 'recipes' => $group->recipes()],
            $group,
            app(StockImportService::class),
        );

        $byCode = [];
        foreach ($this->sampleRows as $row) {
            if ($reader->shouldSkip($row)) {
                continue;
            }

            $code = $reader->distributorCode($row);
            if ($code === '') {
                continue;
            }

            if (! isset($byCode[$code])) {
                $byCode[$code] = [
                    'code' => $code,
                    'tanggal' => $reader->tanggal($row),
                    'item' => $reader->itemName($row),
                    'qty' => $reader->quantity($row),
                    'batch' => $reader->batchNo($row),
                    'rows' => 0,
                ];
            }

            $byCode[$code]['rows']++;
        }

        if ($byCode === []) {
            return [];
        }

        $known = Distributor::whereIn('distributor_code', array_keys($byCode))
            ->pluck('name', 'distributor_code');

        return array_values(array_map(function (array $entry) use ($known) {
            $entry['known'] = $known->has($entry['code']);
            $entry['distributor'] = $known->get($entry['code']);

            return $entry;
        }, $byCode));
    }

    public function clearSample(): void
    {
        $this->sampleHeaders = [];
        $this->sampleSheets = [];
        $this->sampleRows = [];
        $this->sampleInfo = null;
        $this->sampleOk = false;
        $this->sampleFile = null;
        $this->resetErrorBag('sampleFile');
    }

    /**
     * Terjemahkan isian form menjadi bentuk simpan column_map.
     *
     * @return array<string, array|string>
     */
    private function buildColumnMap(): array
    {
        $map = [];

        foreach (StockTemplateColumns::all() as $canonical) {
            $entry = $this->columnMap[$canonical] ?? [];
            $parts = [];

            foreach ((array) ($entry['parts'] ?? []) as $part) {
                $value = trim((string) ($part['value'] ?? ''));
                if ($value === '') {
                    continue;
                }
                $parts[] = ($part['type'] ?? 'column') === 'text'
                    ? ['text' => $value]
                    : ['column' => $value];
            }

            if ($parts === []) {
                continue;
            }

            $recipe = StockColumnRecipe::fromValue([
                'parts' => $parts,
                'nospace' => (bool) ($entry['nospace'] ?? false),
                'upper' => (bool) ($entry['upper'] ?? false),
            ]);

            if ($recipe) {
                $map[$canonical] = $recipe->toStorage();
            }
        }

        return $map;
    }

    /**
     * Muat berkas unggahan sementara menjadi Spreadsheet.
     *
     * Berkas sementara Livewire tidak selalu berakhiran .xlsx, sementara
     * PhpSpreadsheet menentukan pembacanya dari ekstensi — tanpa disalin ke
     * nama bereksntensi benar, berkas yang sah pun ditolak dengan pesan
     * 'Unable to identify a reader for this file'.
     *
     * tempnam() sudah membuat berkas dan mengembalikan path-nya; menambahkan
     * ekstensi menghasilkan path BERBEDA, jadi keduanya dihapus bersama.
     */
    private function loadSpreadsheet($file): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $cleanExt = in_array($ext, ['xlsx', 'xls'], true) ? $ext : 'xlsx';

        $tempBase = tempnam(sys_get_temp_dir(), 'satoria_tpl_');
        $tempFile = $tempBase.'.'.$cleanExt;
        copy($file->getRealPath(), $tempFile);

        try {
            return IOFactory::load($tempFile);
        } finally {
            @unlink($tempFile);
            @unlink($tempBase);
        }
    }

    /**
     * Jalankan pembacaan sungguhan atas berkas contoh — tanpa menyimpan apa pun.
     *
     * Pratinjau di atas hanya memperlihatkan kode dan tanggal dari 50 baris
     * pertama. Uji coba ini membaca SELURUH berkas dengan mesin yang sama
     * dipakai saat impor, lalu melaporkan hal-hal yang baru terasa saat impor
     * sungguhan: item yang belum ter-mapping, ED yang tidak terbaca, batch yang
     * kosong, dan cabang yang belum terdaftar.
     *
     * Yang diuji adalah resep YANG SEDANG DISUSUN di form ini, bukan yang
     * tersimpan di database — jadi perubahan bisa dinilai sebelum disimpan.
     */
    public function runTest(): void
    {
        $this->testReport = [];
        $this->resetErrorBag('testFile');

        if (! $this->testFile) {
            $this->addError('testFile', 'Pilih berkas Excel yang ingin diuji terlebih dahulu.');

            return;
        }

        $this->validate([
            'testFile' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ], [], ['testFile' => 'berkas uji']);

        try {
            $spreadsheet = $this->loadSpreadsheet($this->testFile);
        } catch (\Throwable $e) {
            $this->addError('testFile', 'Berkas gagal dibaca: '.$e->getMessage());

            return;
        }

        $group = $this->previewGroup();

        $sheet = ($group->sheet_name ? $spreadsheet->getSheetByName($group->sheet_name) : null)
            ?? $spreadsheet->getSheetByName('Template')
            ?? $spreadsheet->getActiveSheet();

        if ($group->sheet_name && ! $spreadsheet->getSheetByName($group->sheet_name)) {
            $this->testReport = [
                'ok' => false,
                'error' => 'Sheet "'.$group->sheet_name.'" tidak ada pada berkas ini. Sheet yang tersedia: '
                    .implode(', ', $spreadsheet->getSheetNames()).'.',
                'branches' => [],
            ];

            return;
        }

        $data = $sheet->toArray(null, true, false, false);
        $resolved = (new StockHeaderResolver)->resolve($data, $group);

        if (! $resolved['ok']) {
            $others = array_values(array_diff($spreadsheet->getSheetNames(), [$sheet->getTitle()]));

            // Sheet yang dibaca disebutkan terang-terangan: penyebab tersering
            // kegagalan di sini bukan resep yang salah, melainkan sheet yang
            // keliru — berkas distributor kerap punya sheet ringkasan di depan.
            $this->testReport = [
                'ok' => false,
                'error' => 'Sheet yang dibaca: "'.$sheet->getTitle().'". '.$resolved['error']
                    .($others === [] ? '' : ' Sheet lain pada berkas ini: '.implode(', ', $others).' — bila datanya ada di salah satu sheet itu, pilih pada setelan "Sheet yang dibaca".'),
                'branches' => [],
            ];

            return;
        }

        $reader = new StockRowReader($resolved, $group, app(StockImportService::class));

        $branches = [];
        $skippedZero = 0;
        $totalRows = 0;

        foreach (array_slice($data, $resolved['header_row'] + 1) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = $reader->distributorCode($row);
            $itemName = $reader->itemName($row);

            if ($code === '' || $itemName === '') {
                continue;
            }

            if ($reader->shouldSkip($row)) {
                $skippedZero++;

                continue;
            }

            $totalRows++;

            $branches[$code] ??= [
                'code' => $code,
                'rows' => 0,
                'tanggal' => $reader->tanggal($row),
                'no_tanggal' => 0,
                'no_ed' => 0,
                'no_batch' => 0,
                'items' => [],
            ];

            $branches[$code]['rows']++;

            if (! $reader->tanggal($row)) {
                $branches[$code]['no_tanggal']++;
            }
            if (! $reader->expiredDate($row)) {
                $branches[$code]['no_ed']++;
            }
            if (! $reader->batchNo($row)) {
                $branches[$code]['no_batch']++;
            }

            $branches[$code]['items'][mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)))] = $itemName;
        }

        if ($branches === []) {
            $this->testReport = [
                'ok' => false,
                'error' => 'Tidak ada baris data yang terbaca. Periksa susunan kolom ID Distributor dan Nama Item.',
                'branches' => [],
            ];

            return;
        }

        $distributors = Distributor::whereIn('distributor_code', array_keys($branches))
            ->get()
            ->keyBy('distributor_code');

        foreach ($branches as $code => $branch) {
            $distributor = $distributors->get($code);

            $branch['known'] = $distributor !== null;
            $branch['distributor'] = $distributor?->name;
            $branch['inactive'] = $distributor !== null && ! $distributor->is_active;

            // Item yang belum ter-mapping ke NetSuite tidak akan tersimpan saat
            // impor — inilah angka yang paling sering mengejutkan operator.
            $branch['unmapped'] = [];
            if ($distributor) {
                $known = DistributorItem::where('distributor_id', $distributor->id)
                    ->get()
                    ->keyBy(fn ($i) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $i->item_name))));

                foreach ($branch['items'] as $key => $name) {
                    $item = $known->get($key);
                    if (! $item || ! $item->isMapped()) {
                        $branch['unmapped'][] = $name;
                    }
                }
            }

            $branch['item_count'] = count($branch['items']);
            unset($branch['items']);

            $branches[$code] = $branch;
        }

        $this->testReport = [
            'ok' => true,
            'error' => null,
            'sheet' => $sheet->getTitle(),
            'header_row' => $resolved['header_row'] + 1,
            'total_rows' => $totalRows,
            'skipped_zero' => $skippedZero,
            'branches' => array_values($branches),
        ];
    }

    public function clearTest(): void
    {
        $this->testFile = null;
        $this->testReport = [];
        $this->resetErrorBag('testFile');
    }

    /** Aktifkan atau nonaktifkan grup langsung dari daftar. */
    public function toggleActive(int $id): void
    {
        $group = DistributorTemplateGroup::findOrFail($id);
        Gate::authorize('update', $group);

        $group->update(['is_active' => ! $group->is_active]);

        session()->flash('status', $group->is_active
            ? "Grup \"{$group->name}\" diaktifkan dan kembali ikut dicobakan saat membaca berkas."
            : "Grup \"{$group->name}\" dinonaktifkan. Resepnya tidak lagi dicobakan, tapi tetap tersimpan.");
    }

    /**
     * Aturan validasi format tanggal: harus benar-benar bisa dipakai membaca.
     */
    private function formatRule(): \Closure
    {
        return function (string $attribute, $value, $fail) {
            if ($value && ! DistributorTemplateGroup::formatIsValid($value)) {
                $fail('Format "'.$value.'" tidak dikenali. Pakai lambang tanggal PHP, mis. d/m/Y untuk 31/12/2027 atau m/Y untuk 12/2027.');
            }
        };
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('distributor_template_groups', 'name')->ignore($this->editingId)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sheet_name' => ['nullable', 'string', 'max:255'],
            'header_row' => ['nullable', 'integer', 'min:1', 'max:200'],
            // Format diketik bebas, tapi diuji dulu: format yang salah ketik
            // gagal diam-diam saat impor, bukan saat disimpan.
            'date_format' => ['nullable', 'string', 'max:50', $this->formatRule()],
            'ed_format' => ['nullable', 'string', 'max:50', $this->formatRule()],
            'default_batch' => ['nullable', 'string', 'max:100'],
            'skip_nonpositive_qty' => ['boolean'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => 'nama grup',
            'header_row' => 'baris header',
            'sheet_name' => 'nama sheet',
            'date_format' => 'format tanggal',
            'ed_format' => 'format ED',
            'default_batch' => 'batch pengganti',
        ]);

        $map = $this->buildColumnMap();

        // Dua kolom kanonik yang menunjuk susunan kolom yang sama akan saling
        // berebut slot di resolver; lebih baik ditolak di sini daripada
        // menghasilkan impor yang salah diam-diam.
        $seen = [];
        $duplicates = [];
        foreach ($map as $canonical => $stored) {
            $signature = StockTemplateColumns::normalize(
                is_string($stored) ? $stored : json_encode($stored)
            );
            if (isset($seen[$signature])) {
                $duplicates[] = $seen[$signature].' & '.$canonical;
            }
            $seen[$signature] = $canonical;
        }
        if ($duplicates !== []) {
            $this->addError('columnMap', 'Susunan kolom yang sama dipakai lebih dari satu kolom sistem: '.implode(', ', $duplicates).'.');

            return;
        }

        $data = [
            'name' => $this->name,
            'is_active' => $this->is_active,
            'notes' => $this->notes ?: null,
            'sheet_name' => $this->sheet_name ?: null,
            'header_row' => ($this->header_row !== null && $this->header_row !== '') ? (int) $this->header_row : null,
            'column_map' => $map ?: null,
            'date_format' => $this->date_format ?: null,
            'ed_format' => $this->ed_format ?: null,
            // Cara membaca tanggal disimpulkan dari formatnya (lihat dateMode());
            // kolom lama disetel konsisten supaya tidak ada sisa nilai menyesatkan.
            'date_mode' => DistributorTemplateGroup::DATE_AUTO,
            'default_batch' => $this->default_batch ?: null,
            'skip_nonpositive_qty' => $this->skip_nonpositive_qty,
        ];

        if ($this->editingId) {
            $group = DistributorTemplateGroup::findOrFail($this->editingId);
            Gate::authorize('update', $group);
            $group->update($data);
        } else {
            Gate::authorize('create', DistributorTemplateGroup::class);
            $group = DistributorTemplateGroup::create($data);
        }

        // Keanggotaan disinkronkan dua arah: yang dicentang ditempel ke grup
        // ini, yang tidak dicentang tapi sebelumnya milik grup ini dilepas.
        $ids = array_values(array_filter(array_map('intval', $this->selectedDistributors)));
        if ($ids !== []) {
            Distributor::whereIn('id', $ids)->update(['template_group_id' => $group->id]);
        }
        Distributor::where('template_group_id', $group->id)
            ->when($ids !== [], fn ($q) => $q->whereNotIn('id', $ids))
            ->update(['template_group_id' => null]);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Grup template tersimpan.');
    }

    public function delete(int $id): void
    {
        $group = DistributorTemplateGroup::findOrFail($id);
        Gate::authorize('delete', $group);

        // Distributor tidak ikut terhapus — cukup dilepas dari grup, supaya
        // menghapus grup tidak pernah berarti kehilangan master distributor.
        $group->distributors()->update(['template_group_id' => null]);
        $group->delete();

        session()->flash('status', 'Grup template dihapus. Distributor anggotanya kembali memakai deteksi otomatis.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->is_active = true;
        $this->notes = null;
        $this->sheet_name = null;
        $this->header_row = null;
        $this->date_format = null;
        $this->ed_format = null;
        $this->default_batch = null;
        $this->skip_nonpositive_qty = false;
        $this->selectedDistributors = [];

        $this->columnMap = [];
        foreach (StockTemplateColumns::all() as $canonical) {
            $this->columnMap[$canonical] = ['parts' => [], 'nospace' => false, 'upper' => false];
        }

        $this->clearSample();
        $this->clearTest();
        $this->resetErrorBag();
    }

    public function render()
    {
        $groups = DistributorTemplateGroup::query()
            ->withCount('distributors')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'ilike', Search::contains($this->search))
                    ->orWhere('notes', 'ilike', Search::contains($this->search));
            }))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.template-groups.index', [
            'groups' => $groups,
            'definitions' => StockTemplateColumns::definitions(),
            'allDistributors' => Distributor::orderBy('name')->get(['id', 'name', 'distributor_code', 'template_group_id']),
            'preview' => $this->preview,
        ]);
    }
}
