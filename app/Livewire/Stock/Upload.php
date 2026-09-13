<?php

namespace App\Livewire\Stock;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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

    /**
     * Format tanggal teks yang diterima dari file Excel.
     * Diseragamkan menggunakan format Day-First {DD/MM/YYYY} (d/m/Y).
     */
    private const DATE_FORMATS = [
        'd/m/Y',
        'd-m-Y',
        'j/n/Y',
        'j-n-Y',
        'Y-m-d',
        'Y/m/d',
        'm/d/Y',
        'n/j/Y',
        'm-d-Y',
        'n-j-Y',
    ];


    public ?string $tanggal = null;

    public ?int $distributorId = null;

    public string $activeTab = 'import';

    /** @var array<int, array> current grid rows, kept in sync with AG Grid on save */
    public array $rows = [];

    /**
     * distributor_item_id yang secara eksplisit dihapus pengguna dari grid dan
     * karenanya harus ikut dihapus dari database saat menyimpan.
     *
     * Sengaja memakai daftar eksplisit, bukan "hapus yang tidak ada di grid":
     * grid sering hanya memuat sebagian data (import melewati item yang belum
     * ter-mapping), sehingga rekonsiliasi otomatis bisa menghapus baris yang
     * tidak pernah dilihat pengguna.
     *
     * @var array<int, int>
     */
    public array $removedItemIds = [];

    public $file = null;

    public array $skippedItems = [];

    public array $skippedRowsData = [];

    public bool $showRequestModal = false;

    public bool $showSingleRequestModal = false;

    public string $requestItemName = '';

    public string $requestSatuan = 'PCS';

    public ?int $addItemId = null;

    public bool $showSuccessModal = false;

    public bool $showConflictModal = false;

    public array $pendingImportData = [];

    public array $saveSummary = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', StockEntry::class);
        $this->tanggal = request()->query('tanggal', now()->toDateString());
        if ($distId = request()->query('distributor_id')) {
            $this->distributorId = (int) $distId;
            $this->loadExisting();
        } elseif ($emailUid = request()->query('from_email_uid')) {
            $attachmentId = request()->query('attachment_id');
            $this->loadFromEmail($emailUid, $attachmentId);
        }
    }

    public function loadFromEmail(string|int $emailUid, ?string $attachmentId = null): void
    {
        Gate::authorize('create', StockEntry::class);

        $imapService = app(\App\Services\ImapService::class);
        if (! $imapService->isConfigured()) {
            session()->flash('error', 'Koneksi mail server IMAP belum dikonfigurasi pada .env.');

            return;
        }

        $att = $imapService->getExcelAttachment($emailUid, $attachmentId);
        if (! $att || empty($att['content'])) {
            session()->flash('error', "Tidak ditemukan lampiran berkas Excel pada email (UID #{$emailUid}).");

            return;
        }

        $filename = $att['filename'] ?? 'attachment.xlsx';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $cleanExt = in_array($ext, ['xlsx', 'xls'], true) ? $ext : 'xlsx';
        $tempClean = tempnam(sys_get_temp_dir(), 'satoria_email_att_').'.'.$cleanExt;

        file_put_contents($tempClean, $att['content']);

        try {
            $success = $this->processSpreadsheetPath($tempClean, "Lampiran Email ({$filename})");
            if ($success) {
                // Tandai email sebagai terbaca setelah berhasil diproses
                $imapService->markAsRead($emailUid);

                if (! $this->showConflictModal) {
                    $distName = $this->distributorId ? (Distributor::find($this->distributorId)?->name ?? 'Distributor') : 'Distributor';
                    session()->flash('status', "Lampiran berkas '{$filename}' dari email berhasil dimuat ke grid: ".count($this->rows)." baris untuk {$distName}.");
                }
            }
        } finally {
            @unlink($tempClean);
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
        $this->removedItemIds = [];

        if ($existing->isEmpty()) {
            $this->rows = [];
            session()->flash('status', 'Belum ada data stock untuk tanggal & distributor ini. Silakan import atau tambah baris manual.');

            return;
        }

        $this->rows = $existing->map(fn (StockEntry $e) => [
            'distributor_item_id' => $e->distributor_item_id,
            'item_name' => $e->distributorItem?->item_name ?? ('Item ID #'.$e->distributor_item_id),
            'satuan' => $e->satuan ?: ($e->distributorItem?->satuan ?? 'PCS'),
            'quantity' => (float) $e->quantity,
            'expired_date' => optional($e->expired_date)->format('d/m/Y'),
            'batch_no' => $e->batch_no,
            'mapped' => $e->distributorItem?->isMapped() ?? false,
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

        $ext = strtolower($this->file->getClientOriginalExtension());
        $cleanExt = in_array($ext, ['xlsx', 'xls'], true) ? $ext : 'xlsx';
        $tempClean = tempnam(sys_get_temp_dir(), 'satoria_stock_').'.'.$cleanExt;
        copy($this->file->getRealPath(), $tempClean);

        try {
            $success = $this->processSpreadsheetPath($tempClean, 'File Excel');
            if ($success && ! $this->showConflictModal) {
                $distName = $this->distributorId ? (Distributor::find($this->distributorId)?->name ?? 'Distributor') : 'Distributor';
                session()->flash('status', 'Import selesai: '.count($this->rows)." baris dimuat ke grid untuk {$distName} — {$this->tanggal}.");
            }
        } finally {
            @unlink($tempClean);
        }
    }

    protected function processSpreadsheetPath(string $filePath, string $sourceDescription = 'File Excel'): bool
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            $this->addError('file', "Gagal membaca {$sourceDescription}: ".$e->getMessage());

            return false;
        }

        $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, false, false);

        $header = array_map(fn ($h) => trim((string) $h), $data[0] ?? []);
        $col = array_flip($header);

        $requiredCols = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity'];
        foreach ($requiredCols as $rc) {
            if (! isset($col[$rc])) {
                $this->addError('file', "Kolom '{$rc}' tidak ditemukan di sheet Template.");

                return false;
            }
        }

        $bodyRows = array_slice($data, 1);
        $firstDataRow = null;
        foreach ($bodyRows as $r) {
            $codeVal = trim((string) ($r[$col['ID DISTRIBUTOR']] ?? ''));
            if ($codeVal !== '' && strtoupper($codeVal) !== 'XXXX') {
                $firstDataRow = $r;
                break;
            }
        }

        if (! $firstDataRow) {
            foreach ($bodyRows as $r) {
                if (! empty($r[$col['ID DISTRIBUTOR']] ?? null)) {
                    $firstDataRow = $r;
                    break;
                }
            }
        }

        if (! $firstDataRow) {
            $this->addError('file', "Berkas ({$sourceDescription}) tidak berisi baris data.");

            return false;
        }

        $distributorCode = trim((string) $firstDataRow[$col['ID DISTRIBUTOR']]);
        if (strtoupper($distributorCode) === 'XXXX') {
            $this->addError('file', "Kode distributor masih berupa format contoh ('xxxx'). Silakan ganti dengan kode distributor sebenarnya (lihat sheet 'Daftar Distributor').");

            return false;
        }

        $distributor = Distributor::where('distributor_code', $distributorCode)->first();

        if (! $distributor) {
            $this->addError('file', "Distributor dengan kode '{$distributorCode}' belum ada di Master Distributor. Minta Admin menambahkan dulu.");

            return false;
        }

        $rawTanggal = $firstDataRow[$col['Tanggal']];
        if (in_array(trim(strtoupper((string) $rawTanggal)), ['DD/MM/YYYY', 'YYYY-MM-DD', 'DD-MM-YYYY'], true)) {
            $this->addError('file', "Tanggal snapshot masih berupa format contoh ('DD/MM/YYYY'). Silakan isi dengan tanggal yang valid (contoh: ".now()->format('d/m/Y').").");

            return false;
        }

        $tanggal = $this->parseExcelDate($rawTanggal);

        $knownItems = DistributorItem::where('distributor_id', $distributor->id)
            ->get()
            ->keyBy(fn ($i) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $i->item_name))));

        $rows = [];
        $skipped = [];
        $skippedRowsData = [];

        foreach ($bodyRows as $r) {
            $itemName = trim((string) ($r[$col['Distributor Item Name']] ?? ''));
            if ($itemName === '' || strtoupper($itemName) === 'XXXXX XXXX') {
                continue;
            }

            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)));
            $distItem = $knownItems->get($key);

            $rawQty = str_replace([',', ' '], '', trim((string) ($r[$col['Quantity']] ?? 0)));
            $qty = (float) $rawQty;
            $satuan = isset($col['Satuan']) ? trim((string) ($r[$col['Satuan']] ?? '')) : null;
            $ed = isset($col['ED']) ? $this->parseExcelDate($r[$col['ED']] ?? null) : null;
            $edFormatted = $ed ? \Carbon\Carbon::parse($ed)->format('d/m/Y') : null;
            $batch = isset($col['Batch No']) ? trim((string) ($r[$col['Batch No']] ?? '')) : null;

            if (! $distItem) {
                $skipped[] = $itemName;

                if (! isset($skippedRowsData[$key])) {
                    $skippedRowsData[$key] = [
                        'item_name' => $itemName,
                        'satuan' => $satuan ?: 'PCS',
                        'quantity' => $qty,
                        'expired_date' => $edFormatted,
                        'batch_no' => $batch ?: null,
                    ];
                } else {
                    $skippedRowsData[$key]['quantity'] += $qty;
                    $skippedRowsData[$key]['batch_no'] = $this->mergeBatchNumbers($skippedRowsData[$key]['batch_no'] ?? null, $batch);
                    $skippedRowsData[$key]['expired_date'] = $this->mergeExpiredDates($skippedRowsData[$key]['expired_date'] ?? null, $edFormatted);
                }

                continue;
            }

            if (isset($rows[$distItem->id])) {
                $rows[$distItem->id]['quantity'] += $qty;
                $rows[$distItem->id]['batch_no'] = $this->mergeBatchNumbers($rows[$distItem->id]['batch_no'] ?? null, $batch);
                $rows[$distItem->id]['expired_date'] = $this->mergeExpiredDates($rows[$distItem->id]['expired_date'] ?? null, $edFormatted);
            } else {
                $rows[$distItem->id] = [
                    'distributor_item_id' => $distItem->id,
                    'item_name' => $distItem->item_name,
                    'satuan' => $satuan ?: $distItem->satuan,
                    'quantity' => $qty,
                    'expired_date' => $edFormatted,
                    'batch_no' => $batch ?: null,
                    'mapped' => $distItem->isMapped(),
                ];
            }
        }

        $effectiveTanggal = $tanggal ?? $this->tanggal ?? now()->toDateString();

        // Cek apakah sudah ada data tersimpan di DB untuk tanggal & distributor ini
        $existingCount = StockEntry::query()
            ->where('tanggal', $effectiveTanggal)
            ->where('distributor_id', $distributor->id)
            ->count();

        if ($existingCount > 0) {
            $this->pendingImportData = [
                'tanggal' => $effectiveTanggal,
                'distributor_id' => $distributor->id,
                'distributor_name' => $distributor->name,
                'existing_count' => $existingCount,
                'new_rows' => $rows,
                'skipped' => array_values(array_unique($skipped)),
                'skipped_rows_data' => array_values($skippedRowsData),
            ];
            $this->showConflictModal = true;

            return true;
        }

        $this->tanggal = $effectiveTanggal;
        $this->distributorId = $distributor->id;
        $this->rows = array_values($rows);
        $this->skippedItems = array_values(array_unique($skipped));
        $this->skippedRowsData = array_values($skippedRowsData);
        $this->removedItemIds = [];
        $this->dispatch('rows-loaded', rows: $this->rows);
        $this->dispatch('file-imported');

        return true;
    }

    public function confirmImport(string $mode): void
    {
        if (empty($this->pendingImportData)) {
            $this->showConflictModal = false;

            return;
        }

        $pending = $this->pendingImportData;
        $this->tanggal = $pending['tanggal'];
        $this->distributorId = $pending['distributor_id'];
        $this->skippedItems = $pending['skipped'];
        $this->skippedRowsData = $pending['skipped_rows_data'];
        $this->removedItemIds = [];

        if ($mode === 'merge') {
            $existingEntries = StockEntry::query()
                ->with('distributorItem.netsuiteItem')
                ->where('tanggal', $this->tanggal)
                ->where('distributor_id', $this->distributorId)
                ->get();

            $mergedRows = $pending['new_rows']; // keyed by distributor_item_id

            foreach ($existingEntries as $entry) {
                $itemId = $entry->distributor_item_id;
                if (isset($mergedRows[$itemId])) {
                    $mergedRows[$itemId]['quantity'] += (float) $entry->quantity;
                    $mergedRows[$itemId]['batch_no'] = $this->mergeBatchNumbers($entry->batch_no, $mergedRows[$itemId]['batch_no'] ?? null);
                    $mergedRows[$itemId]['expired_date'] = $this->mergeExpiredDates(
                        optional($entry->expired_date)->format('d/m/Y'),
                        $mergedRows[$itemId]['expired_date'] ?? null
                    );
                } else {
                    $mergedRows[$itemId] = [
                        'distributor_item_id' => $itemId,
                        'item_name' => $entry->distributorItem?->item_name ?? ('Item ID #'.$itemId),
                        'satuan' => $entry->satuan ?: ($entry->distributorItem?->satuan ?? 'PCS'),
                        'quantity' => (float) $entry->quantity,
                        'expired_date' => optional($entry->expired_date)->format('d/m/Y'),
                        'batch_no' => $entry->batch_no,
                        'mapped' => $entry->distributorItem?->isMapped() ?? false,
                    ];
                }
            }

            $this->rows = array_values($mergedRows);
            session()->flash('status', 'Import selesai (Mode Smart FEFO Merge): '.count($this->rows)." baris stok berhasil digabungkan untuk {$pending['distributor_name']} — {$this->tanggal}.");
        } else {
            // Mode 'replace'
            $this->rows = array_values($pending['new_rows']);
            session()->flash('status', 'Import selesai (Mode Timpa/Revisi): '.count($this->rows)." baris baru dimuat ke grid untuk {$pending['distributor_name']} — {$this->tanggal}.");
        }

        $this->showConflictModal = false;
        $this->pendingImportData = [];
        $this->dispatch('rows-loaded', rows: $this->rows);
        $this->dispatch('file-imported');
    }

    public function cancelConflictModal(): void
    {
        $this->showConflictModal = false;
        $this->pendingImportData = [];
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

                if ($distItem) {
                    $gridKey = null;
                    foreach ($this->rows as $k => $r) {
                        if ((int) ($r['distributor_item_id'] ?? 0) === $distItem->id) {
                            $gridKey = $k;
                            break;
                        }
                    }

                    if ($gridKey !== null) {
                        $this->rows[$gridKey]['quantity'] += (float) ($itemData['quantity'] ?? 0);
                        $this->rows[$gridKey]['batch_no'] = $this->mergeBatchNumbers($this->rows[$gridKey]['batch_no'] ?? null, $itemData['batch_no'] ?? null);
                        $this->rows[$gridKey]['expired_date'] = $this->mergeExpiredDates($this->rows[$gridKey]['expired_date'] ?? null, $itemData['expired_date'] ?? null);
                    } else {
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

        if ($distItem) {
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
        }

        $this->showSingleRequestModal = false;
        $this->reset(['requestItemName', 'requestSatuan']);

        if ($distItem) {
            session()->flash('status', "Item '{$distItem->item_name}' berhasil diajukan ke Admin dan ditambahkan ke grid stock.");
        }
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

    /**
     * Dipanggil dari grid saat pengguna menghapus satu baris.
     *
     * Tidak men-dispatch 'rows-loaded': grid sudah membuang barisnya sendiri,
     * dan mendorong $rows kembali ke grid akan menimpa editan sel yang belum
     * disimpan pada baris-baris lain.
     */
    public function markRowRemoved(int $distributorItemId): void
    {
        if (! in_array($distributorItemId, $this->removedItemIds, true)) {
            $this->removedItemIds[] = $distributorItemId;
        }

        $this->rows = collect($this->rows)
            ->reject(fn ($r) => (int) ($r['distributor_item_id'] ?? 0) === $distributorItemId)
            ->values()
            ->all();
    }

    /**
     * Daftar hapus hanya berlaku untuk satu kombinasi tanggal + distributor.
     * Begitu salah satunya berubah, daftar lama harus dibuang supaya tidak
     * menghapus baris milik tanggal atau distributor yang lain.
     */
    public function updatedTanggal(): void
    {
        $this->removedItemIds = [];
        if ($this->tanggal && preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', trim($this->tanggal), $m)) {
            $this->tanggal = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
    }


    public function updatedDistributorId(): void
    {
        $this->removedItemIds = [];
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
        $deletedCount = 0;

        $savedItemIds = [];

        DB::transaction(function () use ($rows, &$mappedCount, &$unmappedCount, &$deletedCount, &$savedItemIds) {
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
                        'expired_date' => ! empty($row['expired_date']) ? ($this->parseExcelDate($row['expired_date']) ?: $row['expired_date']) : null,
                        'batch_no' => $row['batch_no'] ?: null,
                        'uploaded_by' => Auth::id(),
                    ]
                );

                $savedItemIds[] = $item->id;
                $item->isMapped() ? $mappedCount++ : $unmappedCount++;
            }

            // Hapus HANYA baris yang memang diklik hapus oleh pengguna, dan
            // dibatasi tanggal + distributor yang sedang dikerjakan.
            //
            // Item yang ikut dikirim grid dikecualikan: pengguna bisa saja
            // menghapus sebuah baris lalu menambahkannya kembali sebelum
            // menyimpan. Tanpa pengecualian ini baris tersebut akan di-upsert
            // lalu langsung dihapus lagi pada transaksi yang sama.
            $idsToDelete = array_values(array_diff($this->removedItemIds, $savedItemIds));

            if ($idsToDelete !== []) {
                $toDelete = StockEntry::query()
                    ->where('tanggal', $this->tanggal)
                    ->where('distributor_id', $this->distributorId)
                    ->whereIn('distributor_item_id', $idsToDelete)
                    ->get();

                foreach ($toDelete as $entry) {
                    Gate::authorize('delete', $entry);
                    $entry->delete();
                    $deletedCount++;
                }
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
            'deleted' => $deletedCount,
        ];
        $this->showSuccessModal = true;

        $status = "Tersimpan: {$mappedCount} item ter-mapping, {$unmappedCount} item belum ter-mapping (tetap tersimpan sebagai snapshot {$this->tanggal}).";
        if ($deletedCount > 0) {
            $status .= " {$deletedCount} baris dihapus dari snapshot.";
        }
        session()->flash('status', $status);
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
        $sheet->setShowGridLines(true);

        // Header urutan kolom persis sesuai gambar referensi:
        // Tanggal | ID DISTRIBUTOR | Distributor Item Name | Quantity | Satuan | ED | Batch No
        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => [
                'name' => 'Calibri',
                'size' => 11,
                'bold' => false,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D4D4D4'],
                ],
            ],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Alignment per kolom header
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Baris contoh / placeholder persis sesuai gambar referensi dengan format seragam DD/MM/YYYY:
        // Baris 2: DD/MM/YYYY | xxxx | xxxxx xxxx | xxx | [blank] | DD/MM/YYYY | xxx
        // Baris 3: [blank]     | [blank] | xxxxx xxxx | xxx | [blank] | DD/MM/YYYY | xxx
        $sampleData = [
            ['DD/MM/YYYY', 'xxxx', 'xxxxx xxxx', 'xxx', '', 'DD/MM/YYYY', 'xxx'],
            ['',           '',     'xxxxx xxxx', 'xxx', '', 'DD/MM/YYYY', 'xxx'],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $sheet->getStyle('A2:G3')->getFont()->setName('Calibri')->setSize(11)->getColor()->setRGB('000000');
        $sheet->getStyle('A2:G3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D2:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G2:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Area kolom A dan B dibuat bersih putih (tanpa gridline horizontal) seperti pada tampilan gambar contoh
        $sheet->getStyle('A2:B50')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        // Garis batas vertikal antara Kolom A & B, serta B & C
        $sheet->getStyle('A2:A50')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E2E2');
        $sheet->getStyle('B2:B50')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E2E2');

        // Lebar kolom yang proporsional sesuai tampilan gambar
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(34);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(14);

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
            ['Tanggal', 'WAJIB', '31/08/2026', 'Tanggal posisi snapshot stok (format DD/MM/YYYY, contoh: 31/08/2026). Ditulis pada baris pertama data atau seluruh baris.'],
            ['ID DISTRIBUTOR', 'WAJIB', 'SDLSURABAYA', 'Kode resmi distributor Satoria. Harus persis sesuai dengan sheet "Daftar Distributor". Ditulis pada baris pertama data.'],
            ['Distributor Item Name', 'WAJIB', 'DEXTROSE 5% 500 ml', 'Nama item produk sesuai yang terdaftar di sistem distributor.'],
            ['Quantity', 'WAJIB', '1200', 'Jumlah stok akhir fisik/sistem distributor (hanya angka numerik).'],
            ['Satuan', 'OPSIONAL', 'BOTOL / PCH / BOX', 'Satuan kemasan. Jika kosong, sistem akan menggunakan satuan default dari Master Produk.'],
            ['ED', 'DISARANKAN', '31/12/2027', 'Tanggal kedaluwarsa (Expired Date) produk (format DD/MM/YYYY, contoh: 31/12/2027).'],
            ['Batch No', 'DISARANKAN', '026C05', 'Nomor batch produksi fisik obat/alkes untuk ketertelusuran produk di gudang.'],
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
            "3. Baris 2 & 3 pada sheet Template adalah format contoh (placeholder) yang dapat langsung diganti dengan data Anda.",
            "4. Selalu periksa kode pada sheet 'Daftar Distributor' agar tidak terjadi penolakan akibat kode distributor salah.",
            "5. Satu file hanya boleh berisi SATU kode distributor dan SATU tanggal (format DD/MM/YYYY).",
            "6. Jika terdapat item baru yang belum terdaftar di Satoria, sistem akan memberikan opsi pemetaan atau permintaan mapping produk baru.",
        ];
        foreach ($notes as $idx => $n) {
            $rowIdx = $noteStart + 1 + $idx;
            $guideSheet->setCellValue("A{$rowIdx}", $n);
            $guideSheet->getStyle("A{$rowIdx}")->getFont()->setSize(9)->getColor()->setRGB('334155');
        }

        $exampleStart = $noteStart + count($notes) + 3;
        $guideSheet->setCellValue("A{$exampleStart}", 'CONTOH PENGISIAN (jangan disalin apa adanya — ganti dengan data distributor Anda):');
        $guideSheet->getStyle("A{$exampleStart}")->getFont()->setBold(true)->getColor()->setRGB('0D6D5F');

        $exampleHeaderRow = $exampleStart + 1;
        $guideSheet->fromArray(['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'], null, "A{$exampleHeaderRow}");
        $guideSheet->getStyle("A{$exampleHeaderRow}:G{$exampleHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ]);

        $todayFormatted = now()->format('d/m/Y');

        $exampleRows = [
            [$todayFormatted, 'SDLSURABAYA', 'DEXTROSE 5% 500 ml', 1200, 'BOTOL', '31/12/2027', '026C05'],
            [$todayFormatted, 'SDLSURABAYA', 'DEXTROSE 10% 500 ml', 850, 'BOTOL', '31/12/2027', '026C06'],
            [$todayFormatted, 'SDLSURABAYA', 'SODIUM CHLORIDE 0.9% 500 ml', 2400, 'BOTOL', '30/06/2028', '026D12'],
            [$todayFormatted, 'SDLSURABAYA', 'RINGER LACTATE 500 ml', 1600, 'BOTOL', '30/09/2028', '026E01'],
            [$todayFormatted, 'SDLSURABAYA', 'SATORIA MEDIKA Disposable Infusion Set Y-Port 20drops/mL @1', 500, 'PCH', '31/01/2029', 'B26U01'],
        ];
        $guideSheet->fromArray($exampleRows, null, 'A'.($exampleHeaderRow + 1));

        $exampleEnd = $exampleHeaderRow + count($exampleRows);
        $guideSheet->getStyle("A{$exampleHeaderRow}:G{$exampleEnd}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
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

    private function parseExcelDate(mixed $value): ?string
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

        // Jangan memakai Carbon::parse() di sini: untuk string ambigu ia
        // mengikuti tafsir Amerika, sehingga "03/04/2026" dibaca 4 Maret
        // padahal sheet Panduan menjanjikan DD/MM/YYYY (3 April).
        //
        $value = trim((string) $value);

        foreach (self::DATE_FORMATS as $format) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }

            // Verifikasi round-trip. createFromFormat bersifat permisif dan
            // menggulung tanggal mustahil — 31/02/2026 menjadi 3 Maret. Kalau
            // hasil format ulang tidak identik dengan input, tanggalnya tidak
            // valid: tolak, jangan diam-diam "dibetulkan".
            if ($parsed && $parsed->format($format) === $value) {
                return $parsed->toDateString();
            }
        }

        return null;
    }

    /**
     * Merge two batch numbers into a clean comma-separated list without duplicates.
     */
    private function mergeBatchNumbers(?string $batch1, ?string $batch2): ?string
    {
        $b1 = trim((string) $batch1);
        $b2 = trim((string) $batch2);

        if ($b1 === '' && $b2 === '') {
            return null;
        }
        if ($b1 === '') {
            return $b2;
        }
        if ($b2 === '') {
            return $b1;
        }

        $parts = preg_split('/\s*,\s*/', $b1.','.$b2, -1, PREG_SPLIT_NO_EMPTY);
        $unique = array_unique(array_filter(array_map('trim', $parts)));

        return implode(', ', $unique);
    }

    /**
     * Merge two expired dates by selecting the earliest date (FEFO principle).
     */
    private function mergeExpiredDates(?string $ed1, ?string $ed2): ?string
    {
        $d1 = ! empty($ed1) ? trim((string) $ed1) : null;
        $d2 = ! empty($ed2) ? trim((string) $ed2) : null;

        if (! $d1) {
            return $d2 ? ($this->parseExcelDate($d2) ? \Carbon\Carbon::parse($this->parseExcelDate($d2))->format('d/m/Y') : $d2) : null;
        }
        if (! $d2) {
            return $d1 ? ($this->parseExcelDate($d1) ? \Carbon\Carbon::parse($this->parseExcelDate($d1))->format('d/m/Y') : $d1) : null;
        }

        $iso1 = $this->parseExcelDate($d1) ?? $d1;
        $iso2 = $this->parseExcelDate($d2) ?? $d2;

        $earliest = ($iso1 <= $iso2) ? $iso1 : $iso2;

        return \Carbon\Carbon::parse($earliest)->format('d/m/Y');
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
