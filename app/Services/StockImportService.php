<?php

namespace App\Services;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEmailLog;
use App\Models\StockEntry;
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

        $validRowsToSave = [];
        $skippedItems = [];
        $totalValidDataRows = 0;

        foreach ($bodyRows as $r) {
            $itemName = trim((string) ($r[$col['Distributor Item Name']] ?? ''));
            if ($itemName === '' || strtoupper($itemName) === 'XXXXX XXXX') {
                continue;
            }

            $totalValidDataRows++;

            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $itemName)));
            $distItem = $knownItems->get($key);

            $rawQty = str_replace([',', ' '], '', trim((string) ($r[$col['Quantity']] ?? 0)));
            $qty = (float) $rawQty;
            $satuan = isset($col['Satuan']) ? trim((string) ($r[$col['Satuan']] ?? '')) : null;
            $ed = isset($col['ED']) ? $this->parseExcelDate($r[$col['ED']] ?? null) : null;
            $batch = isset($col['Batch No']) ? trim((string) ($r[$col['Batch No']] ?? '')) : null;

            if (! $distItem) {
                $skippedItems[] = [
                    'item_name' => $itemName,
                    'satuan' => $satuan ?: 'PCS',
                    'quantity' => $qty,
                    'expired_date' => $ed,
                    'batch_no' => $batch ?: null,
                ];
                continue;
            }

            $validRowsToSave[] = [
                'distributor_item_id' => $distItem->id,
                'quantity' => $qty,
                'satuan' => $satuan ?: $distItem->satuan,
                'expired_date' => $ed,
                'batch_no' => $batch,
            ];
        }

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
            DB::transaction(function () use ($validRowsToSave, $distributor, $tanggal, $uploadedBy, &$importedCount) {
                foreach ($validRowsToSave as $row) {
                    StockEntry::updateOrCreate(
                        [
                            'tanggal' => $tanggal,
                            'distributor_item_id' => $row['distributor_item_id'],
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
            });
        } elseif ($dryRun) {
            $importedCount = count($validRowsToSave);
        }

        $status = count($skippedItems) > 0 ? 'partial_unmapped' : 'success';
        $uniqueSkippedNames = array_values(array_unique(array_column($skippedItems, 'item_name')));

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
            'success' => true,
            'status' => $status,
            'distributor' => $distributor,
            'distributor_code' => $distributorCode,
            'distributor_id' => $distributor->id,
            'tanggal' => $tanggal,
            'total_rows' => $totalValidDataRows,
            'imported_rows' => $importedCount,
            'skipped_rows' => count($skippedItems),
            'skipped_items' => $skippedItems,
            'error' => $unmappedError,
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
        $tempFile = tempnam(sys_get_temp_dir(), 'satoria_auto_stock_').'.'.$cleanExt;

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
        }
    }
}
