<?php

namespace App\Support;

/**
 * Resep pembentukan satu nilai kolom kanonik dari sebuah baris Excel.
 *
 * Pemetaan "satu kolom kanonik = satu judul kolom" ternyata tidak cukup untuk
 * berkas yang sesungguhnya beredar:
 *
 *   UDC   ID DISTRIBUTOR = teks "UDC" + isi kolom CUST_NAME
 *   MAM   ID DISTRIBUTOR = isi kolom Distributor + teks "JAKARTA"
 *   UDC   Tanggal        = isi kolom TGL + BLN + TAHUN
 *   GMP   ID DISTRIBUTOR = isi kolom DISTRIBUTOR, tanpa spasi
 *
 * Maka nilai pada column_map boleh berupa dua bentuk:
 *
 *   'SOH_QTY'                                   → satu kolom (bentuk lama)
 *   ['parts' => [['text' => 'UDC'], ['column' => 'CUST_NAME']],
 *    'nospace' => true, 'upper' => true]        → resep
 *
 * Bentuk lama sengaja tetap sah: mapping yang sudah tersimpan tidak perlu
 * dimigrasikan, dan mayoritas grup memang hanya butuh satu kolom.
 */
class StockColumnRecipe
{
    /**
     * @param  array<int, array{text?: string, column?: string}>  $parts
     */
    private function __construct(
        public readonly array $parts,
        public readonly bool $nospace = false,
        public readonly bool $upper = false,
        public readonly string $glue = '',
    ) {}

    /**
     * @param  mixed  $value  isi column_map untuk satu kolom kanonik
     */
    public static function fromValue(mixed $value): ?self
    {
        if (is_string($value)) {
            $header = trim($value);

            return $header === '' ? null : new self([['column' => $header]]);
        }

        if (! is_array($value)) {
            return null;
        }

        $parts = [];
        foreach ((array) ($value['parts'] ?? []) as $part) {
            if (! is_array($part)) {
                continue;
            }

            if (isset($part['column']) && trim((string) $part['column']) !== '') {
                $parts[] = ['column' => trim((string) $part['column'])];
            } elseif (isset($part['text']) && (string) $part['text'] !== '') {
                $parts[] = ['text' => (string) $part['text']];
            } elseif (isset($part['cell']) && trim((string) $part['cell']) !== '') {
                $parts[] = ['cell' => mb_strtoupper(trim((string) $part['cell']))];
            } elseif (! empty($part['sheet'])) {
                $parts[] = ['sheet' => true];
            } elseif (! empty($part['file'])) {
                $parts[] = ['file' => true];
            }
        }

        if ($parts === []) {
            return null;
        }

        return new self(
            $parts,
            (bool) ($value['nospace'] ?? false),
            (bool) ($value['upper'] ?? false),
            (string) ($value['glue'] ?? ''),
        );
    }

    /**
     * Judul kolom yang dibutuhkan resep ini, apa adanya seperti diketik.
     *
     * @return array<int, string>
     */
    public function columns(): array
    {
        $out = [];
        foreach ($this->parts as $part) {
            if (isset($part['column'])) {
                $out[] = $part['column'];
            }
        }

        return $out;
    }

    /**
     * Resep ini mengambil nilai dari luar baris (sel tetap, nama sheet, nama
     * berkas)?
     *
     * Nilai seperti itu hampir selalu berupa kalimat bebas — 'Tgl : 23/09/2026',
     * bukan sel tanggal — jadi pemanggil perlu tahu bahwa isinya harus digali
     * dulu, bukan dibaca apa adanya.
     */
    public function usesFileContext(): bool
    {
        foreach ($this->parts as $part) {
            if (isset($part['cell']) || isset($part['sheet']) || isset($part['file'])) {
                return true;
            }
        }

        return false;
    }

    /** Resep tanpa satu pun kolom (murni teks tetap) selalu bisa dihitung. */
    public function needsColumns(): bool
    {
        return $this->columns() !== [];
    }

    /**
     * Bentuk penyimpanan untuk column_map — teks polos bila cukup satu kolom
     * tanpa perlakuan tambahan, supaya isi kolom JSON tetap mudah dibaca.
     */
    public function toStorage(): array|string
    {
        if (count($this->parts) === 1 && isset($this->parts[0]['column']) && ! $this->nospace && ! $this->upper) {
            return $this->parts[0]['column'];
        }

        return array_filter([
            'parts' => $this->parts,
            'nospace' => $this->nospace ?: null,
            'upper' => $this->upper ?: null,
            'glue' => $this->glue !== '' ? $this->glue : null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Susun nilai untuk satu baris.
     *
     * @param  array<int, mixed>  $row  satu baris hasil Worksheet::toArray()
     * @param  array<string, int>  $indexByHeader  judul ternormalisasi => index kolom
     * @return ?string  null bila SEMUA bagian kolomnya tidak ada/kosong —
     *                  dibedakan dari string kosong supaya pemanggil bisa
     *                  membedakan "kolomnya tidak ada" dari "selnya kosong".
     */
    public function value(array $row, array $indexByHeader, array $context = []): ?string
    {
        $pieces = [];
        $sawColumnValue = false;

        foreach ($this->parts as $part) {
            if (isset($part['text'])) {
                $pieces[] = $part['text'];

                continue;
            }

            // Bahan yang nilainya berasal dari berkas, bukan dari baris:
            // banyak berkas menaruh tanggal di judul laporan, dan menaruh
            // cabang pada nama sheet-nya.
            if (isset($part['cell'])) {
                $pieces[] = (string) ($context['cells'][$part['cell']] ?? '');

                continue;
            }
            if (isset($part['sheet'])) {
                $pieces[] = (string) ($context['sheet'] ?? '');

                continue;
            }
            if (isset($part['file'])) {
                $pieces[] = (string) ($context['file'] ?? '');

                continue;
            }

            $idx = $indexByHeader[StockTemplateColumns::normalize($part['column'])] ?? null;
            if ($idx === null) {
                continue;
            }

            $raw = $row[$idx] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }

            $sawColumnValue = true;
            $pieces[] = trim((string) $raw);
        }

        if ($this->needsColumns() && ! $sawColumnValue) {
            return null;
        }

        $value = implode($this->glue, $pieces);

        if ($this->nospace) {
            $value = preg_replace('/[\s\x{00A0}]+/u', '', $value) ?? $value;
        }
        if ($this->upper) {
            $value = mb_strtoupper($value);
        }

        return trim($value);
    }

    /** Sel tetap yang dibutuhkan resep ini, mis. ['B1', 'A3']. */
    public function cells(): array
    {
        $out = [];
        foreach ($this->parts as $part) {
            if (isset($part['cell'])) {
                $out[] = $part['cell'];
            }
        }

        return $out;
    }

    /**
     * Nilai tiap bagian kolom secara terpisah, tanpa dirangkai.
     *
     * Dipakai tanggal yang terpecah beberapa kolom (UDC: TGL, BLN, TAHUN),
     * yang perlu tahu mana hari, mana bulan, mana tahun — bukan hasil
     * sambungannya.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $indexByHeader
     * @return array<int, string>
     */
    public function columnValues(array $row, array $indexByHeader): array
    {
        $out = [];
        foreach ($this->columns() as $header) {
            $idx = $indexByHeader[StockTemplateColumns::normalize($header)] ?? null;
            if ($idx === null) {
                continue;
            }
            $out[] = trim((string) ($row[$idx] ?? ''));
        }

        return $out;
    }
}
