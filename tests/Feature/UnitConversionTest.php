<?php

namespace Tests\Feature;

use App\Models\UnitConversion;
use App\Services\StockImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UnitConversionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        UnitConversion::query()->delete();
        UnitConversion::create(['from_unit' => 'BOX', 'to_unit' => 'PCS', 'factor' => 50]);
    }

    private function row(float $qty, ?string $satuan, string $batch = 'B1'): array
    {
        return ['item_id' => 1, 'item_name' => 'SUSU', 'qty' => $qty, 'satuan' => $satuan, 'ed' => null, 'batch' => $batch, 'excel_row' => 2];
    }

    public function test_box_dikonversi_ke_pcs_dan_nilai_asli_tersimpan(): void
    {
        $g = app(StockImportService::class)->groupRowsByItemAndBatch([$this->row(10, 'box')]);
        $row = array_values($g['rows'])[0];

        $this->assertEquals(500, $row['quantity']);
        $this->assertSame('PCS', $row['satuan']);
        $this->assertEquals(10, $row['quantity_asli']);
        $this->assertSame('box', $row['satuan_asli']);
    }

    public function test_satuan_tanpa_aturan_disimpan_apa_adanya(): void
    {
        $g = app(StockImportService::class)->groupRowsByItemAndBatch([$this->row(7, 'BTL')]);
        $row = array_values($g['rows'])[0];

        $this->assertEquals(7, $row['quantity']);
        $this->assertSame('BTL', $row['satuan']);
        $this->assertNull($row['quantity_asli']);
    }

    public function test_box_dan_pcs_pada_batch_sama_dijumlah_dalam_pcs(): void
    {
        $g = app(StockImportService::class)->groupRowsByItemAndBatch([
            $this->row(2, 'BOX'),
            $this->row(30, 'PCS'),
        ]);
        $row = array_values($g['rows'])[0];

        $this->assertEquals(130, $row['quantity']);
        // Campuran dua satuan berkas tidak punya satu nilai asli.
        $this->assertNull($row['quantity_asli']);
        $this->assertNull($row['satuan_asli']);
    }
}
