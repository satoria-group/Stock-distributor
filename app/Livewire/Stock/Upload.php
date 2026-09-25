<?php

namespace App\Livewire\Stock;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use App\Models\StockSnapshotActivity;
use App\Support\StockFileReader;
use App\Support\StockRowReader;
use Illuminate\Support\Facades\Auth;
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
     * Identitas sebuah baris grid: item + batch.
     *
     * Sejak stok dicatat per batch, distributor_item_id saja TIDAK LAGI unik —
     * satu item bisa punya beberapa batch pada tanggal yang sama. Setiap tempat
     * yang dulu memakai item id sebagai kunci harus memakai ini.
     */
    public static function rowKey(int|string $itemId, ?string $batch): string
    {
        return $itemId.'|'.mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $batch) ?? ''));
    }

    /**
     * Kunci baris (item|batch) yang secara eksplisit dihapus pengguna dari grid
     * dan karenanya harus ikut dihapus dari database saat menyimpan.
     *
     * Sengaja memakai daftar eksplisit, bukan "hapus yang tidak ada di grid":
     * grid sering hanya memuat sebagian data (import melewati item yang belum
     * ter-mapping), sehingga rekonsiliasi otomatis bisa menghapus baris yang
     * tidak pernah dilihat pengguna.
     *
     * @var array<int, string>
     */
    public array $removedRowKeys = [];

    public $file = null;

    public array $skippedItems = [];

    public array $skippedRowsData = [];

    public bool $showRequestModal = false;

    public bool $showSingleRequestModal = false;

    public string $requestItemName = '';

    public string $requestSatuan = '';

    public ?int $addItemId = null;

    public bool $showConflictModal = false;

    public array $pendingImportData = [];

    /** Modal Ringkasan Berkas, tampil sebelum cabang pertama dimuat ke grid. */
    public bool $showSummaryModal = false;

    /** @var array{file?: ?string, branches?: array, unmapped?: array<string, array>} */
    public array $fileSummary = [];

    /** @var array<int, string> kunci item belum ter-mapping yang dicentang untuk diajukan */
    public array $summarySelected = [];

    /**
     * Asal-usul data yang sedang dimuat di grid, ikut disimpan ke
     * StockSnapshotActivity saat menyimpan.
     *
     * Jalur "upload dari email" SENGAJA tidak memverifikasi whitelist pengirim:
     * ada operator yang memilih emailnya secara sadar, jadi keputusan ada di
     * tangan manusia. Yang dicatat di sini adalah jejaknya — tanpa ini riwayat
     * hanya berbunyi "Upload snapshot baru oleh <nama>", tidak bisa dibedakan
     * dari unggah berkas biasa, dan lampiran asalnya tak bisa ditelusuri lagi
     * begitu email di inbox dihapus atau diarsipkan.
     */
    public ?string $sourceEmailUid = null;

    public ?string $sourceEmailFrom = null;

    public ?string $sourceEmailSubject = null;

    public ?string $sourceFilename = null;

    /**
     * Pengguna memilih "Timpa/Revisi" pada modal konflik, artinya berkas baru
     * dianggap sebagai kebenaran UTUH untuk tanggal + distributor tersebut.
     *
     * Tanpa penanda ini, "Timpa" hanya mengganti isi grid: saveRows() cuma
     * meng-upsert baris yang ada di grid, sehingga SKU lama yang tidak ada di
     * berkas baru tetap tertinggal di database — hasilnya identik dengan mode
     * "Gabung", padahal labelnya menjanjikan hal lain.
     */
    /**
     * Antrian cabang dari satu berkas.
     *
     * Berkas UDC/KFTD bisa memuat beberapa cabang sekaligus, sementara grid
     * ini dirancang untuk satu distributor. Daripada membongkar grid, cabang
     * dikerjakan bergiliran: selesaikan satu, simpan, lanjut sendiri ke
     * berikutnya.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $importQueue = [];

    public int $queueIndex = 0;

    public bool $replaceExistingSnapshot = false;

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
        // tempnam() sudah membuat berkasnya; menambah ekstensi = path berbeda,
        // jadi keduanya harus dihapus (lihat blok finally di bawah).
        $tempBase = tempnam(sys_get_temp_dir(), 'satoria_email_att_');
        $tempClean = $tempBase.'.'.$cleanExt;

        file_put_contents($tempClean, $att['content']);

        // Metadata pengirim dicatat untuk jejak audit, BUKAN untuk memblokir:
        // lihat catatan pada properti $sourceEmailUid.
        //
        // Sebelumnya di sini memanggil getMessageDetails() — metode yang tidak
        // pernah ada di ImapService. Error-nya tertelan `catch (\Throwable)`
        // yang kosong, sehingga pengirim SELALU null tanpa jejak apa pun.
        $this->sourceEmailUid = (string) $emailUid;
        $this->sourceEmailFrom = null;
        $this->sourceEmailSubject = null;
        $this->sourceFilename = $filename;

        $detail = $imapService->getMessage($emailUid);
        if ($detail) {
            $this->sourceEmailFrom = $detail['from_email'] ?: null;
            $this->sourceEmailSubject = $detail['subject'] ?? null;
        } else {
            // Jangan diam-diam: grid tetap boleh dimuat, tapi harus terlihat
            // bahwa asal-usulnya tidak lengkap.
            \Illuminate\Support\Facades\Log::warning('stock.upload.email_sender_unresolved', [
                'uid' => $emailUid,
                'user_id' => Auth::id(),
            ]);
        }

        try {
            $success = $this->processSpreadsheetPath($tempClean, "Lampiran Email ({$filename})");
            if ($success) {
                // Tandai email sebagai terbaca setelah berhasil diproses
                $imapService->markAsRead($emailUid);
            }
        } finally {
            @unlink($tempClean);
            @unlink($tempBase);
        }
    }

    /**
     * Lupakan asal-usul email. Dipanggil saat isi grid diganti dari sumber
     * lain (unggah berkas, memuat data tersimpan) supaya riwayat tidak
     * mengklaim sebuah email sebagai asal data yang bukan berasal darinya.
     */
    private function clearSourceEmail(): void
    {
        $this->sourceEmailUid = null;
        $this->sourceEmailFrom = null;
        $this->sourceEmailSubject = null;
        $this->sourceFilename = null;
    }

    /**
     * Jejak asal-usul untuk disimpan ke metadata StockSnapshotActivity.
     * Mengembalikan array kosong bila data tidak berasal dari email.
     *
     * @return array<string, string|null>
     */
    private function sourceMetadata(): array
    {
        if ($this->sourceEmailUid === null) {
            return $this->sourceFilename ? ['source' => 'file', 'filename' => $this->sourceFilename] : [];
        }

        return [
            'source' => 'email',
            'email_uid' => $this->sourceEmailUid,
            'from_email' => $this->sourceEmailFrom,
            'subject' => $this->sourceEmailSubject,
            'filename' => $this->sourceFilename,
        ];
    }

    public function resetManualForm(): void
    {
        $this->tanggal = null;
        $this->distributorId = null;
        $this->rows = [];
        $this->removedRowKeys = [];
        $this->skippedItems = [];
        $this->skippedRowsData = [];
        $this->clearImportQueue();
    }

    public function loadExisting(): void
    {
        $this->activeTab = 'manual';
        $this->clearSourceEmail();
        $this->replaceExistingSnapshot = false;

        if (! $this->tanggal || ! $this->distributorId) {
            $this->addError('load', 'Pilih tanggal dan distributor dulu.');

            return;
        }

        $dist = Distributor::find($this->distributorId);
        if (! $dist || ! $dist->is_active) {
            $this->addError('load', "Distributor berstatus NON-AKTIF di Master Data. Data stok tidak dapat diakses.");

            return;
        }

        $existing = StockEntry::query()
            ->with('distributorItem.netsuiteItem')
            ->where('tanggal', $this->tanggal)
            ->where('distributor_id', $this->distributorId)
            ->get();

        $this->skippedItems = [];
        $this->skippedRowsData = [];
        $this->removedRowKeys = [];

        if ($existing->isEmpty()) {
            $this->rows = [];
            session()->flash('status', 'Belum ada data stock untuk tanggal & distributor ini. Silakan import atau tambah baris manual.');

            return;
        }

        $this->rows = $existing->map(fn (StockEntry $e) => [
            'distributor_item_id' => $e->distributor_item_id,
            'item_name' => $e->distributorItem?->item_name ?? ('Item ID #'.$e->distributor_item_id),
            'satuan' => $e->satuan ?: null,
            'quantity' => (float) $e->quantity,
            'quantity_asli' => $e->quantity_asli !== null ? (float) $e->quantity_asli : null,
            'satuan_asli' => $e->satuan_asli,
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

        // Unggah berkas dari komputer: asal-usul email sebelumnya (jika ada)
        // tidak lagi berlaku untuk isi grid yang baru.
        $this->clearSourceEmail();
        $this->sourceFilename = $this->file->getClientOriginalName();

        $ext = strtolower($this->file->getClientOriginalExtension());
        $cleanExt = in_array($ext, ['xlsx', 'xls'], true) ? $ext : 'xlsx';
        // Lihat catatan di loadFromEmail(): tempnam() meninggalkan berkas kedua.
        $tempBase = tempnam(sys_get_temp_dir(), 'satoria_stock_');
        $tempClean = $tempBase.'.'.$cleanExt;
        copy($this->file->getRealPath(), $tempClean);

        try {
            // Hasilnya tampil di modal Ringkasan Berkas (lihat startQueue()).
            $this->processSpreadsheetPath($tempClean, 'File Excel');
        } finally {
            @unlink($tempClean);
            @unlink($tempBase);
        }
    }

    protected function processSpreadsheetPath(string $filePath, string $sourceDescription = 'File Excel'): bool
    {
        // Pemilihan cara baca dan pemecahan per cabang dikerjakan di satu
        // tempat yang sama dengan jalur otomasi email, supaya satu berkas tidak
        // pernah terbaca berbeda di dua jalur.
        //
        // Nama berkas ASLI ikut diserahkan: sebagian distributor tidak menulis
        // tanggal di dalam berkasnya sama sekali, hanya pada namanya.
        $importService = app(\App\Services\StockImportService::class);

        try {
            $reading = (new StockFileReader($importService))->read($filePath, $this->sourceFilename);
        } catch (\Throwable $e) {
            $this->addError('file', "Gagal membaca {$sourceDescription}: ".$e->getMessage());

            return false;
        }

        if (! $reading['ok']) {
            $this->addError('file', $reading['error'] ?? 'Judul kolom pada berkas Excel tidak dikenali.');

            return false;
        }

        if ($reading['placeholder']) {
            $this->addError('file', "Kode distributor masih berupa format contoh ('xxxx'). Silakan ganti dengan kode distributor sebenarnya (lihat sheet 'Daftar Distributor').");

            return false;
        }

        $buckets = $reading['buckets'];

        if ($buckets === []) {
            $this->addError('file', "Berkas ({$sourceDescription}) tidak berisi baris data.");

            return false;
        }

        // Seluruh cabang diperiksa SEBELUM satu baris pun diproses. Berkas yang
        // separuh cabangnya masuk dan separuh ditolak jauh lebih merepotkan
        // daripada berkas yang ditolak utuh dengan sebab yang jelas.
        $codes = array_keys($buckets);
        $distributors = Distributor::whereIn('distributor_code', $codes)->get()->keyBy('distributor_code');

        $unknown = array_values(array_diff($codes, $distributors->keys()->all()));
        if ($unknown !== []) {
            $this->addError('file', 'Kode distributor berikut belum ada di Master Distributor: '.implode(', ', $unknown).'. Minta Admin menambahkannya dulu, lalu unggah ulang berkas ini.');

            return false;
        }

        $inactive = $distributors->filter(fn (Distributor $d) => ! $d->is_active);
        if ($inactive->isNotEmpty()) {
            $names = $inactive->map(fn (Distributor $d) => $d->name.' ('.$d->distributor_code.')')->implode(', ');
            $this->addError('file', "Distributor berikut berstatus NON-AKTIF di Master Data: {$names}. Seluruh pengunggahan data stok ditolak.");

            return false;
        }

        // Tidak ada verifikasi whitelist pengirim di sini — DISENGAJA.
        //
        // Jalur ini selalu dijalankan oleh operator yang memilih sendiri berkas
        // atau emailnya, jadi keputusan ada di tangan manusia. Whitelist
        // ditegakkan pada jalur OTOMATIS (tanpa pengawasan) di
        // StockImportService::parseAndImportSpreadsheet().
        //
        // Yang tetap dijaga di sini: asal-usulnya dicatat ke riwayat snapshot
        // (lihat $sourceEmailFrom dan saveRows()).

        $batches = [];
        foreach ($buckets as $code => $branchRows) {
            $batch = $this->buildBranchBatch($distributors[$code], $branchRows, $importService);

            if ($batch === null) {
                // Sebab kegagalannya sudah dilaporkan oleh buildBranchBatch();
                // seluruh berkas dibatalkan, tidak ada cabang yang setengah masuk.
                return false;
            }

            $batches[] = $batch;
        }

        $this->importQueue = $batches;
        $this->queueIndex = 0;

        // Ringkasan seisi berkas dulu — cabang pertama baru dimuat ke grid
        // setelah operator menekan "Mulai" (startQueue()).
        $this->fileSummary = $this->buildFileSummary($batches, $sourceDescription);
        $this->fileSummary['format'] = $reading['group']?->name;
        $this->summarySelected = array_keys(array_filter(
            $this->fileSummary['unmapped'],
            fn ($u) => ! $u['queued']
        ));
        $this->showSummaryModal = true;

        return true;
    }

    /**
     * Ringkasan berkas untuk modal awal: per cabang, plus satu daftar gabungan
     * item belum ter-mapping dari SEMUA cabang.
     *
     * Item digabung per PEMILIK mapping (grup atau cabang), sama seperti
     * DistributorItem::queueFor() — nama yang sama di empat cabang SDL cukup
     * jadi satu baris, karena memang hanya satu baris antrean yang akan dibuat.
     *
     * @param  array<int, array<string, mixed>>  $batches
     * @return array{file: ?string, branches: array, unmapped: array<string, array>}
     */
    private function buildFileSummary(array $batches, string $sourceDescription): array
    {
        $distributors = Distributor::with('group')
            ->whereIn('id', array_column($batches, 'distributor_id'))
            ->get()
            ->keyBy('id');

        $branches = [];
        $unmapped = [];

        foreach ($batches as $i => $batch) {
            $dist = $distributors[$batch['distributor_id']] ?? null;

            $existing = StockEntry::where('tanggal', $batch['tanggal'])
                ->where('distributor_id', $batch['distributor_id'])
                ->count();

            $branches[] = [
                'index' => $i,
                'code' => $batch['distributor_code'],
                'name' => $batch['distributor_name'],
                'tanggal' => $batch['tanggal'],
                'rows' => count($batch['rows']),
                // Satu item bisa punya beberapa baris batch.
                'items' => count(array_unique(array_column($batch['rows'], 'distributor_item_id'))),
                'unmapped' => count($batch['skipped_rows_data']),
                'existing' => $existing,
            ];

            $ownerKey = $dist?->distributor_group_id ? 'g'.$dist->distributor_group_id : 'd'.$batch['distributor_id'];
            $ownerLabel = $dist?->group?->name ? 'Grup '.$dist->group->name : $batch['distributor_name'];

            foreach ($batch['skipped_rows_data'] as $item) {
                $key = $ownerKey.'|'.DistributorItem::normalizeName($item['item_name']);

                if (! isset($unmapped[$key])) {
                    $unmapped[$key] = [
                        'key' => $key,
                        'item_name' => $item['item_name'],
                        'satuan' => $item['satuan'] ?? null,
                        'quantity' => 0,
                        'owner' => $ownerLabel,
                        'distributor_id' => $batch['distributor_id'],
                        'branches' => [],
                        'queued' => (bool) ($item['queued'] ?? false),
                    ];
                }

                $unmapped[$key]['quantity'] += (float) ($item['quantity'] ?? 0);
                $unmapped[$key]['branches'][] = $batch['distributor_code'];
                $unmapped[$key]['satuan'] ??= $item['satuan'] ?? null;
            }
        }

        foreach ($unmapped as &$u) {
            $u['branches'] = array_values(array_unique($u['branches']));
        }
        unset($u);

        // Contoh: baris pertama yang benar-benar akan masuk grid.
        $sample = null;
        foreach ($batches as $batch) {
            if ($batch['rows'] !== []) {
                $row = array_values($batch['rows'])[0];
                $sample = [
                    'branch' => $batch['distributor_code'],
                    'fields' => [
                        ['label' => 'Tanggal Snapshot', 'value' => $batch['tanggal']],
                        ['label' => 'Kode Distributor', 'value' => $batch['distributor_code']],
                        ['label' => 'Nama Item Distributor', 'value' => $row['item_name']],
                        [
                            'label' => 'Quantity',
                            'value' => rtrim(rtrim(number_format((float) $row['quantity'], 2, ',', '.'), '0'), ','),
                            'note' => ! empty($row['satuan_asli'])
                                ? 'dikonversi dari '.rtrim(rtrim(number_format((float) $row['quantity_asli'], 2, ',', '.'), '0'), ',').' '.$row['satuan_asli']
                                : null,
                        ],
                        ['label' => 'Satuan (UOM)', 'value' => $row['satuan'] ?? null],
                        ['label' => 'Expired Date', 'value' => $row['expired_date'] ?? null],
                        ['label' => 'Batch / Lot', 'value' => $row['batch_no'] ?? null],
                    ],
                ];
                break;
            }
        }

        return [
            'file' => $this->sourceFilename ?: $sourceDescription,
            'branches' => $branches,
            'unmapped' => $unmapped,
            'sample' => $sample,
        ];
    }

    /** Ajukan item yang dicentang di modal ringkasan ke antrean mapping. */
    public function submitSummaryMapping(): void
    {
        Gate::authorize('create', StockEntry::class);

        $selected = array_intersect_key($this->fileSummary['unmapped'] ?? [], array_flip($this->summarySelected));
        $added = 0;

        DB::transaction(function () use ($selected, &$added) {
            foreach ($selected as $key => $u) {
                if ($u['queued']) {
                    continue;
                }

                $distributor = Distributor::find($u['distributor_id']);
                if ($distributor && DistributorItem::queueFor($distributor, $u['item_name'], $u['satuan'] ?: null)) {
                    $this->fileSummary['unmapped'][$key]['queued'] = true;
                    $added++;
                }
            }
        });

        $this->markQueuedInBatches();
        $this->summarySelected = [];

        $this->dispatch('toast',
            variant: 'success',
            title: "{$added} item diajukan ke antrean mapping",
            message: 'Admin tinggal memetakannya ke produk NetSuite di Master Mapping.',
        );
    }

    /**
     * Tandai item yang sudah diajukan pada tiap cabang di antrian, supaya
     * panel "belum ter-mapping" per cabang tidak menawarkan pengajuan ulang.
     */
    private function markQueuedInBatches(): void
    {
        $queued = [];
        foreach ($this->fileSummary['unmapped'] ?? [] as $u) {
            if ($u['queued']) {
                $queued[DistributorItem::normalizeName($u['item_name'])] = true;
            }
        }

        foreach ($this->importQueue as &$batch) {
            foreach ($batch['skipped_rows_data'] as &$item) {
                if (isset($queued[DistributorItem::normalizeName($item['item_name'])])) {
                    $item['queued'] = true;
                }
            }
            unset($item);
        }
        unset($batch);
    }

    /** Tutup ringkasan dan mulai kerjakan cabang pertama. */
    public function startQueue(): void
    {
        $this->showSummaryModal = false;

        if (! $this->loadQueueItem(0)) {
            return;
        }

        if (! $this->showConflictModal) {
            $total = count($this->importQueue);
            $this->dispatch('toast',
                variant: 'info',
                title: 'Berkas dimuat ke grid',
                message: $total > 1
                    ? "Cabang 1 dari {$total}: {$this->importQueue[0]['distributor_name']}."
                    : count($this->rows)." baris dimuat untuk {$this->importQueue[0]['distributor_name']}.",
            );
        }
    }

    /** Batalkan berkas dari modal ringkasan. */
    public function cancelSummary(): void
    {
        $this->showSummaryModal = false;
        $this->fileSummary = [];
        $this->summarySelected = [];
        $this->clearImportQueue();
    }

    /**
     * Susun satu "batch" siap-grid untuk sebuah cabang.
     *
     * @param  array<int, array{row: array<int, mixed>, excel_row: int}>  $branchRows
     * @return ?array  null bila berkas harus ditolak (sebabnya sudah dilaporkan)
     */
    private function buildBranchBatch(
        Distributor $distributor,
        array $branchRows,
        \App\Services\StockImportService $importService
    ): ?array {
        $firstRow = $branchRows[0]['row'];
        $reader = $branchRows[0]['reader'];

        $rawTanggal = $reader->rawTanggal($firstRow);
        if (in_array(trim(strtoupper((string) $rawTanggal)), ['DD/MM/YYYY', 'YYYY-MM-DD', 'DD-MM-YYYY'], true)) {
            $this->addError('file', "Tanggal snapshot masih berupa format contoh ('DD/MM/YYYY'). Silakan isi dengan tanggal yang valid (contoh: ".now()->format('d/m/Y').').');

            return null;
        }

        $tanggal = $reader->tanggal($firstRow);
        if (! $tanggal) {
            $this->addError('file', "Format tanggal snapshot pada berkas Excel tidak dikenali ('{$rawTanggal}') untuk {$distributor->name}. Harap gunakan format tanggal yang valid (contoh: ".now()->format('d/m/Y').').');

            return null;
        }

        // Pemetaan milik GRUP berlaku untuk seluruh cabangnya; baris milik
        // cabang hanya ada sebagai pengecualian dan menimpa yang segrup.
        $knownItems = DistributorItem::lookupFor($distributor);

        $skipped = [];
        $skippedRowsData = [];
        $parsedRows = [];

        foreach ($branchRows as $entry) {
            $r = $entry['row'];
            $excelRow = $entry['excel_row'];
            // Tiap baris membawa pembacanya sendiri: satu berkas SDL memuat
            // beberapa sheet, masing-masing dengan baris header sendiri.
            $reader = $entry['reader'];

            $itemName = $reader->itemName($r);
            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)));
            $distItem = $knownItems->get($key);

            $qty = $reader->quantity($r);
            $satuan = $reader->satuan($r);
            $ed = $reader->expiredDate($r);
            $edFormatted = $ed ? \Carbon\Carbon::parse($ed)->format('d/m/Y') : null;
            $batch = $reader->batchNo($r);

            if (! $distItem || ! $distItem->isMapped()) {
                $skipped[] = $itemName;

                if (! isset($skippedRowsData[$key])) {
                    $skippedRowsData[$key] = [
                        'item_name' => $itemName,
                        'satuan' => $satuan ?: null,
                        'quantity' => $qty,
                        'expired_date' => $edFormatted,
                        'batch_no' => $batch ?: null,
                        // Sudah ada di Master Mapping, tinggal dipetakan Admin.
                        'queued' => $distItem !== null,
                    ];
                } else {
                    $skippedRowsData[$key]['quantity'] += $qty;
                    $skippedRowsData[$key]['batch_no'] = $this->mergeBatchNumbers($skippedRowsData[$key]['batch_no'] ?? null, $batch);
                    $skippedRowsData[$key]['expired_date'] = $this->mergeExpiredDates($skippedRowsData[$key]['expired_date'] ?? null, $edFormatted);
                }

                // Ikut divalidasi walau tidak disimpan — lihat catatan pada
                // StockImportService::groupRowsByItemAndBatch().
                $parsedRows[] = [
                    'item_id' => 'x'.$key,
                    'item_name' => $itemName,
                    'qty' => $qty,
                    'satuan' => $satuan,
                    'ed' => $ed,
                    'batch' => $batch,
                    'excel_row' => $excelRow,
                    'save' => false,
                ];

                continue;
            }

            $parsedRows[] = [
                'item_id' => $distItem->id,
                'item_name' => $distItem->item_name,
                'qty' => $qty,
                'satuan' => $satuan ?: null,
                'ed' => $ed,
                'batch' => $batch,
                'excel_row' => $excelRow,
                'mapped' => $distItem->isMapped(),
            ];
        }

        // Pengelompokan per (item, batch) memakai definisi yang sama persis
        // dengan jalur otomasi email — termasuk dua aturan penolakannya.
        $grouping = $importService->groupRowsByItemAndBatch($parsedRows);

        if (! $grouping['ok']) {
            foreach ($grouping['errors'] as $err) {
                $this->addError('file', $distributor->name.': '.$err);
            }

            return null;
        }

        // Grid memakai ED berformat d/m/Y; pengelompokan bekerja dalam ISO.
        $rows = [];
        foreach ($grouping['rows'] as $gKey => $g) {
            $rows[$gKey] = [
                'distributor_item_id' => $g['distributor_item_id'],
                'item_name' => $g['item_name'],
                'satuan' => $g['satuan'],
                'quantity' => $g['quantity'],
                'quantity_asli' => $g['quantity_asli'],
                'satuan_asli' => $g['satuan_asli'],
                'expired_date' => $g['expired_date'] ? \Carbon\Carbon::parse($g['expired_date'])->format('d/m/Y') : null,
                'batch_no' => $g['batch_no'],
                'mapped' => true,
            ];
        }

        return [
            'tanggal' => $tanggal,
            'distributor_id' => $distributor->id,
            'distributor_name' => $distributor->name,
            'distributor_code' => $distributor->distributor_code,
            'rows' => array_values($rows),
            'skipped' => array_values(array_unique($skipped)),
            'skipped_rows_data' => array_values($skippedRowsData),
        ];
    }

    /**
     * Tampilkan satu cabang dari antrian ke grid.
     *
     * Pemeriksaan bentrok snapshot dilakukan per cabang di sini, bukan sekali
     * di muka: cabang A bisa saja sudah punya data hari ini sementara cabang B
     * belum, dan keduanya berhak atas pertanyaan Gabung/Ganti-nya sendiri.
     */
    private function loadQueueItem(int $index): bool
    {
        if (! isset($this->importQueue[$index])) {
            return false;
        }

        $this->queueIndex = $index;
        $batch = $this->importQueue[$index];

        $existingCount = StockEntry::query()
            ->where('tanggal', $batch['tanggal'])
            ->where('distributor_id', $batch['distributor_id'])
            ->count();

        if ($existingCount > 0) {
            $this->pendingImportData = [
                'tanggal' => $batch['tanggal'],
                'distributor_id' => $batch['distributor_id'],
                'distributor_name' => $batch['distributor_name'],
                'existing_count' => $existingCount,
                'new_rows' => $batch['rows'],
                'skipped' => $batch['skipped'],
                'skipped_rows_data' => $batch['skipped_rows_data'],
            ];
            $this->showConflictModal = true;

            return true;
        }

        $this->applyBatchToGrid($batch);

        return true;
    }

    /** @param array<string, mixed> $batch */
    private function applyBatchToGrid(array $batch): void
    {
        $this->tanggal = $batch['tanggal'];
        $this->distributorId = $batch['distributor_id'];
        $this->rows = array_values($batch['rows']);
        $this->skippedItems = $batch['skipped'];
        $this->skippedRowsData = $batch['skipped_rows_data'];
        $this->removedRowKeys = [];
        $this->replaceExistingSnapshot = false;
        $this->dispatch('rows-loaded', rows: $this->rows);
        $this->dispatch('file-imported');
    }

    /** Cabang berikutnya dalam antrian, atau null bila ini yang terakhir. */
    public function getNextQueueBranchProperty(): ?string
    {
        return $this->importQueue[$this->queueIndex + 1]['distributor_name'] ?? null;
    }

    /** Cabang sebelumnya dalam antrian, atau null bila ini yang pertama. */
    public function getPreviousQueueBranchProperty(): ?string
    {
        return $this->queueIndex > 0
            ? ($this->importQueue[$this->queueIndex - 1]['distributor_name'] ?? null)
            : null;
    }

    /** Lanjut ke cabang berikutnya tanpa menyimpan cabang yang sedang tampil. */
    public function skipQueueItem(): void
    {
        $current = $this->importQueue[$this->queueIndex]['distributor_name'] ?? 'Cabang ini';

        if (! $this->loadQueueItem($this->queueIndex + 1)) {
            $this->resetAfterImport();
            $this->dispatch('toast', variant: 'info', title: "{$current} dilewati", message: 'Seluruh cabang pada berkas ini sudah selesai diproses.');

            return;
        }

        session()->flash('status', "{$current} dilewati tanpa disimpan.");
    }

    /**
     * Pindah ke cabang mana pun dalam antrian, maju maupun mundur.
     *
     * Isi grid selalu dimuat ulang dari hasil pembacaan berkas, jadi kembali
     * ke cabang sebelumnya berarti melihat data berkas apa adanya — bukan
     * koreksi yang belum sempat disimpan, dan bukan pula snapshot yang sudah
     * tersimpan. Karena itu tombolnya meminta konfirmasi.
     */
    public function goToQueueItem(int $index): void
    {
        if ($index === $this->queueIndex || ! isset($this->importQueue[$index])) {
            return;
        }

        $target = $this->importQueue[$index]['distributor_name'];

        $this->loadQueueItem($index);

        session()->flash('status', "Berpindah ke cabang ".($index + 1)." dari ".count($this->importQueue).": {$target}.");
    }

    /** Kembali ke cabang sebelumnya dalam antrian. */
    public function previousQueueItem(): void
    {
        $this->goToQueueItem($this->queueIndex - 1);
    }

    public function clearImportQueue(): void
    {
        $this->importQueue = [];
        $this->queueIndex = 0;
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
        $this->removedRowKeys = [];
        $this->replaceExistingSnapshot = ($mode !== 'merge');

        if ($mode === 'merge') {
            $existingEntries = StockEntry::query()
                ->with('distributorItem.netsuiteItem')
                ->where('tanggal', $this->tanggal)
                ->where('distributor_id', $this->distributorId)
                ->get();

            // Di-key per (item|batch). Penggabungan hanya terjadi bila batch-nya
            // memang sama — batch berbeda tetap berdiri sebagai baris sendiri,
            // bukan dilebur seperti sebelumnya.
            $mergedRows = $pending['new_rows'];

            foreach ($existingEntries as $entry) {
                if (! $entry->distributorItem?->isMapped()) {
                    continue;
                }

                $itemId = $entry->distributor_item_id;
                $key = self::rowKey($itemId, $entry->batch_no);

                if (isset($mergedRows[$key])) {
                    // Batch identik -> satu tumpukan stok yang sama, dijumlah.
                    $mergedRows[$key]['quantity'] += (float) $entry->quantity;
                    // Hasil gabungan dua sumber tidak lagi punya satu nilai asli.
                    $mergedRows[$key]['quantity_asli'] = null;
                    $mergedRows[$key]['satuan_asli'] = null;
                } else {
                    $mergedRows[$key] = [
                        'distributor_item_id' => $itemId,
                        'item_name' => $entry->distributorItem?->item_name ?? ('Item ID #'.$itemId),
                        'satuan' => $entry->satuan ?: null,
                        'quantity' => (float) $entry->quantity,
                        'quantity_asli' => $entry->quantity_asli !== null ? (float) $entry->quantity_asli : null,
                        'satuan_asli' => $entry->satuan_asli,
                        'expired_date' => optional($entry->expired_date)->format('d/m/Y'),
                        'batch_no' => $entry->batch_no,
                        'mapped' => true,
                    ];
                }
            }

            $this->rows = array_values($mergedRows);
            session()->flash('status', 'Import selesai (Mode Gabung Data): '.count($this->rows)." baris stok berhasil digabungkan untuk {$pending['distributor_name']} — {$this->tanggal}.");
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
        $this->replaceExistingSnapshot = false;
        $this->pendingImportData = [];
    }

    /**
     * Tombol "Lewati" pada pilihan Gabung/Timpa: cabang ini tidak dimuat ke
     * grid. Masih ada cabang berikutnya -> lanjut ke sana; ini cabang terakhir
     * (atau satu-satunya) -> antrian selesai dan grid dikosongkan.
     */
    public function skipConflict(): void
    {
        $skipped = $this->pendingImportData['distributor_name'] ?? 'Cabang ini';
        $this->cancelConflictModal();

        if ($this->importQueue !== [] && $this->loadQueueItem($this->queueIndex + 1)) {
            if (! $this->showConflictModal) {
                $this->dispatch('toast',
                    variant: 'info',
                    title: "{$skipped} dilewati",
                    message: 'Lanjut ke cabang '.($this->queueIndex + 1).' dari '.count($this->importQueue).": {$this->importQueue[$this->queueIndex]['distributor_name']}.",
                );
            }

            return;
        }

        // Cabang terakhir: tidak ada yang dimuat, dan sisa isi grid dari cabang
        // sebelumnya tidak boleh terlihat seolah milik cabang yang dilewati.
        $this->resetAfterImport();

        $this->dispatch('toast',
            variant: 'info',
            title: "{$skipped} dilewati",
            message: 'Tidak ada data yang dimuat ke grid. Data lama di database tidak berubah.',
        );
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
                'satuan' => null,
                'quantity' => 0,
                'expired_date' => null,
                'batch_no' => null,
            ], $this->skippedItems);

        $addedCount = 0;
        DB::transaction(function () use ($itemsToProcess, &$addedCount) {
            $distributor = Distributor::find($this->distributorId);

            foreach ($itemsToProcess as $itemData) {
                if (DistributorItem::queueFor($distributor, $itemData['item_name'], $itemData['satuan'] ?? null)) {
                    $addedCount++;
                }
            }
        });

        $this->skippedItems = [];
        $this->skippedRowsData = [];
        $this->showRequestModal = false;

        session()->flash('status', "{$addedCount} item berhasil diajukan ke antrean mapping. Silakan petakan item tersebut di Master Mapping sebelum data stoknya di-upload.");
    }

    public function openSingleRequestModal(): void
    {
        $this->requestItemName = '';
        $this->requestSatuan = '';
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
            'requestSatuan' => ['nullable', 'string', 'max:50'],
        ]);

        $distItem = DistributorItem::queueFor(
            Distributor::find($this->distributorId),
            $this->requestItemName,
            trim($this->requestSatuan) ?: null,
        );

        $this->showSingleRequestModal = false;
        $this->reset(['requestItemName', 'requestSatuan']);

        if ($distItem) {
            session()->flash('status', "Item '{$distItem->item_name}' berhasil diajukan ke antrean mapping. Silakan petakan item tersebut di Master Mapping.");
        }
    }

    public function addRow(): void
    {
        if (! $this->addItemId || ! $this->distributorId) {
            return;
        }

        $item = DistributorItem::find($this->addItemId);
        if (! $this->itemBelongsToCurrentDistributor($item) || ! $item->isMapped()) {
            return;
        }

        // Baris baru selalu berbatch kosong. Kalau sudah ada satu baris item ini
        // yang batch-nya juga masih kosong, menambah lagi hanya menghasilkan
        // dua baris kembar yang bentrok saat disimpan.
        $emptyBatchKey = self::rowKey($item->id, null);
        $already = collect($this->rows)
            ->first(fn ($r) => self::rowKey((int) ($r['distributor_item_id'] ?? 0), $r['batch_no'] ?? null) === $emptyBatchKey);

        if ($already) {
            return;
        }

        $this->rows[] = [
            'distributor_item_id' => $item->id,
            'item_name' => $item->item_name,
            'satuan' => null,
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
    public function markRowRemoved(int $distributorItemId, ?string $batchNo = null): void
    {
        // Batch ikut menentukan baris mana yang dihapus. Tanpa itu, menghapus
        // satu batch akan membuang SEMUA batch milik item tersebut.
        $key = self::rowKey($distributorItemId, $batchNo);

        if (! in_array($key, $this->removedRowKeys, true)) {
            $this->removedRowKeys[] = $key;
        }

        $this->rows = collect($this->rows)
            ->reject(fn ($r) => self::rowKey((int) ($r['distributor_item_id'] ?? 0), $r['batch_no'] ?? null) === $key)
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
        $this->removedRowKeys = [];
        $this->replaceExistingSnapshot = false;
        if ($this->tanggal && preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', trim($this->tanggal), $m)) {
            $this->tanggal = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
    }


    public function updatedDistributorId(): void
    {
        $this->removedRowKeys = [];
        $this->replaceExistingSnapshot = false;
        // Operator berpindah distributor sendiri: antrian cabang dari berkas
        // sebelumnya tidak lagi menggambarkan apa yang ada di grid.
        $this->clearImportQueue();
    }

    /**
     * Baris pemetaan ini boleh dipakai distributor yang sedang dikerjakan?
     *
     * Sejak pemetaan dimiliki GRUP, kepemilikan tidak lagi bisa diperiksa
     * dengan membandingkan distributor_id: baris milik grup memang tidak punya
     * cabang. Yang diperiksa kini: milik cabang ini sendiri, atau milik grup
     * usahanya.
     */
    private function itemBelongsToCurrentDistributor(?DistributorItem $item): bool
    {
        if (! $item || ! $this->distributorId) {
            return false;
        }

        if ($item->distributor_id !== null) {
            return $item->distributor_id === $this->distributorId;
        }

        return $item->distributor_group_id !== null
            && $item->distributor_group_id === Distributor::find($this->distributorId)?->distributor_group_id;
    }

    public function saveRows(array $rows): void
    {
        Gate::authorize('create', StockEntry::class);

        if (! $this->tanggal || ! $this->distributorId) {
            $this->addError('save', 'Tanggal dan distributor wajib dipilih sebelum menyimpan.');

            return;
        }

        $dist = Distributor::find($this->distributorId);
        if (! $dist || ! $dist->is_active) {
            $this->addError('save', "Distributor berstatus NON-AKTIF di Master Data. Penyimpanan stok ditolak.");

            return;
        }

        $mappedCount = 0;
        $unmappedCount = 0;
        $deletedCount = 0;

        $isExistingSnapshot = StockEntry::where('tanggal', $this->tanggal)
            ->where('distributor_id', $this->distributorId)
            ->exists();

        // Kunci baris (item|batch) yang benar-benar tersimpan, dipakai untuk
        // menentukan apa yang boleh dihapus.
        $savedRowKeys = [];

        DB::transaction(function () use ($rows, $isExistingSnapshot, &$mappedCount, &$unmappedCount, &$deletedCount, &$savedRowKeys) {
            foreach ($rows as $row) {
                if (empty($row['distributor_item_id'])) {
                    continue;
                }

                $item = DistributorItem::find($row['distributor_item_id']);
                if (! $this->itemBelongsToCurrentDistributor($item)) {
                    continue;
                }

                // Proteksi: Item yang belum ter-mapping TIDAK BISA disimpan ke stock_entries
                if (! $item->isMapped()) {
                    $unmappedCount++;
                    continue;
                }

                $batchNo = ! empty($row['batch_no']) ? trim((string) $row['batch_no']) : null;

                // Baris yang belum dikonversi (mis. satuan BOX diketik manual di
                // grid) dikonversi di sini; yang sudah dikonversi saat impor
                // membawa satuan_asli dan dilewati.
                if (empty($row['satuan_asli'])) {
                    $c = \App\Models\UnitConversion::apply((float) ($row['quantity'] ?? 0), ($row['satuan'] ?? null) ?: null);
                    $row['quantity'] = $c['quantity'];
                    $row['satuan'] = $c['satuan'];
                    $row['quantity_asli'] = $c['quantity_asli'];
                    $row['satuan_asli'] = $c['satuan_asli'];
                }

                StockEntry::updateOrCreate(
                    [
                        'tanggal' => $this->tanggal,
                        // Cabang ikut jadi kunci: satu baris pemetaan milik grup
                        // dipakai banyak cabang, jadi tanpa ini snapshot cabang
                        // berikutnya akan menimpa yang sebelumnya.
                        'distributor_id' => $this->distributorId,
                        'distributor_item_id' => $item->id,
                        // Batch bagian dari identitas: dua batch pada item &
                        // tanggal yang sama adalah dua baris, bukan timpa.
                        'batch_no' => $batchNo,
                    ],
                    [
                        'distributor_id' => $this->distributorId,
                        'quantity' => (float) ($row['quantity'] ?? 0),
                        'satuan' => ($row['satuan'] ?? null) ?: null,
                        'quantity_asli' => isset($row['quantity_asli']) && $row['quantity_asli'] !== '' ? (float) $row['quantity_asli'] : null,
                        'satuan_asli' => ($row['satuan_asli'] ?? null) ?: null,
                        // Kalau tidak terbaca, simpan NULL — JANGAN teruskan
                        // string mentahnya. Kolomnya bertipe `date`, sehingga
                        // nilai seperti "ED menyusul" membuat seluruh transaksi
                        // penyimpanan gagal dengan error SQL, bukan sekadar
                        // satu sel yang kosong.
                        'expired_date' => ! empty($row['expired_date']) ? $this->parseExcelDate($row['expired_date']) : null,
                        'uploaded_by' => Auth::id(),
                    ]
                );

                $savedRowKeys[] = self::rowKey($item->id, $batchNo);
                $item->isMapped() ? $mappedCount++ : $unmappedCount++;
            }

            // Hapus HANYA baris yang memang diklik hapus oleh pengguna, dan
            // dibatasi tanggal + distributor yang sedang dikerjakan.
            //
            // Item yang ikut dikirim grid dikecualikan: pengguna bisa saja
            // menghapus sebuah baris lalu menambahkannya kembali sebelum
            // menyimpan. Tanpa pengecualian ini baris tersebut akan di-upsert
            // lalu langsung dihapus lagi pada transaksi yang sama.
            $keysToDelete = array_values(array_diff($this->removedRowKeys, $savedRowKeys));

            // Baris kandidat dievaluasi per (item|batch), bukan per item, supaya
            // menghapus satu batch tidak ikut membuang batch lain milik item
            // yang sama.
            $candidates = StockEntry::query()
                ->where('tanggal', $this->tanggal)
                ->where('distributor_id', $this->distributorId)
                ->get();

            $toDelete = $candidates->filter(function (StockEntry $e) use ($keysToDelete, $savedRowKeys) {
                $key = self::rowKey($e->distributor_item_id, $e->batch_no);

                if (in_array($key, $keysToDelete, true)) {
                    return true;
                }

                // Mode "Timpa/Revisi": berkas baru adalah kebenaran UTUH untuk
                // tanggal + distributor ini, jadi baris lama yang tidak ada di
                // berkas baru ikut dibuang. Inilah yang membedakannya dari mode
                // "Gabung"; tanpa ini keduanya menghasilkan data yang sama.
                return $this->replaceExistingSnapshot && ! in_array($key, $savedRowKeys, true);
            });

            if ($toDelete->isNotEmpty()) {
                foreach ($toDelete as $entry) {
                    Gate::authorize('delete', $entry);
                    $entry->delete();
                    $deletedCount++;
                }
            }

            if ($mappedCount > 0 || $deletedCount > 0) {
                $userName = Auth::user()?->name ?? 'User';
                $actionType = $isExistingSnapshot ? 'edit' : 'upload';
                $desc = $isExistingSnapshot
                    ? "Koreksi/Update snapshot oleh {$userName} ({$mappedCount} SKU tersimpan" . ($deletedCount > 0 ? ", {$deletedCount} baris dihapus" : "") . ")"
                    : "Upload snapshot baru oleh {$userName} ({$mappedCount} SKU)";

                // Sebutkan asal emailnya di deskripsi, bukan hanya di metadata:
                // inilah teks yang dibaca orang di halaman Riwayat.
                if ($this->sourceEmailUid !== null) {
                    $desc .= ' — dari email '.($this->sourceEmailFrom ?: 'pengirim tidak diketahui')
                        .' (UID #'.$this->sourceEmailUid.')';
                }

                // Kolom description hanya varchar(255); metadata menyimpan versi
                // utuhnya, jadi aman dipotong di sini.
                $desc = \Illuminate\Support\Str::limit($desc, 250);

                StockSnapshotActivity::create([
                    'tanggal' => $this->tanggal,
                    'distributor_id' => $this->distributorId,
                    'user_id' => Auth::id(),
                    'action' => $actionType,
                    'description' => $desc,
                    'metadata' => [
                        'mapped_count' => $mappedCount,
                        'unmapped_count' => $unmappedCount,
                        'deleted_count' => $deletedCount,
                    ] + $this->sourceMetadata(),
                ]);
            }
        });

        $dist = Distributor::find($this->distributorId);
        $tanggalLabel = \Illuminate\Support\Carbon::parse($this->tanggal)->translatedFormat('d M Y');

        $details = ["{$mappedCount} item tersimpan · {$tanggalLabel}"];
        if ($unmappedCount > 0) {
            $details[] = "{$unmappedCount} item belum ter-mapping dilewati";
        }
        if ($deletedCount > 0) {
            $details[] = "{$deletedCount} baris dihapus";
        }

        $toast = [
            'variant' => $unmappedCount > 0 ? 'warning' : 'success',
            'title' => ($dist?->name ?? 'Stock').' tersimpan',
            'message' => implode(' · ', $details),
        ];

        // Berkas berisi beberapa cabang: begitu cabang ini tersimpan, cabang
        // berikutnya langsung dimuat ke grid supaya operator tidak perlu
        // mengunggah ulang berkas yang sama berkali-kali.
        if ($this->importQueue !== []) {
            $position = $this->queueIndex + 1;
            $total = count($this->importQueue);

            if ($this->loadQueueItem($this->queueIndex + 1)) {
                $next = $this->importQueue[$this->queueIndex]['distributor_name'];
                $toast['message'] .= "\nCabang {$position}/{$total} selesai — lanjut ke {$next}.";
                $this->dispatch('toast', ...$toast);

                return;
            }

            $toast['message'] .= "\nSeluruh {$total} cabang pada berkas ini sudah selesai.";
            $this->dispatch('toast', ...$toast);

            // Berkas selesai: halaman kembali bersih, siap untuk berkas berikutnya.
            $this->resetAfterImport();

            return;
        }

        // Simpan dari "Muat Data Manual": tetap di data yang sedang dikerjakan.
        $this->dispatch('toast', ...$toast);
        $this->loadExisting();
    }

    /** Kosongkan grid, antrian, berkas, dan pilihan — kembali ke tab Import. */
    private function resetAfterImport(): void
    {
        $this->resetManualForm();
        $this->clearSourceEmail();
        $this->reset(['file', 'fileSummary', 'summarySelected', 'showSummaryModal', 'pendingImportData', 'showConflictModal']);
        $this->replaceExistingSnapshot = false;
        $this->activeTab = 'import';
        $this->resetErrorBag();

        $this->dispatch('rows-loaded', rows: []);
        $this->dispatch('file-imported');
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
            ['ED', 'DISARANKAN', '31/12/2027', 'Tanggal kedaluwarsa (Expired Date) produk (format DD/MM/YYYY, contoh: 31/12/2027). Satu nomor batch hanya boleh punya SATU tanggal ED.'],
            ['Batch No', 'WAJIB', '026C05', 'Nomor batch produksi. Setiap baris WAJIB diisi. Satu item boleh punya beberapa baris dengan batch berbeda — tiap batch dicatat terpisah, tidak digabung.'],
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
            "5a. Kolom 'Batch No' WAJIB diisi pada setiap baris. Berkas dengan batch kosong akan ditolak.",
            "5b. Satu item boleh ditulis beberapa baris dengan nomor batch berbeda — setiap batch disimpan sebagai baris tersendiri, TIDAK digabung.",
            "5c. Nomor batch yang sama WAJIB memakai tanggal ED yang sama. Bila berbeda, berkas ditolak untuk dikoreksi.",
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

        $iso1 = $d1 ? $this->parseExcelDate($d1) : null;
        $iso2 = $d2 ? $this->parseExcelDate($d2) : null;

        if ($iso1 && $iso2) {
            $earliestIso = ($iso1 <= $iso2) ? $iso1 : $iso2;

            return \Carbon\Carbon::createFromFormat('Y-m-d', $earliestIso)->format('d/m/Y');
        }

        if ($iso1) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $iso1)->format('d/m/Y');
        }

        if ($iso2) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $iso2)->format('d/m/Y');
        }

        return $d1 ?: $d2;
    }

    public function render()
    {
        return view('livewire.stock.upload', [
            'distributors' => Distributor::where('is_active', true)->orderBy('name')->get(),
            'availableItems' => $this->distributorId
                ? DistributorItem::mapped()
                    ->where('distributor_id', $this->distributorId)
                    // Hanya sembunyikan item yang SUDAH punya baris berbatch
                    // kosong di grid. Item yang sudah ada dengan batch terisi
                    // tetap boleh dipilih lagi — justru itu cara menambahkan
                    // batch kedua secara manual.
                    ->whereNotIn('id', collect($this->rows)
                        ->filter(fn ($r) => trim((string) ($r['batch_no'] ?? '')) === '')
                        ->pluck('distributor_item_id')->all() ?: [0])
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
