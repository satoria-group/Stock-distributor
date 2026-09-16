<?php

namespace Database\Seeders;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DummyStockSeptemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseDir = base_path('dummy_stock_september_2026');
        if (! is_dir($baseDir)) {
            $this->command?->error("Folder dummy stock tidak ditemukan pada: {$baseDir}");

            return;
        }

        $admin = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        $adminId = $admin?->id;

        $files = glob("{$baseDir}/*/*.xlsx");
        if (empty($files)) {
            $this->command?->warn('Tidak ada file .xlsx di dalam folder dummy_stock_september_2026.');

            return;
        }

        $totalInserted = 0;
        $this->command?->info('Memulai import data dummy stock dari file Excel...');

        DB::beginTransaction();
        try {
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
                $data = $sheet->toArray(null, true, false, false);

                if (count($data) < 2) {
                    continue;
                }

                $header = array_map(fn ($h) => trim((string) $h), $data[0] ?? []);
                $col = array_flip($header);

                $bodyRows = array_slice($data, 1);
                $firstDataRow = $bodyRows[0] ?? null;
                if (! $firstDataRow) {
                    continue;
                }

                $distCode = trim((string) ($firstDataRow[$col['ID DISTRIBUTOR']] ?? ''));
                $dist = Distributor::where('distributor_code', $distCode)->first();
                if (! $dist) {
                    continue;
                }

                $rawTanggal = trim((string) ($firstDataRow[$col['Tanggal']] ?? ''));
                $tanggal = null;
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $rawTanggal, $m)) {
                    $tanggal = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                }

                if (! $tanggal) {
                    continue;
                }

                $items = DistributorItem::where('distributor_id', $dist->id)->get()->keyBy(fn ($i) => mb_strtolower(trim($i->item_name)));

                foreach ($bodyRows as $row) {
                    $itemName = trim((string) ($row[$col['Distributor Item Name']] ?? ''));
                    $itemKey = mb_strtolower(trim($itemName));
                    $distItem = $items->get($itemKey);
                    if (! $distItem) {
                        continue;
                    }

                    $qty = (float) str_replace([',', ' '], '', trim((string) ($row[$col['Quantity']] ?? 0)));
                    $satuan = isset($col['Satuan']) ? trim((string) ($row[$col['Satuan']] ?? '')) : $distItem->satuan;
                    $batch = isset($col['Batch No']) ? trim((string) ($row[$col['Batch No']] ?? '')) : null;

                    $rawEd = isset($col['ED']) ? trim((string) ($row[$col['ED']] ?? '')) : null;
                    $ed = null;
                    if ($rawEd && preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $rawEd, $mEd)) {
                        $ed = sprintf('%04d-%02d-%02d', $mEd[3], $mEd[2], $mEd[1]);
                    }

                    StockEntry::updateOrCreate(
                        [
                            'tanggal' => $tanggal,
                            'distributor_item_id' => $distItem->id,
                        ],
                        [
                            'distributor_id' => $dist->id,
                            'quantity' => $qty,
                            'satuan' => $satuan ?: $distItem->satuan,
                            'expired_date' => $ed,
                            'batch_no' => $batch ?: null,
                            'uploaded_by' => $adminId,
                        ]
                    );
                    $totalInserted++;
                }

                $this->command?->line("  -> Selesai diproses: {$filename} ({$distCode} - {$tanggal})");
            }

            DB::commit();
            $this->command?->info("Sukses! Sebanyak {$totalInserted} data snapshot stock berhasil disimpan ke database.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command?->error('Terjadi kesalahan saat import: '.$e->getMessage());
        }
    }
}
