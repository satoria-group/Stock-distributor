<?php

namespace App\Services;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEmailLog;
use App\Models\StockEntry;
use App\Models\StockSnapshotActivity;
use App\Support\StockFileReader;
use App\Support\StockRowReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StockImportService
{
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

    /**
     * Parse date value from Excel into Y-m-d string.
     */
    public function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (self::DATE_FORMATS as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed && $parsed->format($format) === $value) {
                return $parsed->toDateString();
            }
        }

        return null;
    }

    /**
     * Baca nilai Quantity dari sel Excel menjadi float.
     *
     * Sebelumnya: str_replace([',', ' '], '', $raw) — semua koma dibuang mentah.
     * Itu benar untuk pemisah ribuan gaya Inggris ("1,234" -> 1234) tapi SALAH
     * TOTAL untuk format Indonesia, di mana koma adalah pemisah DESIMAL:
     * "1,5" terbaca 15 — sepuluh kali lipat, diam-diam, tanpa error.
     *
     * Aturan yang dipakai di sini:
     *  - Nilai numerik asli dari PhpSpreadsheet dipakai apa adanya.
     *  - Bila ada '.' DAN ',', pemisah yang muncul TERAKHIR adalah desimal
     *    ("1.234,56" -> 1234.56 ; "1,234.56" -> 1234.56).
     *  - Bila hanya satu jenis pemisah dan diikuti TEPAT 3 digit sampai akhir,
     *    itu dianggap pemisah ribuan ("1,234" -> 1234). Ini konvensi yang
     *    paling lazim di berkas stok dan mempertahankan perilaku lama.
     *  - Selain itu, pemisah tunggal dianggap desimal ("1,5" -> 1.5).
     */
    public function parseQuantity(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        // Buang spasi (termasuk non-breaking space yang sering ikut dari Excel).
        $raw = preg_replace('/[\s\x{00A0}]+/u', '', $raw) ?? '';

        if ($raw === '') {
            return 0.0;
        }

        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '+-');

        $lastDot = strrpos($raw, '.');
        $lastComma = strrpos($raw, ',');

        if ($lastDot !== false && $lastComma !== false) {
            // Keduanya ada: yang terakhir adalah pemisah desimal.
            [$decimalSep, $groupSep] = $lastComma > $lastDot ? [',', '.'] : ['.', ','];
        } elseif ($lastDot !== false || $lastComma !== false) {
            $sep = $lastDot !== false ? '.' : ',';
            $pos = $lastDot !== false ? $lastDot : $lastComma;
            $digitsAfter = strlen($raw) - $pos - 1;
            $occurrences = substr_count($raw, $sep);

            // Tepat 3 digit di belakang DAN tanpa pemisah lain di depan yang
            // bertentangan -> pemisah ribuan. Selain itu -> desimal.
            $isGrouping = $digitsAfter === 3 && ($occurrences > 1 || $pos > 0);

            [$decimalSep, $groupSep] = $isGrouping ? ['', $sep] : [$sep, ''];
        } else {
            [$decimalSep, $groupSep] = ['', ''];
        }

        if ($groupSep !== '') {
            $raw = str_replace($groupSep, '', $raw);
        }
        if ($decimalSep !== '') {
            $raw = str_replace($decimalSep, '.', $raw);
        }

        // Sisakan hanya angka dan satu titik desimal.
        $raw = preg_replace('/[^0-9.]/', '', $raw) ?? '';

        $result = is_numeric($raw) ? (float) $raw : 0.0;

        return $negative ? -$result : $result;
    }

    /**
     * Normalisasi nomor batch untuk dipakai sebagai bagian identitas baris.
     * Spasi berlebih dan beda huruf besar-kecil TIDAK boleh menghasilkan dua
     * batch berbeda — "IGMP-02" dan "igmp-02 " adalah batch yang sama.
     */
    public function normalizeBatch(?string $batch): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $batch) ?? ''));
    }

    /**
     * Kelompokkan baris Excel per (item, batch) — BUKAN per item saja.
     *
     * Sebelumnya seluruh baris satu item dilebur menjadi satu: kuantitas
     * dijumlah, nomor batch disambung koma, dan ED diambil yang paling awal.
     * Akibatnya identitas batch hilang dan FEFO salah — stok dengan ED 2029
     * ikut ditandai mendekati kedaluwarsa hanya karena satu batch lain di item
     * yang sama ber-ED 2028.
     *
     * Dua aturan penolakan (keputusan pengguna, keduanya menolak SELURUH berkas
     * agar tidak ada data setengah benar yang diam-diam tersimpan):
     *
     *  A. Batch No kosong  -> ditolak. Batch kini bagian dari identitas baris;
     *     tanpa itu dua baris berbeda tidak bisa dibedakan.
     *  B. Batch sama tapi ED berbeda -> ditolak. Secara farmasi satu batch
     *     hanya punya satu ED, jadi ini hampir pasti salah input.
     *
     * Baris dengan (item, batch) yang benar-benar identik tetap dijumlahkan —
     * itu memang satu tumpukan stok yang sama.
     *
     * Baris yang itemnya belum ter-mapping tetap IKUT DIVALIDASI (tandai
     * 'save' => false) walau tidak masuk hasil. Kalau tidak, berkas dengan
     * batch kosong pada item belum ter-mapping akan lolos hari ini lalu ditolak
     * begitu item tersebut dipetakan — kegagalan yang muncul belakangan dan
     * membingungkan.
     *
     * @param  array<int, array{item_id:int|string, item_name:string, qty:float, satuan:?string, ed:?string, batch:?string, excel_row:int, save?:bool}>  $rows
     * @return array{ok: bool, rows: array<string, array>, errors: array<int, string>}
     */
    public function groupRowsByItemAndBatch(array $rows): array
    {
        $grouped = [];
        $missingBatch = [];
        $edConflicts = [];

        foreach ($rows as $r) {
            $batch = $this->normalizeBatch($r['batch'] ?? null);
            $save = $r['save'] ?? true;

            // Konversi satuan (mis. BOX -> PCS) sebelum dijumlahkan, supaya
            // baris BOX dan PCS pada batch yang sama terjumlah dengan benar.
            $converted = \App\Models\UnitConversion::apply((float) $r['qty'], $r['satuan'] ?? null);
            $r['qty'] = $converted['quantity'];
            $r['satuan'] = $converted['satuan'];

            if ($batch === '') {
                $missingBatch[] = "baris {$r['excel_row']} ({$r['item_name']})";

                continue;
            }

            $key = $r['item_id'].'|'.$batch;

            if (! $save) {
                // Hanya divalidasi (deteksi bentrok ED), tidak ikut disimpan.
                $key = 'unmapped:'.$key;
            }

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'distributor_item_id' => $r['item_id'],
                    'item_name' => $r['item_name'],
                    'quantity' => $r['qty'],
                    'satuan' => $r['satuan'] ?: null,
                    'expired_date' => $r['ed'],
                    'batch_no' => trim((string) $r['batch']),
                    'excel_row' => $r['excel_row'],
                    'save' => $save,
                    'quantity_asli' => $converted['quantity_asli'],
                    'satuan_asli' => $converted['satuan_asli'],
                ];

                continue;
            }

            // Batch yang sama wajib punya ED yang sama. Null dianggap berbeda
            // dari tanggal terisi: satu baris menyebut ED dan satunya tidak
            // adalah ketidakcocokan yang perlu dikonfirmasi manusia.
            if ($grouped[$key]['expired_date'] !== $r['ed']) {
                $first = $grouped[$key]['expired_date'] ?? '(kosong)';
                $second = $r['ed'] ?? '(kosong)';
                $edConflicts[] = "{$r['item_name']} batch {$batch}: baris {$grouped[$key]['excel_row']} ED {$first} vs baris {$r['excel_row']} ED {$second}";

                continue;
            }

            $grouped[$key]['quantity'] += $r['qty'];

            // Nilai asli hanya bermakna bila semua baris yang dijumlah
            // berasal dari satuan berkas yang sama.
            $sameOrigin = $grouped[$key]['satuan_asli'] !== null
                && $converted['satuan_asli'] !== null
                && \App\Models\UnitConversion::normalizeUnit($grouped[$key]['satuan_asli'])
                    === \App\Models\UnitConversion::normalizeUnit($converted['satuan_asli']);

            if ($sameOrigin) {
                $grouped[$key]['quantity_asli'] += $converted['quantity_asli'];
            } else {
                $grouped[$key]['quantity_asli'] = null;
                $grouped[$key]['satuan_asli'] = null;
            }
        }

        $errors = [];

        if ($missingBatch !== []) {
            $errors[] = 'Kolom "Batch No" wajib diisi pada setiap baris data ('
                .count($missingBatch).' baris kosong): '
                .implode('; ', array_slice($missingBatch, 0, 5))
                .(count($missingBatch) > 5 ? ' (dan '.(count($missingBatch) - 5).' baris lainnya)' : '').'.';
        }

        if ($edConflicts !== []) {
            $errors[] = 'Nomor batch yang sama memiliki tanggal ED berbeda ('
                .count($edConflicts).' kasus): '
                .implode('; ', array_slice($edConflicts, 0, 5))
                .(count($edConflicts) > 5 ? ' (dan '.(count($edConflicts) - 5).' kasus lainnya)' : '')
                .'. Satu batch hanya boleh punya satu ED — mohon perbaiki berkasnya.';
        }

        // Baris yang hanya divalidasi dibuang dari hasil.
        $savable = array_filter($grouped, fn ($g) => $g['save']);

        return ['ok' => $errors === [], 'rows' => $savable, 'errors' => $errors];
    }

    public function mergeBatchNumbers(?string $batch1, ?string $batch2): ?string
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

    public function mergeExpiredDates(?string $ed1, ?string $ed2): ?string
    {
        $d1 = ! empty($ed1) ? trim((string) $ed1) : null;
        $d2 = ! empty($ed2) ? trim((string) $ed2) : null;

        $iso1 = $d1 ? $this->parseExcelDate($d1) : null;
        $iso2 = $d2 ? $this->parseExcelDate($d2) : null;

        if ($iso1 && $iso2) {
            return ($iso1 <= $iso2) ? $iso1 : $iso2;
        }

        return $iso1 ?: ($iso2 ?: ($d1 ?: $d2));
    }

    /**
     * Process an Excel file from disk, validate structure & template, and import into database.
     *
     * @return array{
     *     success: bool,
     *     status: string,
     *     distributor: ?Distributor,
     *     distributor_code: ?string,
     *     distributor_id: ?int,
     *     tanggal: ?string,
     *     total_rows: int,
     *     imported_rows: int,
     *     skipped_rows: int,
     *     skipped_items: array,
     *     error: ?string,
     *     details: array
     * }
     */
    public function parseAndImportSpreadsheet(
        string $filePath,
        ?string $fromEmail = null,
        ?int $uploadedBy = null,
        bool $dryRun = false,
        bool $isEmailAutomation = false,
        ?string $originalName = null
    ): array {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'success' => false,
                'status' => 'failed',
                'distributor' => null,
                'distributor_code' => null,
                'distributor_id' => null,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => 'Berkas lampiran tidak ditemukan atau tidak dapat dibaca di server.',
                'details' => [],
            ];
        }

        // Pemilihan cara baca dan pemecahan per cabang dikerjakan di kelas yang
        // sama dengan jalur upload manual — satu berkas tidak boleh terbaca
        // berbeda hanya karena datang lewat email.
        try {
            $reading = (new StockFileReader($this))->read($filePath, $originalName);
        } catch (\Throwable $e) {
            return $this->importResult('invalid_template', false, null, null, [
                'error' => 'Berkas bukan berkas spreadsheet Excel (.xlsx / .xls) yang valid: '.$e->getMessage(),
            ]);
        }

        if (! $reading['ok']) {
            return $this->importResult('invalid_template', false, null, null, [
                'error' => $reading['error'] ?? 'Judul kolom pada berkas Excel tidak dikenali.',
                'details' => [
                    'missing_column' => implode(', ', $reading['resolved']['missing'] ?? []),
                    'available_headers' => array_values(array_filter($reading['resolved']['headers'] ?? [], fn ($h) => $h !== '')),
                ],
            ]);
        }

        if ($reading['placeholder']) {
            return $this->importResult('invalid_template', false, null, null, [
                'error' => "Kode distributor masih berupa contoh template ('XXXX').",
            ]);
        }

        $buckets = $reading['buckets'];

        if ($buckets === []) {
            return $this->importResult('invalid_template', false, null, null, [
                'error' => 'Tidak ditemukan baris data produk pada file Excel.',
            ]);
        }

        // Seluruh cabang diperiksa sebelum satu baris pun disimpan.
        $codes = array_keys($buckets);
        $distributors = Distributor::whereIn('distributor_code', $codes)->get()->keyBy('distributor_code');

        $unknown = array_values(array_diff($codes, $distributors->keys()->all()));
        if ($unknown !== []) {
            return $this->importResult('unknown_distributor', false, null, null, [
                'error' => 'Kode distributor berikut belum terdaftar di Master Distributor: '.implode(', ', $unknown).'.',
                'details' => ['distributor_code' => $unknown[0], 'unknown_codes' => $unknown],
                'distributor_code' => $unknown[0],
            ]);
        }

        foreach ($distributors as $code => $distributor) {
            if (! $distributor->is_active) {
                return $this->importResult('inactive_distributor', false, $distributor, null, [
                    'error' => "Distributor '{$distributor->name}' ({$code}) berstatus NON-AKTIF di Master Data. Seluruh pengunggahan data stok ditolak.",
                    'details' => ['distributor_code' => $code, 'is_active' => false],
                ]);
            }

            // Whitelist ditegakkan per cabang: satu cabang yang pengirimnya
            // tidak berwenang membatalkan seluruh berkas, karena berkasnya
            // datang sebagai satu kiriman dari satu pengirim.
            if ($fromEmail !== null) {
                $rejection = $this->senderRejection($distributor, (string) $code, $fromEmail);
                if ($rejection !== null) {
                    return $rejection;
                }
            }
        }

        // Satu transaksi untuk seisi berkas: kalau satu cabang gagal di
        // tengah jalan, cabang yang sudah tersimpan ikut dibatalkan. Berkas
        // yang separuh masuk jauh lebih merepotkan daripada berkas yang
        // ditolak utuh dengan sebab yang jelas.
        $branchResults = [];
        $failure = null;

        try {
            DB::transaction(function () use ($buckets, $distributors, $fromEmail, $uploadedBy, $dryRun, $isEmailAutomation, &$branchResults, &$failure) {
                foreach ($buckets as $code => $branchRows) {
                    $result = $this->importBranchRows(
                        $distributors[$code],
                        $branchRows,
                        $fromEmail,
                        $uploadedBy,
                        $dryRun,
                        $isEmailAutomation
                    );

                    if (! $result['success']) {
                        $failure = $result;
                        throw new \RuntimeException('branch_import_failed');
                    }

                    $branchResults[] = $result;
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'branch_import_failed') {
                throw $e;
            }

            return $failure;
        }

        return $this->mergeBranchResults($branchResults);
    }

    /**
     * Susun satu nilai kembalian parseAndImportSpreadsheet().
     *
     * Bentuk kembaliannya panjang dan dipakai di belasan tempat; menuliskannya
     * berulang-ulang membuat satu kunci mudah tertinggal tanpa ketahuan.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function importResult(
        string $status,
        bool $success,
        ?Distributor $distributor,
        ?string $tanggal,
        array $overrides = []
    ): array {
        return array_merge([
            'success' => $success,
            'status' => $status,
            'distributor' => $distributor,
            'distributor_code' => $distributor?->distributor_code,
            'distributor_id' => $distributor?->id,
            'tanggal' => $tanggal,
            'total_rows' => 0,
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'skipped_items' => [],
            'error' => null,
            'details' => [],
        ], $overrides);
    }

    /**
     * Verifikasi pengirim terhadap whitelist distributor.
     *
     * @return ?array  hasil penolakan, atau null bila pengirimnya sah
     */
    private function senderRejection(Distributor $distributor, string $distributorCode, string $fromEmail): ?array
    {
        // Whitelist yang berlaku: milik cabang bila diisi, kalau tidak milik
        // grupnya — satu berkas berisi banyak cabang selalu datang dari satu
        // alamat, jadi mewajibkan tiap cabang mengisinya hanya mengundang
        // penolakan berkas yang sah.
        $senderEmailConfig = trim((string) ($distributor->effectiveSenderEmail() ?? ''));

        if ($senderEmailConfig === '') {
            return $this->importResult('unauthorized_sender', false, $distributor, null, [
                'error' => "Distributor '{$distributor->name}' ({$distributorCode}) belum mendaftarkan email whitelist resmi — baik di grup usahanya maupun di Master Distributor. Pengiriman dari '{$fromEmail}' ditolak demi keamanan data.",
                'details' => [
                    'from_email' => $fromEmail,
                    'expected_sender' => null,
                    'reason' => 'whitelist_not_configured',
                ],
            ]);
        }

        $cleanFromEmail = mb_strtolower(trim($fromEmail));
        $allowedEmails = array_map('trim', explode(',', mb_strtolower($senderEmailConfig)));

        foreach ($allowedEmails as $allowed) {
            if ($allowed === '') {
                continue;
            }
            if ($allowed === $cleanFromEmail) {
                return null;
            }
            // Domain wildcard, mis. @kftd.co.id
            if (str_starts_with($allowed, '@') && str_ends_with($cleanFromEmail, $allowed)) {
                return null;
            }
        }

        return $this->importResult('unauthorized_sender', false, $distributor, null, [
            'error' => "Pengirim email ('{$fromEmail}') tidak terdaftar pada whitelist resmi distributor '{$distributor->name}' ({$distributorCode}).",
            'details' => [
                'from_email' => $fromEmail,
                'expected_sender' => $distributor->effectiveSenderEmail(),
                'reason' => 'sender_not_in_whitelist',
            ],
        ]);
    }

    /**
     * Gabungkan hasil tiap cabang menjadi satu nilai kembalian.
     *
     * Satu berkas tetap menghasilkan SATU baris StockEmailLog, jadi angkanya
     * dijumlahkan dan rincian per cabang disimpan di details.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function mergeBranchResults(array $results): array
    {
        if (count($results) === 1) {
            return $results[0];
        }

        $first = $results[0];
        $skippedItems = [];
        $branches = [];
        $totalRows = 0;
        $importedRows = 0;
        $anySkipped = false;
        $anyImported = false;

        foreach ($results as $r) {
            $totalRows += $r['total_rows'];
            $importedRows += $r['imported_rows'];
            $skippedItems = array_merge($skippedItems, $r['skipped_items']);
            $anySkipped = $anySkipped || $r['skipped_rows'] > 0;
            $anyImported = $anyImported || $r['imported_rows'] > 0;

            $branches[] = [
                'distributor_code' => $r['distributor_code'],
                'distributor_id' => $r['distributor_id'],
                'tanggal' => $r['tanggal'],
                'imported_rows' => $r['imported_rows'],
                'skipped_rows' => $r['skipped_rows'],
                'status' => $r['status'],
            ];
        }

        $status = match (true) {
            ! $anyImported && $anySkipped => 'all_unmapped',
            $anySkipped => 'partial_unmapped',
            default => 'success',
        };

        $codes = array_column($branches, 'distributor_code');

        return [
            'success' => $anyImported,
            'status' => $status,
            // Log hanya punya satu kolom distributor; cabang pertama yang
            // dicatat, selebihnya ada di details['branches'].
            'distributor' => $first['distributor'],
            'distributor_code' => $first['distributor_code'],
            'distributor_id' => $first['distributor_id'],
            'tanggal' => $first['tanggal'],
            'total_rows' => $totalRows,
            'imported_rows' => $importedRows,
            'skipped_rows' => count($skippedItems),
            'skipped_items' => $skippedItems,
            'error' => $status === 'success'
                ? null
                : count($skippedItems).' item belum ter-mapping pada berkas berisi '.count($branches).' cabang.',
            'details' => [
                'branch_count' => count($branches),
                'branches' => $branches,
                'distributor_codes' => $codes,
                'imported_count' => $importedRows,
                'skipped_count' => count($skippedItems),
            ],
        ];
    }

    /**
     * Impor baris milik SATU cabang.
     *
     * @param  array<int, array{row: array<int, mixed>, excel_row: int}>  $branchRows
     * @return array<string, mixed>
     */
    private function importBranchRows(
        Distributor $distributor,
        array $branchRows,
        ?string $fromEmail,
        ?int $uploadedBy,
        bool $dryRun,
        bool $isEmailAutomation
    ): array {
        $distributorCode = $distributor->distributor_code;
        $firstRow = $branchRows[0]['row'];
        $reader = $branchRows[0]['reader'];

        $rawTanggal = $reader->rawTanggal($firstRow);
        if (in_array(trim(strtoupper((string) $rawTanggal)), ['DD/MM/YYYY', 'YYYY-MM-DD', 'DD-MM-YYYY'], true)) {
            return $this->importResult('invalid_template', false, $distributor, null, [
                'error' => "Tanggal snapshot masih berupa placeholder ('{$rawTanggal}').",
            ]);
        }

        $tanggal = $reader->tanggal($firstRow);
        if (! $tanggal) {
            return $this->importResult('invalid_template', false, $distributor, null, [
                'error' => "Format tanggal snapshot tidak dikenali: '{$rawTanggal}'.",
                'details' => ['raw_tanggal' => $rawTanggal],
            ]);
        }

        // Otomasi email tidak boleh menimpa data yang sudah ada: keputusan
        // Gabung/Ganti hanya boleh diambil manusia di halaman Upload.
        if ($isEmailAutomation || $fromEmail !== null) {
            $existingCount = StockEntry::query()
                ->where('distributor_id', $distributor->id)
                ->where('tanggal', $tanggal)
                ->count();

            if ($existingCount > 0) {
                return $this->importResult('data_already_exists', false, $distributor, $tanggal, [
                    'error' => "Data sudah ada untuk distributor '{$distributor->name}' ({$distributorCode}) pada tanggal {$tanggal} ({$existingCount} baris data ditemukan). Silakan upload manual jika ingin memperbarui.",
                    'details' => [
                        'existing_count' => $existingCount,
                        'distributor_code' => $distributorCode,
                        'distributor_id' => $distributor->id,
                        'tanggal' => $tanggal,
                        'reason' => 'data_already_exists',
                    ],
                ]);
            }
        }

        // Pemetaan milik GRUP berlaku untuk seluruh cabangnya; baris milik
        // cabang hanya ada sebagai pengecualian dan menimpa yang segrup.
        $knownItems = DistributorItem::lookupFor($distributor);

        $parsedRows = [];
        $skippedItems = [];
        $totalValidDataRows = 0;

        foreach ($branchRows as $entry) {
            $r = $entry['row'];
            // Tiap baris membawa pembacanya sendiri: satu berkas bisa memuat
            // beberapa sheet dengan baris header masing-masing.
            $reader = $entry['reader'];
            $itemName = $reader->itemName($r);
            $totalValidDataRows++;

            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)));
            $distItem = $knownItems->get($key);

            $qty = $reader->quantity($r);
            $satuan = $reader->satuan($r);
            $ed = $reader->expiredDate($r);
            $batch = $reader->batchNo($r);

            if (! $distItem || ! $distItem->isMapped()) {
                $skippedItems[] = [
                    'item_name' => $itemName,
                    'satuan' => $satuan ?: null,
                    'quantity' => $qty,
                    'expired_date' => $ed,
                    'batch_no' => $batch ?: null,
                ];

                // Ikut divalidasi walau tidak disimpan — lihat catatan pada
                // groupRowsByItemAndBatch().
                $parsedRows[] = [
                    'item_id' => 'x'.mb_strtolower($itemName),
                    'item_name' => $itemName,
                    'qty' => $qty,
                    'satuan' => $satuan,
                    'ed' => $ed,
                    'batch' => $batch,
                    'excel_row' => $entry['excel_row'],
                    'save' => false,
                ];

                continue;
            }

            $parsedRows[] = [
                'item_id' => $distItem->id,
                'item_name' => $itemName,
                'qty' => $qty,
                // Satuan hanya dari berkas ini sendiri — tidak meminjam satuan
                // master item yang berasal dari berkas lain.
                'satuan' => $satuan ?: null,
                'ed' => $ed,
                'batch' => $batch,
                'excel_row' => $entry['excel_row'],
            ];
        }

        // Pengelompokan per (item, batch) + dua aturan penolakan.
        $grouping = $this->groupRowsByItemAndBatch($parsedRows);

        if (! $grouping['ok']) {
            return $this->importResult('invalid_batch_data', false, $distributor, $tanggal, [
                'total_rows' => $totalValidDataRows,
                'error' => $distributor->name.': '.implode(' ', $grouping['errors']),
                'details' => ['batch_errors' => $grouping['errors']],
            ]);
        }

        $validRowsToSave = $grouping['rows'];

        if ($totalValidDataRows === 0) {
            return $this->importResult('invalid_template', false, $distributor, $tanggal, [
                'error' => 'Tidak ditemukan baris data produk pada file Excel.',
            ]);
        }

        $importedCount = 0;

        if (! $dryRun && count($validRowsToSave) > 0) {
            DB::transaction(function () use ($validRowsToSave, $distributor, $tanggal, $uploadedBy, $skippedItems, &$importedCount) {
                foreach ($validRowsToSave as $row) {
                    StockEntry::updateOrCreate(
                        [
                            'tanggal' => $tanggal,
                            // Cabang ikut jadi kunci: sejak pemetaan item
                            // dimiliki GRUP, satu baris pemetaan dipakai banyak
                            // cabang — tanpa ini, snapshot cabang kedua akan
                            // menimpa snapshot cabang pertama.
                            'distributor_id' => $distributor->id,
                            'distributor_item_id' => $row['distributor_item_id'],
                            // Batch kini bagian dari identitas snapshot: dua
                            // batch pada item & tanggal yang sama adalah dua
                            // baris berbeda, bukan saling menimpa.
                            'batch_no' => $row['batch_no'],
                        ],
                        [
                            'distributor_id' => $distributor->id,
                            'quantity' => $row['quantity'],
                            'satuan' => $row['satuan'],
                            'quantity_asli' => $row['quantity_asli'],
                            'satuan_asli' => $row['satuan_asli'],
                            'expired_date' => $row['expired_date'],
                            'batch_no' => $row['batch_no'],
                            'uploaded_by' => $uploadedBy,
                        ]
                    );
                    $importedCount++;
                }

                StockSnapshotActivity::create([
                    'tanggal' => $tanggal,
                    'distributor_id' => $distributor->id,
                    'user_id' => $uploadedBy,
                    'action' => $uploadedBy ? 'upload' : 'automation',
                    'description' => $uploadedBy
                        ? 'Upload dari email oleh '.(\App\Models\User::find($uploadedBy)?->name ?? 'User')." ({$importedCount} SKU)"
                        : "Import otomatis via Email ({$importedCount} SKU)",
                    'metadata' => [
                        'sku_count' => $importedCount,
                        'source' => 'email',
                        'skipped_count' => count($skippedItems),
                    ],
                ]);
            });
        } elseif ($dryRun) {
            $importedCount = count($validRowsToSave);
        }

        $uniqueSkippedNames = array_values(array_unique(array_column($skippedItems, 'item_name')));

        if (count($validRowsToSave) === 0 && count($skippedItems) > 0) {
            $status = 'all_unmapped';
            $isSuccess = false;
        } elseif (count($skippedItems) > 0) {
            $status = 'partial_unmapped';
            $isSuccess = true;
        } else {
            $status = 'success';
            $isSuccess = true;
        }

        $unmappedError = null;
        if (count($skippedItems) > 0) {
            $countSkipped = count($uniqueSkippedNames);
            $namesPreview = implode(', ', array_slice($uniqueSkippedNames, 0, 5));
            if ($countSkipped > 5) {
                $namesPreview .= ' (dan '.($countSkipped - 5).' item lainnya)';
            }
            $unmappedError = "{$countSkipped} item belum ter-mapping ke NetSuite: {$namesPreview}.";

            // Item baru didaftarkan ke antrean mapping supaya operator tinggal
            // memetakannya, bukan mengetik ulang namanya.
            if (! $dryRun) {
                foreach ($skippedItems as $skip) {
                    // Satu pintu untuk semua jalur yang menemukan item baru —
                    // termasuk aturan bahwa baris yang pernah dihapus dipulihkan
                    // TANPA pemetaan lamanya.
                    DistributorItem::queueFor($distributor, $skip['item_name'], $skip['satuan'] ?: null);
                }
            }
        }

        return $this->importResult($status, $isSuccess, $distributor, $tanggal, [
            'total_rows' => $totalValidDataRows,
            'imported_rows' => $importedCount,
            'skipped_rows' => count($skippedItems),
            'skipped_items' => $skippedItems,
            'error' => $status === 'all_unmapped'
                ? 'Seluruh item ('.count($uniqueSkippedNames).' item) belum ter-mapping ke NetSuite. 0 baris disimpan ke database. Harap petakan item terlebih dahulu di Master Mapping.'
                : $unmappedError,
            'details' => [
                'imported_count' => $importedCount,
                'skipped_count' => count($skippedItems),
                'unique_skipped_names' => $uniqueSkippedNames,
            ],
        ]);
    }

    /**
     * Process raw binary content from an email attachment, write to temp file,
     * execute import, and record audit log in StockEmailLog.
     */
    public function processEmailAttachment(
        string $binaryContent,
        string $filename,
        string $fromEmail,
        ?string $fromName = null,
        string $subject = '',
        ?string $emailUid = null,
        ?string $messageId = null,
        bool $dryRun = false
    ): array {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $cleanExt = in_array($ext, ['xlsx', 'xls'], true) ? $ext : 'xlsx';
        // tempnam() SUDAH membuat berkas dan mengembalikan path-nya. Menambahkan
        // ekstensi menghasilkan path BERBEDA, sehingga berkas asli tertinggal
        // dan tidak pernah terhapus. Dengan cron tiap menit, temp dir terus
        // membengkak. Karena itu kedua path disimpan dan dihapus bersama.
        $tempBase = tempnam(sys_get_temp_dir(), 'satoria_auto_stock_');
        $tempFile = $tempBase.'.'.$cleanExt;

        file_put_contents($tempFile, $binaryContent);

        try {
            $result = $this->parseAndImportSpreadsheet($tempFile, $fromEmail, null, $dryRun, true, $filename);

            // Record to StockEmailLog
            StockEmailLog::create([
                'email_uid' => (string) ($emailUid ?? 'unknown'),
                'message_id' => $messageId,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'subject' => $subject,
                'distributor_id' => $result['distributor_id'] ?? null,
                'distributor_code' => $result['distributor_code'] ?? null,
                'tanggal_snapshot' => $result['tanggal'] ?? null,
                'filename' => $filename,
                'file_size_bytes' => strlen($binaryContent),
                'status' => $result['status'],
                'total_rows' => $result['total_rows'] ?? 0,
                'imported_rows' => $result['imported_rows'] ?? 0,
                'skipped_rows' => $result['skipped_rows'] ?? 0,
                'error_message' => $result['error'] ?? null,
                'details' => $result['details'] ?? [],
            ]);

            return $result;
        } finally {
            @unlink($tempFile);
            @unlink($tempBase);
        }
    }
}
