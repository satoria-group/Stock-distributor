<?php

namespace Database\Seeders;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the master data Satoria Group already prepared (Distributor,
 * Netsuite Item, Distributor Item sheets) from the source workbook that
 * shipped with this project. Re-running is safe (idempotent upserts).
 */
class MasterDataSeeder extends Seeder
{
    private const SOURCE_FILE = __DIR__.'/data/stock_distributor_source.xlsx';

    public function run(): void
    {
        if (! file_exists(self::SOURCE_FILE)) {
            $this->command?->warn('Source workbook tidak ditemukan, skip import master data: '.self::SOURCE_FILE);

            return;
        }

        $spreadsheet = IOFactory::load(self::SOURCE_FILE);

        $this->importDistributors($spreadsheet->getSheetByName('Distributor')?->toArray());
        $this->importNetsuiteItems($spreadsheet->getSheetByName('Netsuite Item')?->toArray());
        $this->importDistributorItems($spreadsheet->getSheetByName('Distributor Item')?->toArray());
    }

    private function importDistributors(?array $rows): void
    {
        if (! $rows) {
            return;
        }

        $seenCodes = [];

        foreach (array_slice($rows, 1) as $row) {
            $code = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[2] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            // Duplicate distributor code in the source: keep the first occurrence.
            if (isset($seenCodes[$code])) {
                continue;
            }
            $seenCodes[$code] = true;

            Distributor::updateOrCreate(
                ['distributor_code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }

        $this->command?->info('Distributor: '.count($seenCodes).' baris di-import.');
    }

    private function importNetsuiteItems(?array $rows): void
    {
        if (! $rows) {
            return;
        }

        $count = 0;

        foreach (array_slice($rows, 1) as $row) {
            $internalId = $row[0] ?? null;
            $netsuiteId = trim((string) ($row[1] ?? ''));
            $netsuiteName = trim((string) ($row[2] ?? ''));

            if ($netsuiteId === '') {
                continue;
            }

            NetsuiteItem::updateOrCreate(
                ['netsuite_id' => $netsuiteId],
                [
                    'internal_id' => $internalId !== null && $internalId !== '' ? (int) $internalId : null,
                    'netsuite_name' => $netsuiteName,
                ]
            );
            $count++;
        }

        $this->command?->info('Netsuite Item: '.$count.' baris di-import.');
    }

    private function importDistributorItems(?array $rows): void
    {
        if (! $rows) {
            return;
        }

        $distributorIdByCode = Distributor::pluck('id', 'distributor_code')->all();
        $netsuiteIdByCode = NetsuiteItem::pluck('id', 'netsuite_id')->all();

        $mapped = 0;
        $unmapped = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            $distributorCode = trim((string) ($row[1] ?? ''));
            $sourceItemId = $row[3] !== null ? trim((string) $row[3]) : null;
            $itemName = trim((string) ($row[4] ?? ''));
            $satuan = $row[5] !== null ? trim((string) $row[5]) : null;
            $netsuiteId = $row[6] !== null ? trim((string) $row[6]) : null;

            $distributorId = $distributorIdByCode[$distributorCode] ?? null;

            if ($distributorId === null || $itemName === '') {
                $skipped++;

                continue;
            }

            $netsuiteItemId = ($netsuiteId !== null && $netsuiteId !== '')
                ? ($netsuiteIdByCode[$netsuiteId] ?? null)
                : null;

            DistributorItem::updateOrCreate(
                ['distributor_id' => $distributorId, 'item_name' => $itemName],
                [
                    'source_item_id' => $sourceItemId ?: null,
                    'satuan' => $satuan ?: null,
                    'netsuite_item_id' => $netsuiteItemId,
                ]
            );

            $netsuiteItemId ? $mapped++ : $unmapped++;
        }

        $this->command?->info("Distributor Item: {$mapped} ter-mapping, {$unmapped} belum ter-mapping, {$skipped} baris dilewati (distributor tidak dikenal).");
    }
}
