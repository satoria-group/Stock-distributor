<?php

namespace App\Livewire\Stock;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Layout('layouts.app', ['title' => 'Upload Stock Harian', 'subtitle' => 'Import Template harian, koreksi langsung di grid, lalu simpan sebagai snapshot stock.'])]
class Upload extends Component
{
    use WithFileUploads;

    public ?string $tanggal = null;

    public ?int $distributorId = null;

    public string $activeTab = 'import';

    /** @var array<int, array> current grid rows, kept in sync with AG Grid on save */
    public array $rows = [];

    public $file = null;

    public array $skippedItems = [];

    public array $skippedRowsData = [];

    public bool $showRequestModal = false;

    public bool $showSingleRequestModal = false;

    public string $requestItemName = '';

    public string $requestSatuan = 'PCS';

    public ?int $addItemId = null;

    public bool $showSuccessModal = false;

    public array $saveSummary = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', StockEntry::class);
        $this->tanggal = now()->toDateString();
    }

    public function loadExisting(): void
    {
        $this->activeTab = 'manual';

        if (! $this->tanggal || ! $this->distributorId) {
            $this->addError('load', 'Pilih tanggal dan distributor dulu.');

            return;
        }

        $existing = StockEntry::query()
            ->with('distributorItem.netsuiteItem')
            ->where('tanggal', $this->tanggal)
            ->where('distributor_id', $this->distributorId)
            ->get();

        $this->skippedItems = [];
        $this->skippedRowsData = [];

        if ($existing->isEmpty()) {
            $this->rows = [];
            session()->flash('status', 'Belum ada data stock untuk tanggal & distributor ini. Silakan import atau tambah baris manual.');

            return;
        }

        $this->rows = $existing->map(fn (StockEntry $e) => [
            'distributor_item_id' => $e->distributorItem->id,
            'item_name' => $e->distributorItem->item_name,
            'satuan' => $e->satuan,
            'quantity' => (float) $e->quantity,
            'expired_date' => optional($e->expired_date)->toDateString(),
            'batch_no' => $e->batch_no,
            'mapped' => $e->distributorItem->isMapped(),
        ])->values()->all();

        $this->dispatch('rows-loaded', rows: $this->rows);
    }

    public function setDateAndLoad(string $date): void
    {
        $this->tanggal = $date;
        $this->loadExisting();
    }

    public function updatedFile(): void
    {
        $this->resetErrorBag('file');
    }

    public function importFile(): void
    {
        Gate::authorize('create', StockEntry::class);

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ], [
            'file.required' => 'File Excel belum selesai diunggah atau belum dipilih. Mohon tunggu sejenak hingga file siap.',
            'file.mimes' => 'Format file harus berupa berkas Excel (.xlsx atau .xls).',
        ]);

        try {
            $spreadsheet = IOFactory::load($this->file->getRealPath());
        } catch (\Throwable $e) {
            $this->addError('file', 'Gagal membaca file spreadsheet: '.$e->getMessage());

            return;
        }

        $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        $header = array_map(fn ($h) => trim((string) $h), $data[0] ?? []);
        $col = array_flip($header);

        $requiredCols = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity'];
        foreach ($requiredCols as $rc) {
            if (! isset($col[$rc])) {
                $this->addError('file', "Kolom '{$rc}' tidak ditemukan di sheet Template.");

                return;
            }
        }

        $bodyRows = array_slice($data, 1);
        $firstDataRow = null;
        foreach ($bodyRows as $r) {
            if (! empty($r[$col['ID DISTRIBUTOR']] ?? null)) {
                $firstDataRow = $r;
                break;
            }
        }

        if (! $firstDataRow) {
            $this->addError('file', 'File tidak berisi baris data.');

            return;
        }

        $distributorCode = trim((string) $firstDataRow[$col['ID DISTRIBUTOR']]);
        $distributor = Distributor::where('distributor_code', $distributorCode)->first();

        if (! $distributor) {
            $this->addError('file', "Distributor dengan kode '{$distributorCode}' belum ada di Master Distributor. Minta Admin menambahkan dulu.");

            return;
        }

        $rawTanggal = $firstDataRow[$col['Tanggal']];
        $tanggal = $this->parseExcelDate($rawTanggal);

        $knownItems = DistributorItem::where('distributor_id', $distributor->id)
            ->get()
            ->keyBy(fn ($i) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $i->item_name))));

        $rows = [];
        $skipped = [];
        $skippedRowsData = [];

        foreach ($bodyRows as $r) {
            $itemName = trim((string) ($r[$col['Distributor Item Name']] ?? ''));
            if ($itemName === '') {
                continue;
            }

            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)));
            $distItem = $knownItems->get($key);

            $qty = (float) ($r[$col['Quantity']] ?? 0);
            $satuan = isset($col['Satuan']) ? trim((string) ($r[$col['Satuan']] ?? '')) : null;
            $ed = isset($col['ED']) ? $this->parseExcelDate($r[$col['ED']] ?? null) : null;
            $batch = isset($col['Batch No']) ? trim((string) ($r[$col['Batch No']] ?? '')) : null;

            if (! $distItem) {
                $skipped[] = $itemName;

                if (! isset($skippedRowsData[$key])) {
                    $skippedRowsData[$key] = [
                        'item_name' => $itemName,
                        'satuan' => $satuan ?: 'PCS',
                        'quantity' => $qty,
                        'expired_date' => $ed,
                        'batch_no' => $batch ?: null,
                    ];
                } else {
                    $skippedRowsData[$key]['quantity'] += $qty;
                    if ($ed && empty($skippedRowsData[$key]['expired_date'])) {
                        $skippedRowsData[$key]['expired_date'] = $ed;
                    }
                    if ($batch && empty($skippedRowsData[$key]['batch_no'])) {
                        $skippedRowsData[$key]['batch_no'] = $batch;
                    }
                }

                continue;
            }

            if (isset($rows[$distItem->id])) {
                $rows[$distItem->id]['quantity'] += $qty;
            } else {
                $rows[$distItem->id] = [
                    'distributor_item_id' => $distItem->id,
                    'item_name' => $distItem->item_name,
                    'satuan' => $satuan ?: $distItem->satuan,
                    'quantity' => $qty,
                    'expired_date' => $ed,
                    'batch_no' => $batch ?: null,
                    'mapped' => $distItem->isMapped(),
                ];
            }
        }

        $this->tanggal = $tanggal ?? $this->tanggal;
        $this->distributorId = $distributor->id;
        $this->rows = array_values($rows);
        $this->skippedItems = array_values(array_unique($skipped));
        $this->skippedRowsData = array_values($skippedRowsData);
        $this->dispatch('rows-loaded', rows: $this->rows);
        $this->dispatch('file-imported');

        session()->flash('status', 'Import selesai: '.count($this->rows)." baris dimuat ke grid untuk {$distributor->name} — {$this->tanggal}.");
    }

    public function openRequestModal(): void
    {
        $this->showRequestModal = true;
    }

    public function submitRequestMapping(): void
    {
        Gate::authorize('create', StockEntry::class);

        if (! $this->distributorId || (empty($this->skippedRowsData) && empty($this->skippedItems))) {
            $this->showRequestModal = false;

            return;
        }

        $itemsToProcess = ! empty($this->skippedRowsData)
            ? $this->skippedRowsData
            : array_map(fn ($name) => [
                'item_name' => $name,
                'satuan' => 'PCS',
                'quantity' => 0,
                'expired_date' => null,
                'batch_no' => null,
            ], $this->skippedItems);

        $addedCount = 0;
        DB::transaction(function () use ($itemsToProcess, &$addedCount) {
            foreach ($itemsToProcess as $itemData) {
                $rawName = trim($itemData['item_name']);
                $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $rawName)));

                $existing = DistributorItem::where('distributor_id', $this->distributorId)
                    ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
                    ->first();

                if ($existing) {
                    $distItem = $existing;
                } else {
                    $distItem = DistributorItem::create([
                        'distributor_id' => $this->distributorId,
                        'item_name' => $rawName,
                        'satuan' => $itemData['satuan'] ?: 'PCS',
                        'netsuite_item_id' => null,
                    ]);
                }

                $alreadyInGrid = collect($this->rows)->firstWhere('distributor_item_id', $distItem->id);
                if (! $alreadyInGrid) {
                    $this->rows[] = [
                        'distributor_item_id' => $distItem->id,
                        'item_name' => $distItem->item_name,
                        'satuan' => $itemData['satuan'] ?: $distItem->satuan,
                        'quantity' => (float) ($itemData['quantity'] ?? 0),
                        'expired_date' => $itemData['expired_date'] ?? null,
                        'batch_no' => $itemData['batch_no'] ?? null,
                        'mapped' => $distItem->isMapped(),
                    ];
                    $addedCount++;
                }
            }
        });

        $this->skippedItems = [];
        $this->skippedRowsData = [];
        $this->showRequestModal = false;

        $this->dispatch('rows-loaded', rows: $this->rows);

        session()->flash('status', "{$addedCount} item berhasil diajukan ke Admin (status: Belum ter-mapping) dan telah dimuat ke grid stock.");
    }

    public function openSingleRequestModal(): void
    {
        $this->requestItemName = '';
        $this->requestSatuan = 'PCS';
        $this->resetErrorBag(['requestItemName', 'requestSatuan']);
        $this->showSingleRequestModal = true;
    }

    public function submitSingleRequest(): void
    {
        Gate::authorize('create', StockEntry::class);

        if (! $this->distributorId) {
            $this->addError('requestItemName', 'Pilih distributor terlebih dahulu.');

            return;
        }

        $this->validate([
            'requestItemName' => ['required', 'string', 'min:2', 'max:255'],
            'requestSatuan' => ['required', 'string', 'max:50'],
        ]);

        $rawName = trim($this->requestItemName);
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $rawName)));

        $existing = DistributorItem::where('distributor_id', $this->distributorId)
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
            ->first();

        if ($existing) {
            $distItem = $existing;
        } else {
            $distItem = DistributorItem::create([
                'distributor_id' => $this->distributorId,
                'item_name' => $rawName,
                'satuan' => trim($this->requestSatuan) ?: 'PCS',
                'netsuite_item_id' => null,
            ]);
        }

        $alreadyInGrid = collect($this->rows)->firstWhere('distributor_item_id', $distItem->id);
        if (! $alreadyInGrid) {
            $this->rows[] = [
                'distributor_item_id' => $distItem->id,
                'item_name' => $distItem->item_name,
                'satuan' => $distItem->satuan,
                'quantity' => 0,
                'expired_date' => null,
                'batch_no' => null,
                'mapped' => $distItem->isMapped(),
            ];
            $this->dispatch('rows-loaded', rows: $this->rows);
        }

        $this->showSingleRequestModal = false;
        $this->reset(['requestItemName', 'requestSatuan']);

        session()->flash('status', "Item '{$distItem->item_name}' berhasil diajukan ke Admin dan ditambahkan ke grid stock.");
    }

    public function addRow(): void
    {
        if (! $this->addItemId || ! $this->distributorId) {
            return;
        }

        $item = DistributorItem::find($this->addItemId);
        if (! $item || $item->distributor_id !== $this->distributorId) {
            return;
        }

        $already = collect($this->rows)->firstWhere('distributor_item_id', $item->id);
        if ($already) {
            return;
        }

        $this->rows[] = [
            'distributor_item_id' => $item->id,
            'item_name' => $item->item_name,
            'satuan' => $item->satuan,
            'quantity' => 0,
            'expired_date' => null,
            'batch_no' => null,
            'mapped' => $item->isMapped(),
        ];

        $this->addItemId = null;
        $this->dispatch('rows-loaded', rows: $this->rows);
    }

    public function removeRow(int $distributorItemId): void
    {
        $this->rows = collect($this->rows)
            ->reject(fn ($r) => (int) ($r['distributor_item_id'] ?? 0) === $distributorItemId)
            ->values()
            ->all();

        $this->dispatch('rows-loaded', rows: $this->rows);
    }

    public function saveRows(array $rows): void
    {
        Gate::authorize('create', StockEntry::class);

        if (! $this->tanggal || ! $this->distributorId) {
            $this->addError('save', 'Tanggal dan distributor wajib dipilih sebelum menyimpan.');

            return;
        }

        $mappedCount = 0;
        $unmappedCount = 0;

        DB::transaction(function () use ($rows, &$mappedCount, &$unmappedCount) {
            foreach ($rows as $row) {
                if (empty($row['distributor_item_id'])) {
                    continue;
                }

                $item = DistributorItem::find($row['distributor_item_id']);
                if (! $item || $item->distributor_id !== $this->distributorId) {
                    continue;
                }

                StockEntry::updateOrCreate(
                    [
                        'tanggal' => $this->tanggal,
                        'distributor_item_id' => $item->id,
                    ],
                    [
                        'distributor_id' => $this->distributorId,
                        'quantity' => (float) ($row['quantity'] ?? 0),
                        'satuan' => $row['satuan'] ?: $item->satuan,
                        'expired_date' => $row['expired_date'] ?: null,
                        'batch_no' => $row['batch_no'] ?: null,
                        'uploaded_by' => auth()->id(),
                    ]
                );

                $item->isMapped() ? $mappedCount++ : $unmappedCount++;
            }
        });

        $dist = Distributor::find($this->distributorId);
        $this->saveSummary = [
            'tanggal' => $this->tanggal,
            'distributor_name' => $dist?->name ?? '—',
            'distributor_code' => $dist?->distributor_code ?? '—',
            'total' => $mappedCount + $unmappedCount,
            'mapped' => $mappedCount,
            'unmapped' => $unmappedCount,
        ];
        $this->showSuccessModal = true;

        session()->flash('status', "Tersimpan: {$mappedCount} item ter-mapping, {$unmappedCount} item belum ter-mapping (tetap tersimpan sebagai snapshot {$this->tanggal}).");
        $this->loadExisting();
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.stock.upload', [
            'distributors' => Distributor::orderBy('name')->get(),
            'availableItems' => $this->distributorId
                ? DistributorItem::where('distributor_id', $this->distributorId)
                    ->whereNotIn('id', collect($this->rows)->pluck('distributor_item_id')->all() ?: [0])
                    ->orderBy('item_name')
                    ->get()
                : collect(),
            'recentDates' => $this->distributorId
                ? StockEntry::where('distributor_id', $this->distributorId)
                    ->select('tanggal')->distinct()->orderByDesc('tanggal')->limit(10)->pluck('tanggal')
                : collect(),
        ]);
    }
}
