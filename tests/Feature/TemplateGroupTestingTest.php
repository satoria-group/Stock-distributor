<?php

namespace Tests\Feature;

use App\Livewire\TemplateGroups\Index as TemplateGroups;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\DistributorTemplateGroup;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Dua pengaman di halaman Grup Template: uji coba pembacaan yang tidak
 * menyimpan apa pun, dan status aktif yang menyingkirkan grup dari pencocokan.
 */
class TemplateGroupTestingTest extends TestCase
{
    use DatabaseTransactions;

    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffix = strtoupper(substr(uniqid(), -6));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->syncRoles([User::ROLE_ADMIN]);

        return $user;
    }

    private function file(array $rows): \Illuminate\Http\Testing\File
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        $headers = ['PRD_UJI', 'CAB_UJI', 'BRG_UJI', 'QTY_UJI'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $h);
        }

        foreach ($rows as $n => $row) {
            foreach ($row as $i => $v) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).($n + 2), $v);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'uji_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        return UploadedFile::fake()->createWithContent('uji.xlsx', $content);
    }

    /** @return array<string, string> resep yang cocok dengan berkas di atas */
    private function columnMap(): array
    {
        return [
            'Tanggal' => ['parts' => [['type' => 'column', 'value' => 'PRD_UJI']], 'nospace' => false, 'upper' => false],
            'ID DISTRIBUTOR' => ['parts' => [['type' => 'column', 'value' => 'CAB_UJI']], 'nospace' => true, 'upper' => true],
            'Distributor Item Name' => ['parts' => [['type' => 'column', 'value' => 'BRG_UJI']], 'nospace' => false, 'upper' => false],
            'Quantity' => ['parts' => [['type' => 'column', 'value' => 'QTY_UJI']], 'nospace' => false, 'upper' => false],
            'Satuan' => ['parts' => [], 'nospace' => false, 'upper' => false],
            'ED' => ['parts' => [], 'nospace' => false, 'upper' => false],
            'Batch No' => ['parts' => [], 'nospace' => false, 'upper' => false],
        ];
    }

    public function test_uji_coba_melaporkan_hasil_tanpa_menyimpan_apa_pun(): void
    {
        $distributor = Distributor::create([
            'distributor_code' => 'UJI'.$this->suffix,
            'name' => 'Distributor Uji '.$this->suffix,
            'is_active' => true,
        ]);

        $netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-'.$this->suffix,
            'netsuite_name' => 'Produk Uji '.$this->suffix,
        ]);

        // Satu item ter-mapping, satu belum — keduanya harus terlihat di laporan.
        DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_name' => 'SUSU 400G',
            'satuan' => 'PCS',
            'netsuite_item_id' => $netsuite->id,
        ]);

        $file = $this->file([
            ['22/09/2026', 'UJI'.$this->suffix, 'SUSU 400G', 10],
            ['22/09/2026', 'UJI'.$this->suffix, 'SUSU BELUM DIPETAKAN', 5],
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(TemplateGroups::class)
            ->set('name', 'Uji '.$this->suffix)
            ->set('columnMap', $this->columnMap())
            ->set('default_batch', 'NO-BATCH')
            ->set('testFile', $file)
            ->call('runTest')
            ->assertHasNoErrors();

        $report = $component->get('testReport');

        $this->assertTrue($report['ok']);
        $this->assertSame(2, $report['total_rows']);
        $this->assertCount(1, $report['branches']);

        $branch = $report['branches'][0];
        $this->assertSame('UJI'.$this->suffix, $branch['code']);
        $this->assertTrue($branch['known']);
        $this->assertSame('2026-09-22', $branch['tanggal']);
        $this->assertSame(['SUSU BELUM DIPETAKAN'], $branch['unmapped']);

        // Inti uji coba: tidak ada jejak apa pun yang tertinggal.
        $this->assertSame(0, StockEntry::where('distributor_id', $distributor->id)->count());
        $this->assertSame(0, DistributorTemplateGroup::where('name', 'Uji '.$this->suffix)->count());
        $this->assertSame(1, DistributorItem::where('distributor_id', $distributor->id)->count());
    }

    public function test_uji_coba_menjelaskan_kenapa_berkas_tak_terbaca(): void
    {
        $file = $this->file([['22/09/2026', 'UJI'.$this->suffix, 'SUSU 400G', 10]]);

        $component = Livewire::actingAs($this->admin())
            ->test(TemplateGroups::class)
            ->set('name', 'Salah '.$this->suffix)
            // Kolom Quantity menunjuk judul yang tidak ada di berkas.
            ->set('columnMap.Quantity', ['parts' => [['type' => 'column', 'value' => 'TIDAK_ADA']], 'nospace' => false, 'upper' => false])
            ->set('testFile', $file)
            ->call('runTest');

        $report = $component->get('testReport');

        $this->assertFalse($report['ok']);
        $this->assertStringContainsString('Quantity', $report['error']);
    }

    public function test_grup_nonaktif_tidak_ikut_dicobakan(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'Nonaktif '.$this->suffix,
            'is_active' => false,
            'column_map' => ['Quantity' => 'QTY_UJI'],
        ]);

        $this->assertFalse(
            DistributorTemplateGroup::active()->where('id', $group->id)->exists(),
            'Grup nonaktif tidak boleh ikut dalam daftar kandidat pencocokan.'
        );

        // Dinyalakan lagi lewat tombol di daftar.
        Livewire::actingAs($this->admin())
            ->test(TemplateGroups::class)
            ->call('toggleActive', $group->id);

        $this->assertTrue($group->fresh()->is_active);
        $this->assertTrue(DistributorTemplateGroup::active()->where('id', $group->id)->exists());
    }
}
