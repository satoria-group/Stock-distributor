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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $this->tanggal = request()->query('tanggal', now()->toDateString());
        if ($distId = request()->query('distributor_id')) {
            $this->distributorId = (int) $distId;
            $this->loadExisting();
        }
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

            $rawQty = str_replace([',', ' '], '', trim((string) ($r[$col['Quantity']] ?? 0)));
            $qty = (float) $rawQty;
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
            $processedMasterItems = [];

            foreach ($itemsToProcess as $itemData) {
                $rawName = trim($itemData['item_name']);
                $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $rawName)));

                if (isset($processedMasterItems[$normalized])) {
                    $distItem = $processedMasterItems[$normalized];
                } else {
                    $existing = DistributorItem::withTrashed()
                        ->where('distributor_id', $this->distributorId)
                        ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
                        ->first();

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        if (! empty($itemData['satuan']) && empty($existing->satuan)) {
                            $existing->update(['satuan' => $itemData['satuan']]);
                        }
                        $distItem = $existing;
                    } else {
                        try {
                            $distItem = DistributorItem::create([
                                'distributor_id' => $this->distributorId,
                                'item_name' => $rawName,
                                'satuan' => $itemData['satuan'] ?: 'PCS',
                                'netsuite_item_id' => null,
                            ]);
                        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                            $distItem = DistributorItem::withTrashed()
                                ->where('distributor_id', $this->distributorId)
                                ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
                                ->first();
                            if ($distItem && $distItem->trashed()) {
                                $distItem->restore();
                            }
                        }
                    }
                    $processedMasterItems[$normalized] = $distItem;
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

        $existing = DistributorItem::withTrashed()
            ->where('distributor_id', $this->distributorId)
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            if (! empty($this->requestSatuan) && empty($existing->satuan)) {
                $existing->update(['satuan' => trim($this->requestSatuan)]);
            }
            $distItem = $existing;
        } else {
            try {
                $distItem = DistributorItem::create([
                    'distributor_id' => $this->distributorId,
                    'item_name' => $rawName,
                    'satuan' => trim($this->requestSatuan) ?: 'PCS',
                    'netsuite_item_id' => null,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $distItem = DistributorItem::withTrashed()
                    ->where('distributor_id', $this->distributorId)
                    ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
                    ->first();
                if ($distItem && $distItem->trashed()) {
                    $distItem->restore();
                }
            }
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

    public function downloadTemplate(): StreamedResponse
    {
        Gate::authorize('viewAny', StockEntry::class);

        $spreadsheet = new Spreadsheet();

        // ----------------------------------------------------
        // SHEET 1: Template (Main Upload Sheet)
        // ----------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Satuan', 'Quantity', 'Batch No', 'ED'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6D5F'], // Satoria Teal Brand
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '07352D'],
                ],
            ],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Realistic Satoria Sample Rows
        $today = now()->toDateString();
        $sampleRows = [
            [$today, 'SDLSURABAYA', 'DEXTROSE 5% 500 ml', 'BOTOL', 1200, '026C05', '2027-12-31'],
            [$today, 'SDLSURABAYA', 'DEXTROSE 10% 500 ml', 'BOTOL', 850, '026C06', '2027-12-31'],
            [$today, 'SDLSURABAYA', 'SODIUM CHLORIDE 0.9% 500 ml', 'BOTOL', 2400, '026D12', '2028-06-30'],
            [$today, 'SDLSURABAYA', 'RINGER LACTATE 500 ml', 'BOTOL', 1600, '026E01', '2028-09-30'],
            [$today, 'SDLSURABAYA', 'SATORIA MEDIKA Disposable Infusion Set Y-Port 20drops/mL @1', 'PCH', 500, 'B26U01', '2029-01-31'],
        ];
        $sheet->fromArray($sampleRows, null, 'A2');

        $rowCount = count($sampleRows) + 1;

        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle("A2:G{$rowCount}")->applyFromArray($dataStyle);

        for ($r = 2; $r <= $rowCount; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(20);
            if ($r % 2 === 1) {
                $sheet->getStyle("A{$r}:G{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
        }

        $sheet->getStyle("A2:B{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C2:C{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D2:D{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:E{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E2:E{$rowCount}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("F2:F{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G2:G{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:G{$rowCount}");

        // ----------------------------------------------------
        // SHEET 2: Daftar Distributor (Master Code Reference)
        // ----------------------------------------------------
        $distSheet = $spreadsheet->createSheet();
        $distSheet->setTitle('Daftar Distributor');

        $distHeaders = ['Kode Distributor (ID DISTRIBUTOR)', 'Nama Distributor', 'Status'];
        $distSheet->fromArray($distHeaders, null, 'A1');

        $distHeaderStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
        ];
        $distSheet->getStyle('A1:C1')->applyFromArray($distHeaderStyle);
        $distSheet->getRowDimension(1)->setRowHeight(26);

        $distributors = Distributor::where('is_active', true)
            ->orderBy('distributor_code')
            ->get(['distributor_code', 'name']);

        $distRows = [];
        foreach ($distributors as $d) {
            $distRows[] = [$d->distributor_code, $d->name, 'Aktif'];
        }
        $distSheet->fromArray($distRows, null, 'A2');

        $distRowCount = count($distRows) + 1;
        $distSheet->getStyle("A2:C{$distRowCount}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        for ($r = 2; $r <= $distRowCount; $r++) {
            $distSheet->getRowDimension($r)->setRowHeight(19);
            if ($r % 2 === 1) {
                $distSheet->getStyle("A{$r}:C{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
        }

        $distSheet->getStyle("A2:A{$distRowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $distSheet->getStyle("C2:C{$distRowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A', 'B', 'C'] as $col) {
            $distSheet->getColumnDimension($col)->setAutoSize(true);
        }
        $distSheet->freezePane('A2');
        $distSheet->setAutoFilter("A1:C{$distRowCount}");

        // ----------------------------------------------------
        // SHEET 3: Panduan Pengisian
        // ----------------------------------------------------
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Panduan Pengisian');

        $guideSheet->setCellValue('A1', 'PANDUAN PENGISIAN TEMPLATE UPLOAD STOK DISTRIBUTOR');
        $guideSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0D6D5F');
        $guideSheet->getRowDimension(1)->setRowHeight(28);

        $guideSheet->setCellValue('A2', 'Satoria Group — Manajemen Distribusi & Inventori Farmasi');
        $guideSheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('64748B');

        $colGuideHeaders = ['Nama Kolom', 'Wajib / Opsional', 'Contoh Nilai', 'Penjelasan & Aturan Validasi'];
        $guideSheet->fromArray($colGuideHeaders, null, 'A4');
        $guideSheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6D5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '07352D']]],
        ]);
        $guideSheet->getRowDimension(4)->setRowHeight(24);

        $colGuideData = [
            ['Tanggal', 'WAJIB', '2026-08-31', 'Tanggal posisi snapshot stok (format disarankan YYYY-MM-DD atau DD/MM/YYYY). Semua baris dalam 1 file harus tanggal yang sama.'],
            ['ID DISTRIBUTOR', 'WAJIB', 'SDLSURABAYA', 'Kode resmi distributor Satoria. Harus persis sesuai dengan sheet "Daftar Distributor".'],
            ['Distributor Item Name', 'WAJIB', 'DEXTROSE 5% 500 ml', 'Nama item produk sesuai yang terdaftar di sistem distributor.'],
            ['Satuan', 'OPSIONAL', 'BOTOL / PCH / BOX', 'Satuan kemasan. Jika kosong, sistem akan menggunakan satuan default dari Master Produk.'],
            ['Quantity', 'WAJIB', '1200', 'Jumlah stok akhir fisik/sistem distributor (hanya angka numerik).'],
            ['Batch No', 'DISARANKAN', '026C05', 'Nomor batch produksi fisik obat/alkes untuk ketertelusuran produk di gudang.'],
            ['ED', 'DISARANKAN', '2027-12-31', 'Tanggal kedaluwarsa (Expired Date) produk (format YYYY-MM-DD atau DD/MM/YYYY).'],
        ];
        $guideSheet->fromArray($colGuideData, null, 'A5');
        $guideEnd = 4 + count($colGuideData);
        $guideSheet->getStyle("A5:D{$guideEnd}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($r = 5; $r <= $guideEnd; $r++) {
            $guideSheet->getRowDimension($r)->setRowHeight(24);
            if ($r % 2 === 1) {
                $guideSheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
        }
        $guideSheet->getStyle("A5:A{$guideEnd}")->getFont()->setBold(true);
        $guideSheet->getStyle("B5:B{$guideEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $noteStart = $guideEnd + 2;
        $guideSheet->setCellValue("A{$noteStart}", 'CATATAN PENTING:');
        $guideSheet->getStyle("A{$noteStart}")->getFont()->setBold(true)->getColor()->setRGB('B91C1C');

        $notes = [
            "1. Pastikan sheet utama data tetap bernama 'Template' (atau sheet urutan pertama).",
            "2. Jangan menyisipkan baris kosong di atas baris 1 (Header harus di baris A1:G1).",
            "3. Selalu periksa kode pada sheet 'Daftar Distributor' agar tidak terjadi penolakan akibat kode distributor salah.",
            "4. Hapus atau timpa baris contoh yang disediakan pada sheet Template sebelum mengunggah file.",
            "5. Jika terdapat item baru yang belum terdaftar di Satoria, sistem akan memberikan opsi pemetaan atau permintaan mapping produk baru.",
        ];
        foreach ($notes as $idx => $n) {
            $rowIdx = $noteStart + 1 + $idx;
            $guideSheet->setCellValue("A{$rowIdx}", $n);
            $guideSheet->getStyle("A{$rowIdx}")->getFont()->setSize(9)->getColor()->setRGB('334155');
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $guideSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Return active sheet to Template so user opens directly to it
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template_upload_stock_satoria.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
