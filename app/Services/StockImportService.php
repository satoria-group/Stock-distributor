<?php

namespace App\Services;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEmailLog;
use App\Models\StockEntry;
use App\Models\StockSnapshotActivity;
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
        bool $isEmailAutomation = false
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

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => null,
                'distributor_code' => null,
                'distributor_id' => null,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => 'Berkas bukan berkas spreadsheet Excel (.xlsx / .xls) yang valid: '.$e->getMessage(),
                'details' => [],
            ];
        }

        $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (empty($data) || count($data) < 2) {
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => null,
                'distributor_code' => null,
                'distributor_id' => null,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => 'Lembar kerja Excel kosong atau tidak memiliki baris data.',
                'details' => [],
            ];
        }

        // Header mapping
        $headerRow = $data[0] ?? [];
        $col = [];
        foreach ($headerRow as $idx => $name) {
            if ($name !== null && $name !== '') {
                $col[trim((string) $name)] = $idx;
            }
        }

        $requiredCols = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($requiredCols as $rc) {
            if (! isset($col[$rc])) {
                return [
                    'success' => false,
                    'status' => 'invalid_template',
                    'distributor' => null,
                    'distributor_code' => null,
                    'distributor_id' => null,
                    'tanggal' => null,
                    'total_rows' => 0,
                    'imported_rows' => 0,
                    'skipped_rows' => 0,
                    'skipped_items' => [],
                    'error' => "Kolom wajib '{$rc}' tidak ditemukan pada template Excel.",
                    'details' => ['missing_column' => $rc, 'available_headers' => array_keys($col)],
                ];
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
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => null,
                'distributor_code' => null,
                'distributor_id' => null,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => 'Berkas Excel tidak berisi baris data distributor yang valid.',
                'details' => [],
            ];
        }

        $distributorCode = trim((string) $firstDataRow[$col['ID DISTRIBUTOR']]);
        if (strtoupper($distributorCode) === 'XXXX') {
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => null,
                'distributor_code' => null,
                'distributor_id' => null,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => "Kode distributor masih berupa contoh template ('XXXX').",
                'details' => [],
            ];
        }

        $distributor = Distributor::where('distributor_code', $distributorCode)->first();
        if (! $distributor) {
            return [
                'success' => false,
                'status' => 'unknown_distributor',
                'distributor' => null,
                'distributor_code' => $distributorCode,
                'distributor_id' => null,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => "Distributor dengan kode '{$distributorCode}' belum terdaftar di Master Distributor.",
                'details' => ['distributor_code' => $distributorCode],
            ];
        }

        if (! $distributor->is_active) {
            return [
                'success' => false,
                'status' => 'inactive_distributor',
                'distributor' => $distributor,
                'distributor_code' => $distributorCode,
                'distributor_id' => $distributor->id,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => "Distributor '{$distributor->name}' ({$distributorCode}) berstatus NON-AKTIF di Master Data. Seluruh pengunggahan data stok ditolak.",
                'details' => ['distributor_code' => $distributorCode, 'is_active' => false],
            ];
        }

        // Sender email whitelist verification (mandatory for email automation)
        if ($fromEmail) {
            $senderEmailConfig = trim((string) ($distributor->sender_email ?? ''));

            if ($senderEmailConfig === '') {
                return [
                    'success' => false,
                    'status' => 'unauthorized_sender',
                    'distributor' => $distributor,
                    'distributor_code' => $distributorCode,
                    'distributor_id' => $distributor->id,
                    'tanggal' => null,
                    'total_rows' => 0,
                    'imported_rows' => 0,
                    'skipped_rows' => 0,
                    'skipped_items' => [],
                    'error' => "Distributor '{$distributor->name}' ({$distributorCode}) belum mendaftarkan email whitelist resmi di Master Distributor. Pengiriman dari '{$fromEmail}' ditolak demi keamanan data.",
                    'details' => [
                        'from_email' => $fromEmail,
                        'expected_sender' => null,
                        'reason' => 'missing_whitelist_configuration',
                    ],
                ];
            }

            $allowedEmails = array_map('trim', explode(',', strtolower($senderEmailConfig)));
            $cleanFromEmail = strtolower(trim($fromEmail));
            $isMatch = false;

            foreach ($allowedEmails as $allowed) {
                if ($allowed === '') {
                    continue;
                }
                if ($allowed === $cleanFromEmail) {
                    $isMatch = true;
                    break;
                }
                // Check if domain match (e.g., @kftd.co.id)
                if (str_starts_with($allowed, '@') && str_ends_with($cleanFromEmail, $allowed)) {
                    $isMatch = true;
                    break;
                }
            }

            if (! $isMatch) {
                return [
                    'success' => false,
                    'status' => 'unauthorized_sender',
                    'distributor' => $distributor,
                    'distributor_code' => $distributorCode,
                    'distributor_id' => $distributor->id,
                    'tanggal' => null,
                    'total_rows' => 0,
                    'imported_rows' => 0,
                    'skipped_rows' => 0,
                    'skipped_items' => [],
                    'error' => "Pengirim email ('{$fromEmail}') tidak terdaftar pada whitelist resmi distributor '{$distributor->name}' ({$distributorCode}).",
                    'details' => [
                        'from_email' => $fromEmail,
                        'expected_sender' => $distributor->sender_email,
                        'reason' => 'sender_not_in_whitelist',
                    ],
                ];
            }
        }

        // Tanggal snapshot validation
        $rawTanggal = $firstDataRow[$col['Tanggal']];
        if (in_array(trim(strtoupper((string) $rawTanggal)), ['DD/MM/YYYY', 'YYYY-MM-DD', 'DD-MM-YYYY'], true)) {
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => $distributor,
                'distributor_code' => $distributorCode,
                'distributor_id' => $distributor->id,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => "Tanggal snapshot masih berupa placeholder ('{$rawTanggal}').",
                'details' => [],
            ];
        }

        $tanggal = $this->parseExcelDate($rawTanggal);
        if (! $tanggal) {
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => $distributor,
                'distributor_code' => $distributorCode,
                'distributor_id' => $distributor->id,
                'tanggal' => null,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => "Format tanggal snapshot tidak dikenali: '{$rawTanggal}'.",
                'details' => ['raw_tanggal' => $rawTanggal],
            ];
        }

        // Cek data duplikat untuk Otomasi Email:
        // Jika sudah ada data di database untuk distributor dan tanggal yang sama,
        // tolak otomatis dengan status 'data_already_exists' dan minta user melakukan upload manual.
        if ($isEmailAutomation || $fromEmail !== null) {
            $existingCount = StockEntry::query()
                ->where('distributor_id', $distributor->id)
                ->where('tanggal', $tanggal)
                ->count();

            if ($existingCount > 0) {
                return [
                    'success' => false,
                    'status' => 'data_already_exists',
                    'distributor' => $distributor,
                    'distributor_code' => $distributorCode,
                    'distributor_id' => $distributor->id,
                    'tanggal' => $tanggal,
                    'total_rows' => 0,
                    'imported_rows' => 0,
                    'skipped_rows' => 0,
                    'skipped_items' => [],
                    'error' => "Data sudah ada untuk distributor '{$distributor->name}' ({$distributorCode}) pada tanggal {$tanggal} ({$existingCount} baris data ditemukan). Silakan upload manual jika ingin memperbarui.",
                    'details' => [
                        'existing_count' => $existingCount,
                        'distributor_code' => $distributorCode,
                        'distributor_id' => $distributor->id,
                        'tanggal' => $tanggal,
                        'reason' => 'data_already_exists',
                    ],
                ];
            }
        }

        $knownItems = DistributorItem::where('distributor_id', $distributor->id)
            ->get()
            ->keyBy(fn ($i) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $i->item_name))));

        $parsedRows = [];
        $skippedItems = [];
        $totalValidDataRows = 0;
        $excelRow = 1; // baris 1 = header

        foreach ($bodyRows as $r) {
            $excelRow++;
            $itemName = trim((string) ($r[$col['Distributor Item Name']] ?? ''));
            if ($itemName === '' || strtoupper($itemName) === 'XXXXX XXXX') {
                continue;
            }

            $totalValidDataRows++;

            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)));
            $distItem = $knownItems->get($key);

            $qty = $this->parseQuantity($r[$col['Quantity']] ?? 0);
            $satuan = isset($col['Satuan']) ? trim((string) ($r[$col['Satuan']] ?? '')) : null;
            $ed = isset($col['ED']) ? $this->parseExcelDate($r[$col['ED']] ?? null) : null;
            $batch = isset($col['Batch No']) ? trim((string) ($r[$col['Batch No']] ?? '')) : null;

            if (! $distItem || ! $distItem->isMapped()) {
                $skippedItems[] = [
                    'item_name' => $itemName,
                    'satuan' => $satuan ?: ($distItem?->satuan ?: 'PCS'),
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
                    'excel_row' => $excelRow,
                    'save' => false,
                ];

                continue;
            }

            $parsedRows[] = [
                'item_id' => $distItem->id,
                'item_name' => $itemName,
                'qty' => $qty,
                'satuan' => $satuan ?: $distItem->satuan,
                'ed' => $ed,
                'batch' => $batch,
                'excel_row' => $excelRow,
            ];
        }

        // Pengelompokan per (item, batch) + dua aturan penolakan.
        $grouping = $this->groupRowsByItemAndBatch($parsedRows);

        if (! $grouping['ok']) {
            return [
                'success' => false,
                'status' => 'invalid_batch_data',
                'distributor' => $distributor,
                'distributor_code' => $distributorCode,
                'distributor_id' => $distributor->id,
                'tanggal' => $tanggal,
                'total_rows' => $totalValidDataRows,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => implode(' ', $grouping['errors']),
                'details' => ['batch_errors' => $grouping['errors']],
            ];
        }

        $validRowsToSave = $grouping['rows'];

        if ($totalValidDataRows === 0) {
            return [
                'success' => false,
                'status' => 'invalid_template',
                'distributor' => $distributor,
                'distributor_code' => $distributorCode,
                'distributor_id' => $distributor->id,
                'tanggal' => $tanggal,
                'total_rows' => 0,
                'imported_rows' => 0,
                'skipped_rows' => 0,
                'skipped_items' => [],
                'error' => 'Tidak ditemukan baris data produk pada file Excel.',
                'details' => [],
            ];
        }

        $importedCount = 0;

        if (! $dryRun && count($validRowsToSave) > 0) {
            DB::transaction(function () use ($validRowsToSave, $distributor, $tanggal, $uploadedBy, $skippedItems, &$importedCount) {
                foreach ($validRowsToSave as $row) {
                    StockEntry::updateOrCreate(
                        [
                            'tanggal' => $tanggal,
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
                        ? "Upload dari email oleh " . (\App\Models\User::find($uploadedBy)?->name ?? 'User') . " ({$importedCount} SKU)"
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

            // Otomatis daftarkan item baru yang belum terpetakan ke tabel DistributorItem (antrean mapping)
            if (! $dryRun) {
                foreach ($skippedItems as $skip) {
                    $rawName = trim($skip['item_name']);
                    $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $rawName)));
                    if (! $knownItems->has($norm)) {
                        $existing = DistributorItem::withTrashed()
                            ->where('distributor_id', $distributor->id)
                            ->whereRaw('LOWER(TRIM(item_name)) = ?', [$norm])
                            ->first();

                        if ($existing) {
                            if ($existing->trashed()) {
                                $existing->restore();
                            }
                        } else {
                            try {
                                DistributorItem::create([
                                    'distributor_id' => $distributor->id,
                                    'item_name' => $rawName,
                                    'satuan' => $skip['satuan'] ?: 'PCS',
                                    'netsuite_item_id' => null,
                                ]);
                            } catch (\Throwable) {
                                // Abaikan jika terjadi race condition insert
                            }
                        }
                    }
                }
            }
        }

        return [
            'success' => $isSuccess,
            'status' => $status,
            'distributor' => $distributor,
            'distributor_code' => $distributorCode,
            'distributor_id' => $distributor->id,
            'tanggal' => $tanggal,
            'total_rows' => $totalValidDataRows,
            'imported_rows' => $importedCount,
            'skipped_rows' => count($skippedItems),
            'skipped_items' => $skippedItems,
            'error' => $status === 'all_unmapped'
                ? "Seluruh item (".count($uniqueSkippedNames)." item) belum ter-mapping ke NetSuite. 0 baris disimpan ke database. Harap petakan item terlebih dahulu di Master Mapping."
                : $unmappedError,
            'details' => [
                'imported_count' => $importedCount,
                'skipped_count' => count($skippedItems),
                'unique_skipped_names' => $uniqueSkippedNames,
            ],
        ];
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
            $result = $this->parseAndImportSpreadsheet($tempFile, $fromEmail, null, $dryRun, true);

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
