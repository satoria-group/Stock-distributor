<?php

namespace App\Support;

use App\Models\DistributorTemplateGroup;

/**
 * Menemukan baris header dan memetakan judul kolom berkas Excel apa pun
 * ke nama kolom kanonik (lihat StockTemplateColumns).
 *
 * Ada tiga sumber pemetaan, diterapkan berurutan dari yang paling spesifik:
 *
 *   1. Resep milik Grup Template distributor (kalau grupnya diberikan) — bisa
 *      merangkai beberapa kolom dan teks tetap sekaligus; lihat
 *      StockColumnRecipe.
 *   2. Daftar sinonim bawaan di StockTemplateColumns.
 *   3. Kecocokan sebagian (judul berkas mengandung sinonim, atau sebaliknya) —
 *      hanya dipakai kalau tidak ada kandidat yang cocok persis, supaya
 *      'Qty Retur' tidak merebut slot 'Quantity' dari kolom 'Qty'.
 *
 * Baris header TIDAK diasumsikan ada di baris 1: berkas distributor kerap
 * punya kop surat, judul laporan, atau baris kosong di atasnya. Baris dengan
 * skor kecocokan tertinggi dalam SCAN_ROWS baris pertama yang dipakai.
 */
class StockHeaderResolver
{
    /** Sejauh mana baris header dicari dari atas berkas. */
    private const SCAN_ROWS = 25;

    /**
     * @param  array<int, array<int, mixed>>  $data  hasil Worksheet::toArray() (index numerik)
     * @return array{
     *     ok: bool,
     *     header_row: int,
     *     col: array<string, int>,
     *     index_by_header: array<string, int>,
     *     recipes: array<string, StockColumnRecipe>,
     *     headers: array<int, string>,
     *     missing: array<int, string>,
     *     error: ?string
     * }  header_row = index 0-based baris header di dalam $data;
     *    col = kolom kanonik => index kolom penambat (untuk resep berbilang
     *    kolom, index kolom pertamanya);
     *    index_by_header = judul ternormalisasi => index, dipakai resep untuk
     *    mengambil nilai tiap bagiannya.
     */
    public function resolve(array $data, ?DistributorTemplateGroup $group = null): array
    {
        $recipes = $group?->recipes() ?? [];

        $forcedRow = $group?->header_row ? ((int) $group->header_row - 1) : null;
        $candidateRows = $forcedRow !== null
            ? [$forcedRow]
            : range(0, min(self::SCAN_ROWS, max(count($data) - 1, 0)));

        $best = null;

        foreach ($candidateRows as $rowIdx) {
            if (! isset($data[$rowIdx]) || ! is_array($data[$rowIdx])) {
                continue;
            }

            $headers = [];
            foreach ($data[$rowIdx] as $idx => $value) {
                $headers[$idx] = trim((string) $value);
            }

            $mapped = $this->mapHeaders($headers, $recipes);
            $score = $this->score($mapped['col']);

            if ($best === null || $score > $best['score']) {
                $best = [
                    'row' => $rowIdx,
                    'col' => $mapped['col'],
                    'index_by_header' => $mapped['index_by_header'],
                    'headers' => $headers,
                    'score' => $score,
                ];
            }

            // Semua kolom kanonik ketemu — tidak ada gunanya mencari lebih jauh.
            if (count($mapped['col']) === count(StockTemplateColumns::all())) {
                break;
            }
        }

        if ($best === null) {
            return [
                'ok' => false,
                'header_row' => 0,
                'col' => [],
                'index_by_header' => [],
                'recipes' => $recipes,
                'headers' => [],
                'missing' => StockTemplateColumns::required(),
                'error' => 'Berkas Excel tidak memiliki baris yang bisa dibaca sebagai header kolom.',
            ];
        }

        $missing = array_values(array_diff(StockTemplateColumns::required(), array_keys($best['col'])));

        return [
            'ok' => $missing === [],
            'header_row' => $best['row'],
            'col' => $best['col'],
            'index_by_header' => $best['index_by_header'],
            'recipes' => $recipes,
            'headers' => $best['headers'],
            'missing' => $missing,
            'error' => $missing === [] ? null : $this->missingMessage($missing, $best['headers'], $group),
        ];
    }

    /**
     * Memetakan satu baris judul ke nama kanonik.
     *
     * @param  array<int, string>  $headers  index kolom => judul mentah
     * @param  array<string, StockColumnRecipe>  $recipes  resep milik grup
     * @return array{col: array<string, int>, index_by_header: array<string, int>}
     */
    public function mapHeaders(array $headers, array $recipes = []): array
    {
        $normalized = [];
        $indexByHeader = [];
        foreach ($headers as $idx => $raw) {
            $n = StockTemplateColumns::normalize($raw);
            if ($n === '') {
                continue;
            }
            $normalized[$idx] = $n;
            // Judul kembar: yang pertama menang, supaya nilainya tidak
            // berpindah-pindah tergantung urutan pembacaan.
            $indexByHeader[$n] ??= $idx;
        }

        $col = [];
        $taken = [];

        // Lapis 1: resep grup. Sebuah kolom kanonik hanya dianggap ketemu bila
        // SELURUH kolom yang dibutuhkan resepnya ada — resep 'UDC' + CUST_NAME
        // tidak ada artinya kalau CUST_NAME-nya sendiri tidak ada di berkas.
        foreach ($recipes as $canonical => $recipe) {
            $anchor = null;
            $complete = true;

            foreach ($recipe->columns() as $header) {
                $idx = $indexByHeader[StockTemplateColumns::normalize($header)] ?? null;
                if ($idx === null) {
                    $complete = false;
                    break;
                }
                $anchor ??= $idx;
                $taken[$idx] = true;
            }

            if (! $complete) {
                continue;
            }

            // Resep murni teks tetap tidak menambat kolom mana pun, tapi tetap
            // selalu bisa dihitung — tandai dengan -1.
            $col[$canonical] = $anchor ?? -1;
        }

        // Lapis 2: kecocokan persis dengan sinonim bawaan.
        foreach (StockTemplateColumns::definitions() as $canonical => $def) {
            if (isset($col[$canonical])) {
                continue;
            }

            $needles = [StockTemplateColumns::normalize($canonical)];
            foreach ($def['synonyms'] as $syn) {
                $needles[] = StockTemplateColumns::normalize($syn);
            }

            foreach ($needles as $needle) {
                foreach ($normalized as $idx => $n) {
                    if (isset($taken[$idx]) || $n !== $needle) {
                        continue;
                    }
                    $col[$canonical] = $idx;
                    $taken[$idx] = true;

                    continue 3;
                }
            }
        }

        // Lapis 3: kecocokan sebagian, hanya untuk kanonik yang masih kosong.
        foreach (StockTemplateColumns::definitions() as $canonical => $def) {
            if (isset($col[$canonical])) {
                continue;
            }

            $needles = array_map(
                fn ($s) => StockTemplateColumns::normalize($s),
                array_merge([$canonical], $def['synonyms'])
            );

            foreach ($normalized as $idx => $n) {
                if (isset($taken[$idx])) {
                    continue;
                }
                foreach ($needles as $needle) {
                    // Sinonim sangat pendek ('ed', 'qty') terlalu mudah muncul
                    // di tengah kata lain, jadi tidak ikut pencocokan sebagian.
                    if (mb_strlen($needle) < 4) {
                        continue;
                    }
                    if (str_contains($n, $needle) || str_contains($needle, $n)) {
                        $col[$canonical] = $idx;
                        $taken[$idx] = true;

                        continue 3;
                    }
                }
            }
        }

        return ['col' => $col, 'index_by_header' => $indexByHeader];
    }

    /**
     * Skor sebuah baris sebagai kandidat header: kolom wajib dihargai lebih
     * mahal supaya baris berisi data (yang kebetulan cocok satu-dua kolom)
     * tidak pernah mengalahkan baris header sesungguhnya.
     *
     * @param  array<string, int>  $col
     */
    private function score(array $col): int
    {
        $required = StockTemplateColumns::required();
        $score = 0;
        foreach (array_keys($col) as $canonical) {
            $score += in_array($canonical, $required, true) ? 3 : 1;
        }

        return $score;
    }

    /**
     * @param  array<int, string>  $missing
     * @param  array<int, string>  $headers
     */
    private function missingMessage(array $missing, array $headers, ?DistributorTemplateGroup $group): string
    {
        $found = array_values(array_filter(array_map('trim', $headers), fn ($h) => $h !== ''));
        $foundText = $found === [] ? '(tidak ada judul kolom terbaca)' : implode(', ', array_slice($found, 0, 12));

        $msg = 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Judul kolom yang terbaca di berkas: '.$foundText.'.';

        $msg .= $group
            ? ' Perbarui pemetaan kolom pada Grup Template "'.$group->name.'" di menu Grup Template Excel.'
            : ' Distributor ini belum punya Grup Template. Daftarkan template-nya di menu Grup Template Excel agar judul kolom khas ini dikenali.';

        return $msg;
    }
}
