<?php

namespace Tests\Unit;

use App\Services\StockImportService;
use PHPUnit\Framework\TestCase;

class QuantityParserTest extends TestCase
{
    private StockImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockImportService();
    }

    /**
     * @dataProvider quantityProvider
     */
    public function test_parses_quantity(mixed $input, float $expected, string $why): void
    {
        $this->assertSame($expected, $this->service->parseQuantity($input), $why);
    }

    public static function quantityProvider(): array
    {
        return [
            // Nilai numerik asli dari PhpSpreadsheet
            'integer'                 => [1200, 1200.0, 'angka bulat'],
            'float'                   => [1200.5, 1200.5, 'float apa adanya'],
            'nol'                     => [0, 0.0, 'nol'],

            // Teks polos
            'teks angka'              => ['1200', 1200.0, 'teks angka polos'],
            'spasi di sekeliling'     => ['  850  ', 850.0, 'spasi dibuang'],

            // Pemisah ribuan (perilaku lama yang HARUS dipertahankan)
            'ribuan koma'             => ['1,234', 1234.0, 'koma + 3 digit = ribuan'],
            'ribuan titik'            => ['1.234', 1234.0, 'titik + 3 digit = ribuan'],
            'ribuan ganda koma'       => ['1,234,567', 1234567.0, 'beberapa pemisah = ribuan'],
            'ribuan ganda titik'      => ['1.234.567', 1234567.0, 'gaya Indonesia'],

            // INTI PERBAIKAN: koma sebagai desimal (format Indonesia)
            'desimal koma satu digit' => ['1,5', 1.5, 'KOMA DESIMAL — dulu terbaca 15'],
            'desimal koma dua digit'  => ['10,25', 10.25, 'KOMA DESIMAL — dulu terbaca 1025'],
            'desimal titik'           => ['1.5', 1.5, 'titik desimal'],

            // Campuran: pemisah TERAKHIR adalah desimal
            'indonesia penuh'         => ['1.234,56', 1234.56, 'titik ribuan + koma desimal'],
            'inggris penuh'           => ['1,234.56', 1234.56, 'koma ribuan + titik desimal'],

            // Kasus tepi
            'kosong'                  => ['', 0.0, 'string kosong = 0'],
            'null'                    => [null, 0.0, 'null = 0'],
            'negatif'                 => ['-50', -50.0, 'tanda minus dipertahankan'],
            'negatif desimal'         => ['-1,5', -1.5, 'negatif + koma desimal'],
            'bukan angka'             => ['ED menyusul', 0.0, 'teks non-angka = 0, bukan error'],
        ];
    }

    /**
     * Regresi utama: nilai desimal gaya Indonesia dulu dikali sepuluh.
     */
    public function test_indonesian_decimal_is_not_inflated_tenfold(): void
    {
        $old = (float) str_replace([',', ' '], '', '1,5');   // perilaku lama
        $new = $this->service->parseQuantity('1,5');

        $this->assertSame(15.0, $old, 'Menegaskan perilaku lama memang salah.');
        $this->assertSame(1.5, $new);
    }
}
