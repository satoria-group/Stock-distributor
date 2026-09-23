<?php

namespace Tests\Feature;

use App\Livewire\Stock\Upload;
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
 * Satu berkas UDC/KFTD bisa memuat beberapa cabang sekaligus.
 *
 * Grid halaman Upload tetap melayani satu distributor, jadi cabang dikerjakan
 * bergiliran: berkas dipecah jadi antrian, tiap cabang dimuat, disimpan, lalu
 * berpindah sendiri ke cabang berikutnya.
 */
class StockUploadMultiBranchTest extends TestCase
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

    /** Berkas bergaya UDC: tanggal terpecah tiga kolom, kode = "UDC" + nama cabang. */
    private function udcFile(array $branches): \Illuminate\Http\Testing\File
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        $headers = ['TGL_UJI', 'BLN_UJI', 'THN_UJI', 'CUST_UJI', 'ITEM_UJI', 'QTY_UJI', 'UOM_UJI', 'ED_UJI', 'BATCH_UJI'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $h);
        }

        $rowNo = 2;
        foreach ($branches as $branch => $items) {
            foreach ($items as $itemName => $qty) {
                $values = ['22', '9', '2026', $branch, $itemName, $qty, 'PCS', '31/12/2027', 'B-'.$rowNo];
                foreach ($values as $i => $v) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$rowNo, $v);
                }
                $rowNo++;
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'udc_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        return UploadedFile::fake()->createWithContent('udc.xlsx', $content);
    }

    /** @return array{0: DistributorTemplateGroup, 1: array<string, Distributor>} */
    private function seedUdcGroup(array $branchNames): array
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'UDC '.$this->suffix,
            'column_map' => [
                'Tanggal' => ['parts' => [['column' => 'TGL_UJI'], ['column' => 'BLN_UJI'], ['column' => 'THN_UJI']]],
                'ID DISTRIBUTOR' => [
                    'parts' => [['text' => 'UDC'.$this->suffix], ['column' => 'CUST_UJI']],
                    'nospace' => true,
                    'upper' => true,
                ],
                'Distributor Item Name' => 'ITEM_UJI',
                'Quantity' => 'QTY_UJI',
                'Satuan' => 'UOM_UJI',
                'ED' => 'ED_UJI',
                'Batch No' => 'BATCH_UJI',
            ],
        ]);

        $netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-'.$this->suffix,
            'netsuite_name' => 'Produk Uji '.$this->suffix,
        ]);

        $distributors = [];
        foreach ($branchNames as $branch) {
            $distributor = Distributor::create([
                'distributor_code' => 'UDC'.$this->suffix.strtoupper($branch),
                'name' => 'UDC '.$branch,
                'is_active' => true,
                'template_group_id' => $group->id,
            ]);

            DistributorItem::create([
                'distributor_id' => $distributor->id,
                'item_name' => 'SUSU 400G',
                'satuan' => 'PCS',
                'netsuite_item_id' => $netsuite->id,
            ]);

            $distributors[$branch] = $distributor;
        }

        return [$group, $distributors];
    }

    public function test_berkas_dua_cabang_menjadi_antrian_dan_tersimpan_bergiliran(): void
    {
        [, $distributors] = $this->seedUdcGroup(['BANDUNG', 'MEDAN']);

        $file = $this->udcFile([
            'BANDUNG' => ['SUSU 400G' => 120],
            'MEDAN' => ['SUSU 400G' => 85],
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(Upload::class)
            ->set('file', $file)
            ->call('importFile')
            ->assertHasNoErrors();

        // Cabang pertama tampil di grid, cabang kedua menunggu di antrian.
        $this->assertCount(2, $component->get('importQueue'));
        $this->assertSame(0, $component->get('queueIndex'));
        $this->assertSame($distributors['BANDUNG']->id, $component->get('distributorId'));
        $this->assertSame('2026-09-22', $component->get('tanggal'));

        $component->call('saveRows', $component->get('rows'));

        // Simpan cabang pertama → grid otomatis berpindah ke cabang kedua.
        $this->assertSame(1, $component->get('queueIndex'));
        $this->assertSame($distributors['MEDAN']->id, $component->get('distributorId'));

        $component->call('saveRows', $component->get('rows'));

        // Cabang terakhir tersimpan → antrian dibersihkan.
        $this->assertSame([], $component->get('importQueue'));

        $this->assertSame(120.0, (float) StockEntry::where('distributor_id', $distributors['BANDUNG']->id)
            ->where('tanggal', '2026-09-22')->sum('quantity'));
        $this->assertSame(85.0, (float) StockEntry::where('distributor_id', $distributors['MEDAN']->id)
            ->where('tanggal', '2026-09-22')->sum('quantity'));
    }

    public function test_satu_cabang_belum_terdaftar_menolak_seluruh_berkas(): void
    {
        [, $distributors] = $this->seedUdcGroup(['BANDUNG']);

        $file = $this->udcFile([
            'BANDUNG' => ['SUSU 400G' => 120],
            'SEMARANG' => ['SUSU 400G' => 40],
        ]);

        Livewire::actingAs($this->admin())
            ->test(Upload::class)
            ->set('file', $file)
            ->call('importFile')
            ->assertHasErrors('file');

        // Tidak ada cabang yang masuk separuh: BANDUNG pun tidak tersimpan.
        $this->assertSame(0, StockEntry::where('distributor_id', $distributors['BANDUNG']->id)
            ->where('tanggal', '2026-09-22')->count());
    }

    public function test_cabang_bisa_dilewati_tanpa_disimpan(): void
    {
        [, $distributors] = $this->seedUdcGroup(['BANDUNG', 'MEDAN']);

        $file = $this->udcFile([
            'BANDUNG' => ['SUSU 400G' => 120],
            'MEDAN' => ['SUSU 400G' => 85],
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(Upload::class)
            ->set('file', $file)
            ->call('importFile')
            ->call('skipQueueItem');

        $this->assertSame($distributors['MEDAN']->id, $component->get('distributorId'));
        $this->assertSame(0, StockEntry::where('distributor_id', $distributors['BANDUNG']->id)
            ->where('tanggal', '2026-09-22')->count());
    }

    public function test_bisa_kembali_ke_cabang_sebelumnya(): void
    {
        [, $distributors] = $this->seedUdcGroup(['BANDUNG', 'MEDAN']);

        $file = $this->udcFile([
            'BANDUNG' => ['SUSU 400G' => 120],
            'MEDAN' => ['SUSU 400G' => 85],
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(Upload::class)
            ->set('file', $file)
            ->call('importFile')
            ->call('skipQueueItem');

        $this->assertSame(1, $component->get('queueIndex'));

        $component->call('previousQueueItem');

        // Kembali ke cabang pertama memuat ulang barisnya dari hasil
        // pembacaan berkas, bukan mengosongkan grid.
        $this->assertSame(0, $component->get('queueIndex'));
        $this->assertSame($distributors['BANDUNG']->id, $component->get('distributorId'));
        $this->assertNotEmpty($component->get('rows'));

        // Lompat langsung ke cabang mana pun lewat titik progres.
        $component->call('goToQueueItem', 1);
        $this->assertSame($distributors['MEDAN']->id, $component->get('distributorId'));

        // Indeks di luar antrian diabaikan, bukan membuat grid kosong.
        $component->call('goToQueueItem', 9);
        $this->assertSame(1, $component->get('queueIndex'));
    }
}
