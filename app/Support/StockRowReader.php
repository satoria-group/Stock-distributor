<?php

namespace App\Support;

use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use Carbon\Carbon;

/**
 * Membaca satu baris Excel menjadi nilai-nilai kanonik.
 *
 * Seluruh kerumitan bentuk berkas berhenti di kelas ini: resep berbilang
 * kolom, tanggal yang terpecah tiga kolom, periode yang cuma berisi bulan,
 * batch yang tidak ada kolomnya, dan baris berstok nol. Pemanggilnya — halaman
 * Upload maupun otomasi email — cukup bertanya "berapa kuantitas baris ini?"
 * tanpa tahu grup mana yang sedang dibaca.
 *
 * Dibuat dari hasil StockHeaderResolver::resolve() supaya index kolom yang
 * dipakai persis sama dengan yang dipakai saat menilai baris header.
 */
class StockRowReader
{
    /** @param array{col: array<string,int>, index_by_header: array<string,int>, recipes: array<string,StockColumnRecipe>} $resolved */
    public function __construct(
        private readonly array $resolved,
        private readonly ?DistributorTemplateGroup $group,
        private readonly StockImportService $importService,
    ) {}

    /** Kolom kanonik ini bisa dibaca dari berkas? */
    public function has(string $canonical): bool
    {
        return isset($this->resolved['recipes'][$canonical])
            || isset($this->resolved['col'][$canonical]);
    }

    /**
     * Nilai mentah sebuah kolom kanonik untuk satu baris.
     *
     * @param  array<int, mixed>  $row
     */
    public function raw(array $row, string $canonical): ?string
    {
        $recipe = $this->resolved['recipes'][$canonical] ?? null;
        if ($recipe) {
            return $recipe->value($row, $this->resolved['index_by_header']);
        }

        $idx = $this->resolved['col'][$canonical] ?? null;
        if ($idx === null || $idx < 0) {
            return null;
        }

        $value = $row[$idx] ?? null;

        return $value === null ? null : trim((string) $value);
    }

    /** @param array<int, mixed> $row */
    public function itemName(array $row): string
    {
        return (string) $this->raw($row, 'Distributor Item Name');
    }

    /** @param array<int, mixed> $row */
    public function distributorCode(array $row): string
    {
        return trim((string) $this->raw($row, 'ID DISTRIBUTOR'));
    }

    /** @param array<int, mixed> $row */
    public function quantity(array $row): float
    {
        $recipe = $this->resolved['recipes']['Quantity'] ?? null;

        // Kuantitas harus melewati parseQuantity dalam bentuk seaslinya
        // (termasuk tipe numerik dari PhpSpreadsheet), jadi untuk kolom
        // tunggal nilai selnya diambil langsung tanpa lewat perangkaian resep
        // — perangkaian mengubahnya jadi string dan membuang tipe aslinya.
        if (! $recipe || count($recipe->columns()) === 1) {
            $idx = $this->resolved['col']['Quantity'] ?? null;
            if ($idx !== null && $idx >= 0) {
                return $this->importService->parseQuantity($row[$idx] ?? 0);
            }
        }

        return $this->importService->parseQuantity($this->raw($row, 'Quantity') ?? 0);
    }

    /** @param array<int, mixed> $row */
    public function satuan(array $row): ?string
    {
        $value = $this->raw($row, 'Satuan');

        return ($value === null || $value === '') ? null : $value;
    }

    /**
     * Tanggal kedaluwarsa.
     *
     * Bila grupnya menetapkan format ED, format itu dicoba lebih dulu — ada
     * bentuk yang mustahil ditebak benar, misalnya '12/2027' yang tak punya
     * hari, atau '01/02/2027' yang ambigu antara 1 Februari dan 2 Januari.
     * Format bulan/tahun diambil TANGGAL 1: untuk FEFO, memajukan kedaluwarsa
     * lebih aman daripada mengundurkannya.
     *
     * Deteksi otomatis tetap dipakai sebagai cadangan, karena sel Excel
     * bertipe tanggal datang sebagai angka seri yang tidak cocok dengan format
     * teks mana pun.
     *
     * @param  array<int, mixed>  $row
     * @return ?string  Y-m-d
     */
    public function expiredDate(array $row): ?string
    {
        $idx = $this->resolved['col']['ED'] ?? null;
        $value = (! isset($this->resolved['recipes']['ED']) && $idx !== null && $idx >= 0)
            ? ($row[$idx] ?? null)
            : $this->raw($row, 'ED');

        if ($value === null || $value === '') {
            return null;
        }

        $format = $this->group?->phpEdFormat();
        if ($format) {
            $parsed = $this->tryFormat(trim((string) $value), $format);

            if ($parsed) {
                return DistributorTemplateGroup::formatIsMonthOnly($format)
                    ? $parsed->startOfMonth()->toDateString()
                    : $parsed->toDateString();
            }
        }

        return $this->importService->parseExcelDate($value);
    }

    /**
     * Nomor batch, dengan batch pengganti milik grup sebagai jaring pengaman.
     *
     * GMP, SDL, dan MAM tidak punya kolom batch sama sekali, sementara batch
     * adalah bagian identitas baris stok — tanpa nilai pengganti, berkas
     * mereka ditolak seluruhnya oleh aturan FEFO.
     *
     * @param  array<int, mixed>  $row
     */
    public function batchNo(array $row): ?string
    {
        $value = $this->raw($row, 'Batch No');
        $value = $value === null ? '' : trim($value);

        if ($value !== '') {
            return $value;
        }

        $fallback = trim((string) ($this->group?->default_batch ?? ''));

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * Tanggal snapshot untuk satu baris.
     *
     * @param  array<int, mixed>  $row
     * @return ?string  Y-m-d
     */
    public function tanggal(array $row): ?string
    {
        $mode = $this->group?->dateMode() ?? DistributorTemplateGroup::DATE_AUTO;
        $recipe = $this->resolved['recipes']['Tanggal'] ?? null;

        // Resep Tanggal yang merangkai tiga kolom atau lebih SUDAH menyatakan
        // maksudnya: hari, bulan, tahun. Meminta operator menegaskannya sekali
        // lagi lewat setelan terpisah hanya menciptakan dua sumber kebenaran
        // yang bisa saling bertentangan.
        if ($recipe && count($recipe->columns()) >= 3) {
            return $this->tanggalFromParts($recipe->columnValues($row, $this->resolved['index_by_header']));
        }

        $rawValue = $this->rawTanggal($row);
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        if ($mode === DistributorTemplateGroup::DATE_MONTH) {
            return $this->tanggalFromMonth($rawValue);
        }

        if ($mode === DistributorTemplateGroup::DATE_FORMAT && $this->group?->phpDateFormat()) {
            $parsed = $this->tryFormat((string) $rawValue, $this->group->phpDateFormat());
            if ($parsed) {
                return $parsed->toDateString();
            }
        }

        return $this->importService->parseExcelDate($rawValue);
    }

    /**
     * Nilai tanggal apa adanya — tipe aslinya dipertahankan, karena Excel
     * kerap menyimpan tanggal sebagai angka seri yang hanya bisa dibaca
     * selama belum berubah jadi string.
     *
     * @param  array<int, mixed>  $row
     */
    public function rawTanggal(array $row): mixed
    {
        $idx = $this->resolved['col']['Tanggal'] ?? null;
        if (! isset($this->resolved['recipes']['Tanggal']) && $idx !== null && $idx >= 0) {
            return $row[$idx] ?? null;
        }

        return $this->raw($row, 'Tanggal');
    }

    /**
     * Baris ini dilewati tanpa dianggap kesalahan?
     *
     * KFTD mengirim seluruh isi gudang termasuk baris berstok nol; menolaknya
     * sebagai error akan membuat setiap berkas mereka gagal.
     *
     * @param  array<int, mixed>  $row
     */
    public function shouldSkip(array $row): bool
    {
        if (! ($this->group?->skip_nonpositive_qty)) {
            return false;
        }

        return $this->quantity($row) <= 0;
    }

    /**
     * Tanggal dari kolom-kolom terpisah, urut hari-bulan-tahun (UDC).
     *
     * @param  array<int, string>  $parts
     */
    private function tanggalFromParts(array $parts): ?string
    {
        $parts = array_values(array_filter($parts, fn ($p) => trim($p) !== ''));

        if (count($parts) < 3) {
            return null;
        }

        [$d, $m, $y] = [trim($parts[0]), trim($parts[1]), trim($parts[2])];

        // Tahun dua digit ('26') diperlakukan sebagai 2000-an: berkas stok
        // tidak pernah bicara soal tahun 1926.
        if (preg_match('/^\d{2}$/', $y)) {
            $y = '20'.$y;
        }

        // Bulan boleh angka ('9', '09') maupun nama ('SEP', 'September').
        $candidates = is_numeric($m)
            ? [sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d)]
            : [$d.' '.$m.' '.$y];

        foreach ($candidates as $candidate) {
            try {
                $parsed = Carbon::parse($candidate);
            } catch (\Throwable) {
                continue;
            }

            return $parsed->toDateString();
        }

        return null;
    }

    /**
     * Periode yang hanya berisi bulan — snapshot memakai HARI TERAKHIR bulan
     * itu, karena laporan bulanan menggambarkan posisi stok pada akhir periode.
     */
    private function tanggalFromMonth(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($this->group?->phpDateFormat()) {
            $parsed = $this->tryFormat($raw, $this->group->phpDateFormat());
            if ($parsed) {
                return $parsed->endOfMonth()->toDateString();
            }
        }

        // 202609 / 2026-09 / Sep-2026 / September 2026
        if (preg_match('/^(\d{4})[-\/ ]?(\d{2})$/', $raw, $m)) {
            try {
                return Carbon::create((int) $m[1], (int) $m[2], 1)?->endOfMonth()->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($raw)->endOfMonth()->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Baca sebuah nilai dengan format yang ditetapkan.
     *
     * Dua kehati-hatian yang wajib ada di sini:
     *
     *  - Awalan '!' mengosongkan semua ruas yang tidak disebut format. Tanpa
     *    itu, '12/2027' dibaca dengan HARI INI sebagai harinya — dan pada
     *    tanggal 31, bulan Februari meluber jadi 3 Maret.
     *  - Hasilnya dibentuk ulang dan dibandingkan dengan nilai asalnya, supaya
     *    nilai yang sebenarnya tidak berformat itu jatuh ke deteksi otomatis
     *    alih-alih diterima sebagai tanggal yang keliru.
     */
    private function tryFormat(string $value, string $format): ?Carbon
    {
        try {
            $parsed = Carbon::createFromFormat('!'.$format, $value);
        } catch (\Throwable) {
            return null;
        }

        if (! $parsed || $parsed->format($format) !== $value) {
            return null;
        }

        return $parsed;
    }
}
