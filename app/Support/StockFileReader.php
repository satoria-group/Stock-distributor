<?php

namespace App\Support;

use App\Models\Distributor;
use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Memilih cara membaca sebuah berkas stok, lalu memecah isinya per cabang.
 *
 * Ada satu masalah ayam-dan-telur di sini: grup template pengirim baru bisa
 * diketahui setelah kode distributornya terbaca, padahal kode itu sendiri ada
 * di dalam kolom yang perlu dipetakan oleh grup tersebut.
 *
 * Jalan keluarnya: beberapa cara baca dicoba, lalu dipilih berdasarkan bukti.
 *
 *   1. Deteksi otomatis (sinonim bawaan), lalu resep tiap Grup Template aktif.
 *   2. Yang dipilih adalah cara baca pertama yang menghasilkan kode distributor
 *      yang SEMUANYA ada di Master Distributor — bukan sekadar yang kolomnya
 *      kebetulan lengkap. Beberapa grup bisa saja sama-sama sanggup membaca
 *      satu bentuk berkas; yang benar adalah yang kodenya nyata.
 *   3. Bila tidak ada yang lolos ujian itu, cara baca pertama yang kolomnya
 *      lengkap tetap dikembalikan — supaya pesan kesalahannya bisa menyebut
 *      kode mana yang tidak dikenal, bukan sekadar "gagal".
 *
 * Berkas dibuka dari PATH-nya, bukan diterima sudah termuat, karena pemilihan
 * sheet harus terjadi SEBELUM pemuatan: berkas PPI menyimpan satu sheet berisi
 * 1.047.533 baris yang tidak dipakai sama sekali, dan memuatnya menghabiskan
 * memori sampai proses mati. Pembacaan juga dilakukan tanpa format sel — itu
 * sekaligus menyelesaikan kegagalan iconv pada sebagian berkas .xls lama.
 *
 * Kelas ini dipakai jalur upload manual dan otomasi email sekaligus, supaya
 * satu berkas tidak pernah terbaca berbeda di dua jalur.
 */
class StockFileReader
{
    /**
     * Sheet yang tingginya melebihi ini dianggap sisa kerja, bukan data.
     *
     * Berkas PPI menyimpan satu sheet berisi 1.047.533 baris yang tak terpakai.
     * Nama sheet tidak bisa jadi pegangan — namanya 'Sheet1', persis seperti
     * nama sheet data milik grup lain — jadi yang menjaga adalah ukurannya.
     */
    private const MAX_SHEET_ROWS = 300000;

    /** Batas keras baris yang dibaca dari satu sheet. */
    private const MAX_READ_ROWS = 200000;

    /** @var array<string, array<int, array{sheet: string, rows: array}>> memo per kumpulan sheet */
    private array $loaded = [];

    /** @var array<string, array<string, int>> tinggi tiap sheet, per berkas */
    private array $sheetHeights = [];

    /** Pesan galat terakhir saat memuat berkas, untuk dilaporkan ke operator. */
    private ?string $loadError = null;

    public function __construct(private readonly StockImportService $importService) {}

    /**
     * @param  string  $path  berkas di disk
     * @param  ?string  $originalName  nama berkas asli; sebagian distributor
     *                                 hanya menuliskan tanggal di situ
     * @return array{
     *     ok: bool,
     *     group: ?DistributorTemplateGroup,
     *     resolved: array,
     *     buckets: array<string, array<int, array{row: array, excel_row: int, reader: StockRowReader}>>,
     *     placeholder: bool,
     *     sheets: array<int, string>,
     *     error: ?string
     * }
     */
    /**
     * @param  ?DistributorTemplateGroup  $only  paksa memakai SATU resep, tanpa
     *        mencoba grup lain. Dipakai halaman uji coba, yang justru ingin
     *        menilai resep yang sedang disusun — bukan mencari resep terbaik.
     */
    public function read(string $path, ?string $originalName = null, ?DistributorTemplateGroup $only = null): array
    {
        $fileLabel = pathinfo($originalName ?: $path, PATHINFO_FILENAME);
        $sheetNames = $this->sheetNames($path);

        $fallback = null;

        foreach ($only ? [$only] : $this->candidates() as $group) {
            $wanted = $this->sheetsFor($group, $sheetNames, $path);

            // Grup menyebut sheet yang tidak ada di berkas ini: berarti
            // berkasnya bukan milik grup tersebut.
            if ($wanted === []) {
                if ($only) {
                    return [
                        'ok' => false,
                        'group' => $group,
                        'resolved' => [],
                        'buckets' => [],
                        'placeholder' => false,
                        'sheets' => $sheetNames,
                        'error' => 'Sheet "'.$group->sheet_name.'" tidak ada pada berkas ini. Sheet yang tersedia: '.implode(', ', $sheetNames).'.',
                    ];
                }

                continue;
            }

            $segments = $this->segments($path, $wanted);
            $attempt = $this->attempt($segments, $group, $fileLabel, $sheetNames);

            if ($only) {
                return $attempt;
            }

            if (! $attempt['ok']) {
                continue;
            }

            if ($attempt['placeholder']) {
                return $attempt;
            }

            if ($attempt['buckets'] !== [] && $this->allCodesKnown(array_keys($attempt['buckets']))) {
                return $this->preferOwnGroup($attempt, $path, $fileLabel, $sheetNames);
            }

            $fallback ??= $attempt;
        }

        if ($fallback !== null) {
            return $fallback;
        }

        // Tidak satu pun cara baca yang kolom wajibnya lengkap: kembalikan
        // hasil deteksi otomatis apa adanya, karena pesan kesalahannyalah yang
        // paling berguna bagi operator.
        $segments = $this->segments($path, $this->sheetsFor(null, $sheetNames, $path));
        $resolved = (new StockHeaderResolver)->resolve($segments[0]['rows'] ?? []);

        return [
            'ok' => false,
            'group' => null,
            'resolved' => $resolved,
            'buckets' => [],
            'placeholder' => false,
            'sheets' => $sheetNames,
            'error' => $resolved['error'] ?? 'Judul kolom pada berkas Excel tidak dikenali.',
        ];
    }

    /**
     * Satu percobaan membaca: resolusi header per sheet, lalu pemecahan per
     * cabang.
     *
     * Header diselesaikan PER SHEET, bukan sekali untuk semua: berkas SDL
     * memuat satu sheet per cabang, dan tiap sheet punya kop suratnya sendiri.
     *
     * @param  array<int, array{sheet: string, rows: array}>  $segments
     * @param  array<int, string>  $sheetNames
     * @return array<string, mixed>
     */
    private function attempt(array $segments, ?DistributorTemplateGroup $group, string $fileLabel, array $sheetNames): array
    {
        $resolver = new StockHeaderResolver;
        $buckets = [];
        $firstResolved = null;
        $anyOk = false;

        // Berkas SDL menuliskan tanggalnya hanya di sheet pertama; sheet cabang
        // berikutnya kosong. Nilai sel yang sudah ketemu karena itu diwariskan
        // antar sheet, bukan dicari ulang dari nol.
        $cells = [];

        foreach ($segments as $segment) {
            $resolved = $resolver->resolve($segment['rows'], $group);
            $firstResolved ??= $resolved;

            if (! $resolved['ok']) {
                continue;
            }

            $anyOk = true;
            $firstResolved = $resolved;

            $cells = array_filter($this->cellValues($segment['rows'], $group)) + $cells;

            $reader = new StockRowReader($resolved, $group, $this->importService, [
                'sheet' => $segment['sheet'],
                'file' => $fileLabel,
                'cells' => $cells,
            ]);

            $this->bucket($segment['rows'], $resolved, $reader, $group, $buckets);
        }

        return [
            'ok' => $anyOk,
            'group' => $group,
            'resolved' => $firstResolved ?? [],
            'buckets' => $buckets,
            'placeholder' => isset($buckets['XXXX']),
            'sheets' => $sheetNames,
            'error' => $anyOk ? null : ($firstResolved['error']
                ?? ($segments === [] && $this->loadError ? 'Isi berkas gagal dimuat: '.$this->loadError : null)),
        ];
    }

    /**
     * Setelah cabangnya diketahui, grup MILIK cabang itu lebih berhak daripada
     * grup lain yang kebetulan juga sanggup membaca berkasnya — mis. berkas
     * yang punya kolom 'Qty' dan 'Qty Retur' sekaligus, yang hanya resep
     * grupnya sendiri tahu mana yang benar.
     *
     * @param  array<string, mixed>  $attempt
     * @param  array<int, string>  $sheetNames
     * @return array<string, mixed>
     */
    private function preferOwnGroup(array $attempt, string $path, string $fileLabel, array $sheetNames): array
    {
        $firstCode = array_key_first($attempt['buckets']);

        // Bentuk berkas menempel pada grup usaha; kolom di cabang hanya dipakai
        // bila cabang itu memang menyimpang dari grupnya.
        $ownGroup = Distributor::with(['templateGroup', 'group.templateGroup'])
            ->where('distributor_code', $firstCode)
            ->first()?->effectiveTemplateGroup();

        if (! $ownGroup || $ownGroup->id === $attempt['group']?->id) {
            return $attempt;
        }

        $wanted = $this->sheetsFor($ownGroup, $sheetNames, $path);
        if ($wanted === []) {
            return $attempt;
        }

        $own = $this->attempt($this->segments($path, $wanted), $ownGroup, $fileLabel, $sheetNames);

        // Resep grupnya sendiri hanya dipakai kalau hasilnya tetap masuk akal;
        // kalau justru menghasilkan kode yang tidak dikenal, cara baca yang
        // sudah terbukti tadi tetap dipertahankan.
        if (! $own['ok'] || $own['buckets'] === [] || ! $this->allCodesKnown(array_keys($own['buckets']))) {
            return $attempt;
        }

        return $own;
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
     * Sheet mana yang dibaca untuk sebuah grup.
     *
     * Tanpa setelan, dipakai sheet bernama 'Template' bila ada, kalau tidak
     * sheet pertama YANG TIDAK RAKSASA — di sinilah penjagaan ukuran berlaku,
     * karena deteksi otomatis tidak boleh sampai memilih sisa sheet kerja
     * raksasa hanya karena posisinya pertama.
     *
     * Dengan setelan (nama persis atau pola seperti 'SDL *'), sheet yang
     * DIMINTA OPERATOR SENDIRI selalu dicocokkan apa adanya, termasuk yang
     * dilaporkan Excel berukuran raksasa. Metadata tinggi sheet sering keliru
     * — format sel yang pernah menjangkau ribuan baris membuat Excel melaporkan
     * jutaan baris walau isi sungguhannya cuma puluhan — dan operator yang
     * sudah menyebut nama sheetnya sendiri tahu persis sheet mana yang dia
     * maksud. Pembacaan sungguhan tetap dibatasi MAX_READ_ROWS di segments().
     *
     * @param  array<int, string>  $sheetNames
     * @return array<int, string>
     */
    private function sheetsFor(?DistributorTemplateGroup $group, array $sheetNames, string $path): array
    {
        if ($sheetNames === []) {
            return [];
        }

        if (! $group?->sheet_name) {
            $usable = array_values(array_filter(
                $sheetNames,
                fn ($n) => ($this->sheetHeights[$path][$n] ?? 0) <= self::MAX_SHEET_ROWS
            ));

            if ($usable === []) {
                return [];
            }

            $template = array_values(array_filter($usable, fn ($n) => mb_strtolower($n) === 'template'));

            return [$template[0] ?? $usable[0]];
        }

        return array_values(array_filter($sheetNames, fn ($n) => $group->matchesSheet($n)));
    }

    /**
     * Seluruh nama sheet pada berkas, beserta tingginya.
     *
     * Tingginya TIDAK dipakai menyaring daftar ini — daftar ini juga dipakai
     * menyusun pesan galat ("Sheet yang tersedia: ..."), dan sheet raksasa
     * tetap harus disebut di situ supaya operator tidak bingung melihat nama
     * sheet yang jelas ada di Excel-nya tapi "tidak ditemukan" oleh sistem.
     * Penyaringan ukuran baru terjadi di sheetsFor(), dan hanya untuk deteksi
     * otomatis — lihat catatan di sana.
     *
     * @return array<int, string>
     */
    private function sheetNames(string $path): array
    {
        try {
            $info = IOFactory::createReaderForFile($path)->listWorksheetInfo($path);
        } catch (\Throwable) {
            return [];
        }

        $names = [];
        foreach ($info as $sheet) {
            $this->sheetHeights[$path][$sheet['worksheetName']] = (int) $sheet['totalRows'];
            $names[] = $sheet['worksheetName'];
        }

        return $names;
    }

    /**
     * Muat isi beberapa sheet sekaligus, tanpa format sel.
     *
     * @param  array<int, string>  $wanted
     * @return array<int, array{sheet: string, rows: array}>
     */
    private function segments(string $path, array $wanted): array
    {
        if ($wanted === []) {
            return [];
        }

        $key = implode('|', $wanted);
        if (isset($this->loaded[$key])) {
            return $this->loaded[$key];
        }

        // Sebagian berkas .xls lama (mis. GMP) memuat string UTF-16 yang
        // terpotong. iconv() lalu melempar notice, dan Laravel mengubahnya
        // jadi exception — padahal PhpSpreadsheet sudah punya jalan keluar
        // sendiri (mb_convert_encoding) bila iconv() gagal. Notice iconv saja
        // yang diredam supaya jalan keluar itu sempat dipakai.
        $previous = set_error_handler(null);
        restore_error_handler();
        set_error_handler(function (int $no, string $msg, string $file = '', int $line = 0) use (&$previous) {
            if (str_starts_with($msg, 'iconv():')) {
                return true;
            }

            return $previous ? $previous($no, $msg, $file, $line) : false;
        });

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly($wanted);
            $reader->setReadFilter(new MaxRowFilter(self::MAX_READ_ROWS));
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            $this->loadError = $e->getMessage();

            return $this->loaded[$key] = [];
        } finally {
            restore_error_handler();
        }

        $segments = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $segments[] = [
                'sheet' => $sheet->getTitle(),
                'rows' => $sheet->toArray(null, true, false, false),
            ];
        }

        $spreadsheet->disconnectWorksheets();

        return $this->loaded[$key] = $segments;
    }

    /**
     * Isi sel tetap yang dirujuk resep grup, mis. 'A3' berisi judul laporan.
     *
     * @param  array<int, array>  $rows
     * @return array<string, string>
     */
    private function cellValues(array $rows, ?DistributorTemplateGroup $group): array
    {
        $out = [];

        foreach ($group?->recipes() ?? [] as $recipe) {
            foreach ($recipe->cells() as $cell) {
                if (! preg_match('/^([A-Z]+)(\d+)$/', $cell, $m)) {
                    continue;
                }

                $colIndex = 0;
                foreach (str_split($m[1]) as $letter) {
                    $colIndex = $colIndex * 26 + (ord($letter) - 64);
                }

                $out[$cell] = trim((string) ($rows[((int) $m[2]) - 1][$colIndex - 1] ?? ''));
            }
        }

        return $out;
    }

    /** @param array<int, string> $codes */
    private function allCodesKnown(array $codes): bool
    {
        return Distributor::whereIn('distributor_code', $codes)->count() === count($codes);
    }

    /**
     * Kelompokkan baris satu sheet per kode distributor.
     *
     * Kode dibaca PER BARIS, bukan sekali dari baris pertama: satu berkas bisa
     * memuat beberapa cabang sekaligus.
     *
     * Tiga pembersihan dijalankan di sini, dan URUTANNYA menentukan benar
     * tidaknya hasil:
     *
     *   1. Baris yang mengulang judul kolom dilewati — berkas SDL memulai blok
     *      gudang baru dengan judul kolom yang sama persis.
     *   2. Baris total dilewati. Dikenali dari kolom CABANG, bukan nama item:
     *      ada produk sungguhan bernama 'Total Parenteral Nutrition'.
     *   3. Baru setelah itu sel kosong diwarisi dari baris di atasnya. Kalau
     *      dibalik, baris total PPI — yang nama produknya kosong — akan terisi
     *      nama produk baris sebelumnya lalu terhitung sebagai stok ganda.
     *
     * @param  array<int, array>  $rows
     * @param  array<string, mixed>  $buckets  diisi di tempat
     */
    private function bucket(array $rows, array $resolved, StockRowReader $reader, ?DistributorTemplateGroup $group, array &$buckets): void
    {
        $headerRow = $resolved['header_row'];
        $headerSignature = $this->signature($rows[$headerRow] ?? []);
        $headerCells = $this->mappedHeaderCells($rows[$headerRow] ?? [], $resolved);

        $fillDownIndexes = [];
        foreach ($group?->fillDownColumns() ?? [] as $canonical) {
            $idx = $resolved['col'][$canonical] ?? null;
            if ($idx !== null && $idx >= 0) {
                $fillDownIndexes[] = $idx;
            }
        }

        $lastSeen = [];
        $excelRow = $headerRow + 1;

        foreach (array_slice($rows, $headerRow + 1) as $row) {
            $excelRow++;

            if (! is_array($row)) {
                continue;
            }

            if ($headerSignature !== '' && $this->signature($row) === $headerSignature) {
                continue;
            }

            // Judul berulang yang tidak persis sama — berkas SDL menambahkan
            // nama gudang di ujung baris judul keduanya, sehingga pembanding
            // seluruh baris di atas meloloskannya dan "Nama Barang" terbaca
            // sebagai item.
            if ($this->repeatsHeader($row, $headerCells)) {
                continue;
            }

            if ($this->isTotalRow($row, $resolved)) {
                continue;
            }

            foreach ($fillDownIndexes as $idx) {
                $value = trim((string) ($row[$idx] ?? ''));
                if ($value !== '') {
                    $lastSeen[$idx] = $row[$idx];
                } elseif (isset($lastSeen[$idx])) {
                    $row[$idx] = $lastSeen[$idx];
                }
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

            $buckets[$code][] = ['row' => $row, 'excel_row' => $excelRow, 'reader' => $reader];
        }
    }

    /**
     * Judul kolom pada kolom-kolom yang dipetakan, index => judul ternormalisasi.
     *
     * @param  array<int, mixed>  $header
     * @return array<int, string>
     */
    private function mappedHeaderCells(array $header, array $resolved): array
    {
        $out = [];
        foreach ($resolved['col'] ?? [] as $idx) {
            $text = mb_strtolower(trim((string) ($header[$idx] ?? '')));
            if ($idx >= 0 && $text !== '') {
                $out[$idx] = $text;
            }
        }

        return $out;
    }

    /**
     * Baris ini mengulang judul kolom? Semua kolom yang dipetakan harus berisi
     * judulnya sendiri — cukup ketat supaya baris data tak pernah terbuang.
     *
     * @param  array<int, mixed>  $row
     * @param  array<int, string>  $headerCells
     */
    private function repeatsHeader(array $row, array $headerCells): bool
    {
        if (count($headerCells) < 2) {
            return false;
        }

        foreach ($headerCells as $idx => $text) {
            if (mb_strtolower(trim((string) ($row[$idx] ?? ''))) !== $text) {
                return false;
            }
        }

        return true;
    }

    /** Sidik jari isi satu baris, dipakai mengenali judul kolom yang berulang. */
    private function signature(array $row): string
    {
        $cells = array_filter(array_map(fn ($c) => mb_strtolower(trim((string) $c)), $row), fn ($c) => $c !== '');

        return implode('|', $cells);
    }

    /**
     * Baris subtotal/total? Diperiksa pada kolom cabang saja.
     *
     * @param  array<int, mixed>  $row
     */
    private function isTotalRow(array $row, array $resolved): bool
    {
        $idx = $resolved['col']['ID DISTRIBUTOR'] ?? null;

        if ($idx === null || $idx < 0) {
            return false;
        }

        $value = mb_strtolower(trim((string) ($row[$idx] ?? '')));

        return $value !== '' && preg_match('/(^|\s)(grand\s+)?total$/', $value) === 1;
    }
}

/**
 * Penjaga terakhir: berapa pun besar sheet-nya, yang dibaca terbatas.
 *
 * Sheet Excel bisa melaporkan sejuta baris hanya karena pernah ada rumus di
 * sana, dan memuatnya utuh mematikan proses sebelum sempat ada pesan galat.
 */
final class MaxRowFilter implements IReadFilter
{
    public function __construct(private readonly int $maxRow) {}

    public function readCell($column, $row, $worksheetName = ''): bool
    {
        return $row <= $this->maxRow;
    }
}
