<?php

namespace App\Livewire\TemplateGroups;

use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorItem;
use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use App\Support\Search;
use App\Support\StockColumnRecipe;
use App\Support\StockFileReader;
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
 * Pengelolaan Format Berkas Excel.
 *
 * Satu grup = satu bentuk berkas Excel yang dipakai bersama oleh sekelompok
 * distributor (mis. seluruh cabang UDC). Operator menyusun "resep" tiap kolom
 * sistem dari kolom-kolom berkas milik grup itu, sehingga distributor tidak
 * perlu lagi dipaksa memakai template baku kita.
 *
 * Resep disusun dengan mengetik judul kolom apa adanya, lalu dibuktikan lewat
 * Uji Coba: berkas sungguhan dibaca dengan resep yang sedang tampil di form,
 * dan hasil tiap kolom diperlihatkan apa adanya. Tidak ada contoh karangan —
 * yang dinilai selalu berkas nyata.
 */
#[Layout('layouts.app', ['title' => 'Format Berkas Excel', 'subtitle' => 'Petakan judul kolom berkas Excel tiap grup distributor ke kolom standar sistem.'])]
class Index extends Component
{
    use WithFileUploads, WithPagination;

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

    /** @var array<int, string> kolom kanonik yang nilainya diwarisi bila kosong */
    public array $fill_down = [];

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

    /** @var array<int, int> id grup distributor yang memakai bentuk berkas ini */
    public array $selectedGroups = [];

    /**
     * Pemetaan kode distributor: baris {alias, official} — kode versi
     * distributor sendiri di berkas dipetakan ke distributor_code resmi.
     *
     * @var array<int, array{alias: string, official: string}>
     */
    public array $codeMap = [];

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
        $this->fill_down = $group->fillDownColumns();

        foreach ($group->recipes() as $canonical => $recipe) {
            $parts = [];
            foreach ($recipe->parts as $part) {
                $parts[] = match (true) {
                    isset($part['column']) => ['type' => 'column', 'value' => $part['column']],
                    isset($part['cell']) => ['type' => 'cell', 'value' => $part['cell']],
                    isset($part['sheet']) => ['type' => 'sheet', 'value' => ''],
                    isset($part['file']) => ['type' => 'file', 'value' => ''],
                    default => ['type' => 'text', 'value' => $part['text'] ?? ''],
                };
            }

            $this->columnMap[$canonical] = [
                'parts' => $parts,
                'nospace' => $recipe->nospace,
                'upper' => $recipe->upper,
            ];
        }

        $this->selectedGroups = DistributorGroup::where('template_group_id', $group->id)->pluck('id')->all();

        $this->codeMap = [];
        foreach ((array) ($group->code_map ?? []) as $alias => $official) {
            $this->codeMap[] = ['alias' => (string) $alias, 'official' => (string) $official];
        }

        $this->showModal = true;
    }

    public function addCodeMapRow(): void
    {
        $this->codeMap[] = ['alias' => '', 'official' => ''];
    }

    public function removeCodeMapRow(int $index): void
    {
        unset($this->codeMap[$index]);
        $this->codeMap = array_values($this->codeMap);
    }

    public function addPart(string $canonical, string $type = 'column'): void
    {
        if (! in_array($canonical, StockTemplateColumns::all(), true)) {
            return;
        }

        $this->columnMap[$canonical]['parts'][] = [
            'type' => in_array($type, ['text', 'cell', 'sheet', 'file'], true) ? $type : 'column',
            'value' => '',
        ];
    }

    public function removePart(string $canonical, int $index): void
    {
        unset($this->columnMap[$canonical]['parts'][$index]);
        $this->columnMap[$canonical]['parts'] = array_values($this->columnMap[$canonical]['parts']);
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
        $group->fill_down = $this->fill_down;
        $group->code_map = $this->buildCodeMap();

        return $group;
    }

    /**
     * Terjemahkan isian form menjadi bentuk simpan code_map.
     *
     * @return array<string, string>
     */
    private function buildCodeMap(): array
    {
        $map = [];

        foreach ($this->codeMap as $row) {
            $alias = mb_strtoupper(trim((string) ($row['alias'] ?? '')));
            $official = mb_strtoupper(trim((string) ($row['official'] ?? '')));

            if ($alias === '' || $official === '') {
                continue;
            }

            $map[$alias] = $official;
        }

        return $map;
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
                $type = $part['type'] ?? 'column';
                $value = trim((string) ($part['value'] ?? ''));

                // Nama sheet dan nama berkas tidak punya nilai untuk diketik:
                // keduanya diambil dari berkas yang sedang dibaca.
                if ($type === 'sheet') {
                    $parts[] = ['sheet' => true];

                    continue;
                }
                if ($type === 'file') {
                    $parts[] = ['file' => true];

                    continue;
                }

                if ($value === '') {
                    continue;
                }

                $parts[] = match ($type) {
                    'text' => ['text' => $value],
                    'cell' => ['cell' => mb_strtoupper($value)],
                    default => ['column' => $value],
                };
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

        // Berkas disalin ke nama bereksntensi benar: PhpSpreadsheet memilih
        // pembacanya dari ekstensi, sementara berkas sementara Livewire tidak
        // selalu punya.
        $ext = strtolower($this->testFile->getClientOriginalExtension());
        $tempBase = tempnam(sys_get_temp_dir(), 'satoria_uji_');
        $tempFile = $tempBase.'.'.(in_array($ext, ['xlsx', 'xls'], true) ? $ext : 'xlsx');
        copy($this->testFile->getRealPath(), $tempFile);

        try {
            // Mesin yang sama persis dengan proses impor — termasuk pemilihan
            // sheet, pembersihan baris, dan penjagaan memori. Resep yang diuji
            // adalah yang SEDANG DISUSUN di form ini, bukan yang tersimpan.
            $reading = (new StockFileReader(app(StockImportService::class)))->read(
                $tempFile,
                $this->testFile->getClientOriginalName(),
                $this->previewGroup(),
            );
        } catch (\Throwable $e) {
            $this->addError('testFile', 'Berkas gagal dibaca: '.$e->getMessage());

            return;
        } finally {
            @unlink($tempFile);
            @unlink($tempBase);
        }

        if (! $reading['ok']) {
            $sheets = $reading['sheets'] ?? [];

            $this->testReport = [
                'ok' => false,
                'error' => ($reading['error'] ?? 'Berkas tidak bisa dibaca dengan resep ini.')
                    .($sheets === [] ? '' : ' Sheet pada berkas ini: '.implode(', ', $sheets).'.'),
                'branches' => [],
            ];

            return;
        }

        $buckets = $reading['buckets'];

        if ($buckets === []) {
            $this->testReport = [
                'ok' => false,
                'error' => 'Tidak ada baris data yang terbaca. Periksa susunan kolom ID Distributor dan Nama Item.',
                'branches' => [],
            ];

            return;
        }

        $distributors = Distributor::whereIn('distributor_code', array_keys($buckets))
            ->get()
            ->keyBy('distributor_code');

        $branches = [];
        $totalRows = 0;

        foreach ($buckets as $code => $rows) {
            $distributor = $distributors->get($code);
            $reader = $rows[0]['reader'];

            $branch = [
                'code' => $code,
                'rows' => count($rows),
                'tanggal' => $reader->tanggal($rows[0]['row']),
                'no_tanggal' => 0,
                'no_ed' => 0,
                'no_batch' => 0,
                'known' => $distributor !== null,
                'distributor' => $distributor?->name,
                'inactive' => $distributor !== null && ! $distributor->is_active,
                'unmapped' => [],
            ];

            $names = [];
            foreach ($rows as $entry) {
                $rowReader = $entry['reader'];
                $totalRows++;

                if (! $rowReader->tanggal($entry['row'])) {
                    $branch['no_tanggal']++;
                }
                if (! $rowReader->expiredDate($entry['row'])) {
                    $branch['no_ed']++;
                }
                if (! $rowReader->batchNo($entry['row'])) {
                    $branch['no_batch']++;
                }

                $name = $rowReader->itemName($entry['row']);
                $names[DistributorItem::normalizeName($name)] = $name;
            }

            // Item yang belum ter-mapping ke NetSuite tidak akan tersimpan saat
            // impor — inilah angka yang paling sering mengejutkan operator.
            if ($distributor) {
                $known = DistributorItem::lookupFor($distributor);

                foreach ($names as $key => $name) {
                    $item = $known->get($key);
                    if (! $item || ! $item->isMapped()) {
                        $branch['unmapped'][] = $name;
                    }
                }
            }

            $branch['item_count'] = count($names);
            $branches[] = $branch;
        }

        $firstEntry = $buckets[array_key_first($buckets)][0];

        $this->testReport = [
            'ok' => true,
            'error' => null,
            'columns' => $this->columnPreview($firstEntry),
            'sample_code' => array_key_first($buckets),
            'file' => $this->testFile?->getClientOriginalName(),
            'sheet' => implode(', ', $reading['sheets'] ?? []),
            'header_row' => ($reading['resolved']['header_row'] ?? 0) + 1,
            'total_rows' => $totalRows,
            'skipped_zero' => 0,
            'branches' => $branches,
        ];
    }

    /**
     * Hasil tiap kolom sistem untuk satu baris nyata dari berkas uji.
     *
     * Inilah jurang yang paling sering membuat resep terlihat benar padahal
     * hasilnya lain: operator menyusun "GMP" + kolom Cabang, lalu baru tahu
     * bentuk akhirnya saat impor gagal. Di sini ditampilkan apa adanya — isi
     * mentahnya, lalu nilai setelah diolah.
     *
     * @param  array{row: array, reader: StockRowReader}  $entry
     * @return array<int, array<string, mixed>>
     */
    private function columnPreview(array $entry): array
    {
        $reader = $entry['reader'];
        $row = $entry['row'];

        $out = [];

        foreach (StockTemplateColumns::definitions() as $canonical => $def) {
            $parts = $this->columnMap[$canonical]['parts'] ?? [];

            // Nilai akhir: hasil setelah tanggal digali, format dipaksakan,
            // angka dibaca, dan batch pengganti diterapkan.
            $value = match ($canonical) {
                'Tanggal' => $reader->tanggal($row),
                'ED' => $reader->expiredDate($row),
                'Quantity' => rtrim(rtrim(number_format($reader->quantity($row), 2, ',', '.'), '0'), ','),
                'Batch No' => $reader->batchNo($row),
                'ID DISTRIBUTOR' => $reader->distributorCode($row),
                'Distributor Item Name' => $reader->itemName($row),
                default => $reader->raw($row, $canonical),
            };

            $raw = $reader->raw($row, $canonical);

            // Isi mentah hanya ditampilkan bila BERMAKNA berbeda dari hasil
            // akhirnya — untuk tanggal yang digali dari kalimat, justru itu yang
            // menjelaskan kenapa hasilnya begitu. Perbedaan yang cuma soal
            // penulisan angka (2293 vs 2.293) hanya menambah kebisingan.
            $rawShown = ($raw !== null && trim((string) $raw) !== ''
                && $this->normalizeForCompare((string) $raw) !== $this->normalizeForCompare((string) $value))
                ? (string) $raw
                : null;

            $out[] = [
                'canonical' => $canonical,
                'label' => $def['label'],
                'required' => $def['required'],
                'recipe' => $this->describeRecipe($parts),
                'raw' => $rawShown,
                'value' => ($value === null || $value === '') ? null : (string) $value,
            ];
        }

        return $out;
    }

    /** Bentuk pembanding longgar: beda pemisah ribuan bukan beda nilai. */
    private function normalizeForCompare(string $value): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9]/i', '', $value) ?? $value);
    }

    /**
     * Rangkaian resep dalam bahasa manusia, mis. '"GMP" + kolom Cabang'.
     *
     * @param  array<int, array{type?: string, value?: string}>  $parts
     */
    private function describeRecipe(array $parts): string
    {
        if ($parts === []) {
            return 'otomatis';
        }

        $pieces = [];
        foreach ($parts as $part) {
            $value = trim((string) ($part['value'] ?? ''));

            $pieces[] = match ($part['type'] ?? 'column') {
                'text' => '"'.$value.'"',
                'cell' => 'sel '.$value,
                'sheet' => 'nama sheet',
                'file' => 'nama berkas',
                default => $value !== '' ? $value : '(kolom kosong)',
            };
        }

        return implode(' + ', $pieces);
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
            'fill_down' => ['array'],
            'fill_down.*' => [Rule::in(StockTemplateColumns::all())],
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

        $codeMap = $this->buildCodeMap();

        if ($codeMap !== []) {
            // Kode resmi wajib aktif dan berasal dari grup usaha yang sedang
            // dipilih — sama seperti daftar yang ditampilkan di dropdown form,
            // supaya validasi tidak pernah menolak nilai yang justru baru saja
            // dipilih operator dari situ.
            $knownCodes = $this->codeMapDistributors()
                ->pluck('distributor_code')
                ->map(fn ($c) => mb_strtoupper($c))
                ->all();

            $unknown = array_values(array_diff(array_values($codeMap), $knownCodes));
            if ($unknown !== []) {
                $this->addError('codeMap', 'Kode distributor resmi berikut tidak ditemukan di Master Distributor: '.implode(', ', array_unique($unknown)).'.');

                return;
            }
        }

        $data = [
            'name' => $this->name,
            'is_active' => $this->is_active,
            'notes' => $this->notes ?: null,
            'sheet_name' => $this->sheet_name ?: null,
            'header_row' => ($this->header_row !== null && $this->header_row !== '') ? (int) $this->header_row : null,
            'column_map' => $map ?: null,
            'code_map' => $codeMap ?: null,
            'date_format' => $this->date_format ?: null,
            'ed_format' => $this->ed_format ?: null,
            // Cara membaca tanggal disimpulkan dari formatnya (lihat dateMode());
            // kolom lama disetel konsisten supaya tidak ada sisa nilai menyesatkan.
            'date_mode' => DistributorTemplateGroup::DATE_AUTO,
            'default_batch' => $this->default_batch ?: null,
            'skip_nonpositive_qty' => $this->skip_nonpositive_qty,
            'fill_down' => $this->fill_down ?: null,
        ];

        if ($this->editingId) {
            $group = DistributorTemplateGroup::findOrFail($this->editingId);
            Gate::authorize('update', $group);
            $group->update($data);
        } else {
            Gate::authorize('create', DistributorTemplateGroup::class);
            $group = DistributorTemplateGroup::create($data);
        }

        // Keanggotaan ditetapkan di level GRUP USAHA, bukan per distributor:
        // seluruh cabang satu grup mengirim berkas yang sama, jadi menandai 25
        // cabang UDC satu per satu hanya mengundang kelalaian.
        $ids = array_values(array_filter(array_map('intval', $this->selectedGroups)));
        if ($ids !== []) {
            DistributorGroup::whereIn('id', $ids)->update(['template_group_id' => $group->id]);
        }
        DistributorGroup::where('template_group_id', $group->id)
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
        DistributorGroup::where('template_group_id', $group->id)->update(['template_group_id' => null]);
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
        $this->fill_down = [];
        $this->selectedGroups = [];
        $this->codeMap = [];

        $this->columnMap = [];
        foreach (StockTemplateColumns::all() as $canonical) {
            $this->columnMap[$canonical] = ['parts' => [], 'nospace' => false, 'upper' => false];
        }

        $this->clearTest();
        $this->resetErrorBag();
    }

    public function render()
    {
        $groups = DistributorTemplateGroup::query()
            ->withCount(['distributors', 'distributorGroups'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'ilike', Search::contains($this->search))
                    ->orWhere('notes', 'ilike', Search::contains($this->search));
            }))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.template-groups.index', [
            'groups' => $groups,
            'definitions' => StockTemplateColumns::definitions(),
            'allGroups' => DistributorGroup::ordered()->withCount('distributors')->get(),
            'codeMapDistributors' => $this->codeMapDistributors(),
        ]);
    }

    /**
     * Distributor yang layak dipetakan sebagai "kode resmi" pada dropdown
     * Pemetaan Kode Distributor: aktif, milik grup usaha yang sedang dipilih
     * di form ini, dan belum punya Grup Template sendiri yang lain (supaya
     * pemetaan tidak diam-diam menimpa pengecualian format milik cabang lain).
     *
     * Belum ter-mapping ke grup MANA PUN (baik lewat pemetaan kode di grup
     * ini maupun grup template lain) juga difilter di sisi lain — di sini
     * cukup dibatasi ke grup usaha yang sedang dicentang.
     */
    private function codeMapDistributors()
    {
        $groupIds = array_values(array_filter(array_map('intval', $this->selectedGroups)));

        if ($groupIds === []) {
            return collect();
        }

        return Distributor::query()
            ->where('is_active', true)
            ->whereIn('distributor_group_id', $groupIds)
            ->orderBy('name')
            ->get(['id', 'distributor_code', 'name']);
    }
}
