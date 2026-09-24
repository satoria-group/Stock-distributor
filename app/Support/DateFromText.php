<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Menemukan tanggal di dalam teks bebas.
 *
 * Kebanyakan berkas distributor tidak punya kolom tanggal sama sekali —
 * tanggalnya tertulis sebagai judul laporan atau bahkan hanya pada nama
 * berkas:
 *
 *   "Tgl : 23/09/2026"                          → 23 September 2026
 *   "PER 21 SEPTEMBER 2026"                     → 21 September 2026
 *   "Tgl/Periode: 01-09-2026 s/d 22-09-2026"    → 22 September 2026 (yang AKHIR)
 *   "Stock Satoria 23 September 2026.xlsx"      → 23 September 2026
 *   "Stok Satoria Tgl. 23-09-26"                → 23 September 2026
 *   "...ANEKA INDUSTRI_21_09_2026 - UDC"        → 21 September 2026
 *
 * Bila teksnya memuat lebih dari satu tanggal, yang diambil adalah yang
 * TERAKHIR: bentuk seperti itu selalu berupa rentang periode, dan posisi stok
 * yang dilaporkan adalah posisi di akhir periode, bukan awalnya.
 */
class DateFromText
{
    /** Nama bulan Indonesia beserta singkatan yang lazim dipakai. */
    private const MONTHS = [
        'januari' => 1, 'jan' => 1,
        'februari' => 2, 'pebruari' => 2, 'feb' => 2, 'peb' => 2,
        'maret' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5,
        'juni' => 6, 'jun' => 6,
        'juli' => 7, 'jul' => 7,
        'agustus' => 8, 'agu' => 8, 'ags' => 8, 'agt' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10,
        'november' => 11, 'nopember' => 11, 'nov' => 11, 'nop' => 11,
        'desember' => 12, 'des' => 12,
        // Bentuk Inggris ikut dikenali: berkas ekspor sistem kerap memakainya.
        'january' => 1, 'february' => 2, 'march' => 3, 'may' => 5,
        'june' => 6, 'july' => 7, 'august' => 8, 'aug' => 8,
        'october' => 10, 'oct' => 10, 'december' => 12, 'dec' => 12,
    ];

    /**
     * @param  string  $prefer  'last' (bawaan) atau 'first' bila yang dicari
     *                          justru tanggal pertama pada sebuah rentang
     * @return ?string  Y-m-d
     */
    public static function find(?string $text, string $prefer = 'last'): ?string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        $found = array_merge(self::findNumeric($text), self::findNamedMonth($text));

        if ($found === []) {
            return null;
        }

        // Diurutkan menurut posisi kemunculan, bukan menurut nilai tanggalnya:
        // "01-09-2026 s/d 22-09-2026" harus menghasilkan yang tertulis
        // belakangan, bukan yang paling besar — keduanya kebetulan sama di sini,
        // tapi tidak selalu.
        usort($found, fn ($a, $b) => $a['pos'] <=> $b['pos']);

        $pick = $prefer === 'first' ? reset($found) : end($found);

        return $pick['date'];
    }

    /**
     * Bentuk berangka: 23/09/2026, 23-09-2026, 23.09.26, 2026-09-23.
     *
     * @return array<int, array{pos: int, date: string}>
     */
    private static function findNumeric(string $text): array
    {
        $out = [];

        if (preg_match_all('/(\d{1,4})[\/\-._](\d{1,2})[\/\-._](\d{2,4})(?![\d])/', $text, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $i => $whole) {
                $a = (int) $m[1][$i][0];
                $b = (int) $m[2][$i][0];
                $c = (int) $m[3][$i][0];

                // Empat digit di depan berarti tahun-bulan-hari.
                [$day, $month, $year] = strlen($m[1][$i][0]) === 4 ? [$c, $b, $a] : [$a, $b, $c];

                $date = self::build($day, $month, $year);
                if ($date) {
                    $out[] = ['pos' => $whole[1], 'date' => $date];
                }
            }
        }

        return $out;
    }

    /**
     * Bentuk bernama bulan: "21 SEPTEMBER 2026", "23 Sept 26".
     *
     * @return array<int, array{pos: int, date: string}>
     */
    private static function findNamedMonth(string $text): array
    {
        $names = implode('|', array_map('preg_quote', array_keys(self::MONTHS)));
        $out = [];

        if (preg_match_all('/\b(\d{1,2})\s+('.$names.')\.?\s+(\d{2,4})\b/i', $text, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $i => $whole) {
                $month = self::MONTHS[mb_strtolower($m[2][$i][0])] ?? null;
                if (! $month) {
                    continue;
                }

                $date = self::build((int) $m[1][$i][0], $month, (int) $m[3][$i][0]);
                if ($date) {
                    $out[] = ['pos' => $whole[1], 'date' => $date];
                }
            }
        }

        return $out;
    }

    private static function build(int $day, int $month, int $year): ?string
    {
        // Tahun dua digit selalu 2000-an: berkas stok tidak bicara soal 1926.
        if ($year < 100) {
            $year += 2000;
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::create($year, $month, $day)?->toDateString();
    }
}
