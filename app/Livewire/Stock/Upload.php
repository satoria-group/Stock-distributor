<?php

namespace App\Livewire\Stock;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Layout('layouts.app', ['title' => 'Upload Stock Harian', 'subtitle' => 'Import Template harian, koreksi langsung di grid, lalu simpan sebagai snapshot stock.'])]
class Upload extends Component
{
    use WithFileUploads;

    public ?string $tanggal = null;

    public ?int $distributorId = null;

    /** @var array<int, array> current grid rows, kept in sync with AG Grid on save */
    public array $rows = [];

    public $file = null;

    public array $skippedItems = [];

    public ?int $addItemId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', StockEntry::class);
        $this->tanggal = now()->toDateString();
    }

    public function loadExisting(): void
    {
        if (! $this->tanggal || ! $this->distributorId) {
            $this->addError('load', 'Pilih tanggal dan distributor dulu.');

            return;
        }

        $existing = StockEntry::query()
            ->with('distributorItem.netsuiteItem')
            ->where('tanggal', $this->tanggal)
            ->where('distributor_id', $this->distributorId)
            ->get();

        if ($existing->isEmpty()) {
            $this->rows = [];
            session()->flash('status', 'Belum ada data stock untuk tanggal & distributor ini. Silakan import atau tambah baris manual.');

            return;
        }

        $this->rows = $existing->map(fn (StockEntry $e) => [
            'distributor_item_id' => $e->distributorItem->id,
            'item_name' => $e->distributorItem->item_name,
            'satuan' => $e->satuan,
            'quantity' => (float) $e->quantity,
            'expired_date' => optional($e->expired_date)->toDateString(),
            'batch_no' => $e->batch_no,
            'mapped' => $e->distributorItem->isMapped(),
        ])->values()->all();

        $this->dispatch('rows-loaded', rows: $this->rows);
    }

    public function setDateAndLoad(string $date): void
    {
        $this->tanggal = $date;
        $this->loadExisting();
    }

    public function importFile(): void
    {
        Gate::authorize('create', StockEntry::class);

        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        $spreadsheet = IOFactory::load($this->file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        $header = array_map(fn ($h) => trim((string) $h), $data[0] ?? []);
        $col = array_flip($header);

        $requiredCols = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity'];
        foreach ($requiredCols as $rc) {
            if (! isset($col[$rc])) {
                $this->addError('file', "Kolom '{$rc}' tidak ditemukan di sheet Template.");

                return;
            }
        }

        $bodyRows = array_slice($data, 1);
        $firstDataRow = null;
        foreach ($bodyRows as $r) {
            if (! empty($r[$col['ID DISTRIBUTOR']] ?? null)) {
                $firstDataRow = $r;
                break;
            }
        }

        if (! $firstDataRow) {
            $this->addError('file', 'File tidak berisi baris data.');

            return;
        }

        $distributorCode = trim((string) $firstDataRow[$col['ID DISTRIBUTOR']]);
        $distributor = Distributor::where('distributor_code', $distributorCode)->first();

        if (! $distributor) {
            $this->addError('file', "Distributor dengan kode '{$distributorCode}' belum ada di Master Distributor. Minta Admin menambahkan dulu.");

            return;
        }

        $rawTanggal = $firstDataRow[$col['Tanggal']];
        $tanggal = $this->parseExcelDate($rawTanggal);

        $knownItems = DistributorItem::where('distributor_id', $distributor->id)
            ->get()
            ->keyBy(fn ($i) => mb_strtolower(trim($i->item_name)));

        $rows = [];
        $skipped = [];

        foreach ($bodyRows as $r) {
            $itemName = trim((string) ($r[$col['Distributor Item Name']] ?? ''));
            if ($itemName === '') {
                continue;
            }

            $key = mb_strtolower($itemName);
            $distItem = $knownItems->get($key);

            if (! $distItem) {
                $skipped[] = $itemName;

                continue;
            }

            $qty = (float) ($r[$col['Quantity']] ?? 0);
            $satuan = isset($col['Satuan']) ? trim((string) ($r[$col['Satuan']] ?? '')) : $distItem->satuan;
            $ed = isset($col['ED']) ? $this->parseExcelDate($r[$col['ED']] ?? null) : null;
            $batch = isset($col['Batch No']) ? trim((string) ($r[$col['Batch No']] ?? '')) : null;

            $rows[$distItem->id] = [
                'distributor_item_id' => $distItem->id,
                'item_name' => $distItem->item_name,
                'satuan' => $satuan ?: $distItem->satuan,
                'quantity' => $qty,
                'expired_date' => $ed,
                'batch_no' => $batch ?: null,
                'mapped' => $distItem->isMapped(),
            ];
        }

        $this->tanggal = $tanggal ?? $this->tanggal;
        $this->distributorId = $distributor->id;
        $this->rows = array_values($rows);
        $this->skippedItems = array_values(array_unique($skipped));
        $this->file = null;

        $this->dispatch('rows-loaded', rows: $this->rows);

        session()->flash('status', 'Import selesai: '.count($this->rows)." baris dimuat ke grid untuk {$distributor->name} — {$this->tanggal}.");
    }

    public function addRow(): void
    {
        if (! $this->addItemId || ! $this->distributorId) {
            return;
        }

        $item = DistributorItem::find($this->addItemId);
        if (! $item || $item->distributor_id !== $this->distributorId) {
            return;
        }

        $already = collect($this->rows)->firstWhere('distributor_item_id', $item->id);
        if ($already) {
            return;
        }

        $this->rows[] = [
            'distributor_item_id' => $item->id,
            'item_name' => $item->item_name,
            'satuan' => $item->satuan,
            'quantity' => 0,
            'expired_date' => null,
            'batch_no' => null,
            'mapped' => $item->isMapped(),
        ];

        $this->addItemId = null;
        $this->dispatch('rows-loaded', rows: $this->rows);
    }

    public function saveRows(array $rows): void
    {
        Gate::authorize('create', StockEntry::class);

        if (! $this->tanggal || ! $this->distributorId) {
            $this->addError('save', 'Tanggal dan distributor wajib dipilih sebelum menyimpan.');

            return;
        }

        $mappedCount = 0;
        $unmappedCount = 0;

        DB::transaction(function () use ($rows, &$mappedCount, &$unmappedCount) {
            foreach ($rows as $row) {
                if (empty($row['distributor_item_id'])) {
                    continue;
                }

                $item = DistributorItem::find($row['distributor_item_id']);
                if (! $item || $item->distributor_id !== $this->distributorId) {
                    continue;
                }

                StockEntry::updateOrCreate(
                    [
                        'tanggal' => $this->tanggal,
                        'distributor_item_id' => $item->id,
                    ],
                    [
                        'distributor_id' => $this->distributorId,
                        'quantity' => (float) ($row['quantity'] ?? 0),
                        'satuan' => $row['satuan'] ?: $item->satuan,
                        'expired_date' => $row['expired_date'] ?: null,
                        'batch_no' => $row['batch_no'] ?: null,
                        'uploaded_by' => auth()->id(),
                    ]
                );

                $item->isMapped() ? $mappedCount++ : $unmappedCount++;
            }
        });

        session()->flash('status', "Tersimpan: {$mappedCount} item ter-mapping, {$unmappedCount} item belum ter-mapping (tetap tersimpan sebagai snapshot {$this->tanggal}).");
        $this->loadExisting();
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.stock.upload', [
            'distributors' => Distributor::orderBy('name')->get(),
            'availableItems' => $this->distributorId
                ? DistributorItem::where('distributor_id', $this->distributorId)
                    ->whereNotIn('id', collect($this->rows)->pluck('distributor_item_id')->all() ?: [0])
                    ->orderBy('item_name')
                    ->get()
                : collect(),
            'recentDates' => $this->distributorId
                ? StockEntry::where('distributor_id', $this->distributorId)
                    ->select('tanggal')->distinct()->orderByDesc('tanggal')->limit(10)->pluck('tanggal')
                : collect(),
        ]);
    }
}
