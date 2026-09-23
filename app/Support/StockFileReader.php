<?php

namespace App\Support;

use App\Models\Distributor;
use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Memilih cara membaca sebuah berkas stok, lalu memecah isinya per cabang.
 *
 * Ada satu masalah ayam-dan-telur di sini: grup template pengirim baru bisa
 * diketahui setelah kode distributornya terbaca, padahal kode itu sendiri ada
 * di dalam kolom yang perlu dipetakan oleh grup tersebut.
 *
 * Jalan keluarnya: beberapa cara baca dicoba, lalu dipilih berdasarkan bukti.
 *
 *   1. Deteksi otomatis (sinonim bawaan), lalu resep tiap Grup Template.
 *   2. Yang dipilih adalah cara baca pertama yang menghasilkan kode distributor
 *      yang SEMUANYA ada di Master Distributor — bukan sekadar yang kolomnya
 *      kebetulan lengkap. Beberapa grup bisa saja sama-sama sanggup membaca
 *      satu bentuk berkas; yang benar adalah yang kodenya nyata.
 *   3. Bila tidak ada yang lolos ujian itu, cara baca pertama yang kolomnya
 *      lengkap tetap dikembalikan — supaya pesan kesalahannya bisa menyebut
 *      kode mana yang tidak dikenal, bukan sekadar "gagal".
 *
 * Kelas ini dipakai jalur upload manual dan otomasi email sekaligus, supaya
 * satu berkas tidak pernah terbaca berbeda di dua jalur.
 */
class StockFileReader
{
    public function __construct(private readonly StockImportService $importService) {}

    /**
     * @param  bool  $formatData  diteruskan ke Worksheet::toArray(); jalur email
     *                            membacanya terformat, jalur manual tidak.
     * @return array{
     *     ok: bool,
     *     group: ?DistributorTemplateGroup,
     *     resolved: array,
     *     reader: ?StockRowReader,
     *     buckets: array<string, array<int, array{row: array<int, mixed>, excel_row: int}>>,
     *     placeholder: bool,
     *     error: ?string
     * }
     */
    public function read(Spreadsheet $spreadsheet, bool $formatData = false): array
    {
        $resolver = new StockHeaderResolver;

        $defaultSheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
        $defaultData = $defaultSheet->toArray(null, true, $formatData, false);

        $fallback = null;

        foreach ($this->candidates() as $group) {
            $data = $defaultData;

            if ($group?->sheet_name) {
                $sheet = $spreadsheet->getSheetByName($group->sheet_name);
                // Grup menyebut sheet yang tidak ada di berkas ini: berarti
                // berkasnya bukan milik grup tersebut.
                if (! $sheet) {
                    continue;
                }
                $data = $sheet->toArray(null, true, $formatData, false);
            }

            $resolved = $resolver->resolve($data, $group);
            if (! $resolved['ok']) {
                continue;
            }

            $reader = new StockRowReader($resolved, $group, $this->importService);
            $buckets = $this->bucket($data, $resolved, $reader);

            $attempt = [
                'ok' => true,
                'group' => $group,
                'resolved' => $resolved,
                'reader' => $reader,
                'buckets' => $buckets,
                'placeholder' => isset($buckets['XXXX']),
                'error' => null,
            ];

            if ($attempt['placeholder']) {
                return $attempt;
            }

            if ($buckets !== [] && $this->allCodesKnown(array_keys($buckets))) {
                return $this->preferOwnGroup($attempt, $spreadsheet, $defaultData, $resolver, $formatData);
            }

            $fallback ??= $attempt;
        }

        if ($fallback !== null) {
            return $fallback;
        }

        // Tidak satu pun cara baca yang kolom wajibnya lengkap: kembalikan
        // hasil deteksi otomatis apa adanya, karena pesan kesalahannyalah yang
        // paling berguna bagi operator.
        $resolved = $resolver->resolve($defaultData);

        return [
            'ok' => false,
            'group' => null,
            'resolved' => $resolved,
            'reader' => null,
            'buckets' => [],
            'placeholder' => false,
            'error' => $resolved['error'] ?? 'Judul kolom pada berkas Excel tidak dikenali.',
        ];
    }

    /**
     * Urutan percobaan: deteksi otomatis dulu (paling murah dan paling sering
     * benar), baru resep tiap grup yang berstatus aktif.
     *
     * @return array<int, ?DistributorTemplateGroup>
     */
    private function candidates(): array
    {
        return array_merge([null], DistributorTemplateGroup::active()->get()->all());
    }

    /**
     * Setelah cabangnya diketahui, grup MILIK cabang itu lebih berhak daripada
     * grup lain yang kebetulan juga sanggup membaca berkasnya — mis. berkas
     * yang punya kolom 'Qty' dan 'Qty Retur' sekaligus, yang hanya resep
     * grupnya sendiri tahu mana yang benar.
     *
     * @param  array<string, mixed>  $attempt
     * @param  array<int, array<int, mixed>>  $defaultData
     * @return array<string, mixed>
     */
    private function preferOwnGroup(
        array $attempt,
        Spreadsheet $spreadsheet,
        array $defaultData,
        StockHeaderResolver $resolver,
        bool $formatData
    ): array {
        $firstCode = array_key_first($attempt['buckets']);
        $ownGroup = Distributor::where('distributor_code', $firstCode)->first()?->templateGroup;

        if (! $ownGroup || $ownGroup->id === $attempt['group']?->id) {
            return $attempt;
        }

        $data = $defaultData;
        if ($ownGroup->sheet_name) {
            $sheet = $spreadsheet->getSheetByName($ownGroup->sheet_name);
            if (! $sheet) {
                return $attempt;
            }
            $data = $sheet->toArray(null, true, $formatData, false);
        }

        $resolved = $resolver->resolve($data, $ownGroup);
        if (! $resolved['ok']) {
            return $attempt;
        }

        $reader = new StockRowReader($resolved, $ownGroup, $this->importService);
        $buckets = $this->bucket($data, $resolved, $reader);

        // Resep grupnya sendiri hanya dipakai kalau hasilnya tetap masuk akal;
        // kalau justru menghasilkan kode yang tidak dikenal, cara baca yang
        // sudah terbukti tadi tetap dipertahankan.
        if ($buckets === [] || ! $this->allCodesKnown(array_keys($buckets))) {
            return $attempt;
        }

        return [
            'ok' => true,
            'group' => $ownGroup,
            'resolved' => $resolved,
            'reader' => $reader,
            'buckets' => $buckets,
            'placeholder' => isset($buckets['XXXX']),
            'error' => null,
        ];
    }

    /** @param array<int, string> $codes */
    private function allCodesKnown(array $codes): bool
    {
        return Distributor::whereIn('distributor_code', $codes)->count() === count($codes);
    }

    /**
     * Kelompokkan baris per kode distributor.
     *
     * Kode dibaca PER BARIS, bukan sekali dari baris pertama: satu berkas
     * UDC/KFTD bisa memuat beberapa cabang sekaligus.
     *
     * @param  array<int, array<int, mixed>>  $data
     * @return array<string, array<int, array{row: array<int, mixed>, excel_row: int}>>
     */
    private function bucket(array $data, array $resolved, StockRowReader $reader): array
    {
        $buckets = [];
        $excelRow = $resolved['header_row'] + 1;

        foreach (array_slice($data, $resolved['header_row'] + 1) as $row) {
            $excelRow++;

            if (! is_array($row)) {
                continue;
            }

            $code = $reader->distributorCode($row);
            if ($code === '') {
                continue;
            }

            if (strtoupper($code) === 'XXXX') {
                $buckets['XXXX'] = [];

                continue;
            }

            $itemName = $reader->itemName($row);
            if ($itemName === '' || strtoupper($itemName) === 'XXXXX XXXX') {
                continue;
            }

            // Baris berstok nol pada grup yang memang mengirimkannya (KFTD)
            // dibuang di sini, sebelum sempat dianggap data.
            if ($reader->shouldSkip($row)) {
                continue;
            }

            $buckets[$code][] = ['row' => $row, 'excel_row' => $excelRow];
        }

        return $buckets;
    }
}
