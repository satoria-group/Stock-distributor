<?php

namespace App\Livewire\Stock;

use App\Livewire\Dashboard;
use App\Models\Distributor;
use App\Models\StockEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app', ['title' => 'Riwayat Stok Distributor', 'subtitle' => 'Log audit snapshot stok harian, penelusuran posisi historis, dan arsip upload per distributor'])]
class History extends Component
{
    use WithPagination;

    public string $startDate = '';

    public string $endDate = '';

    public string $selectedGroup = 'ALL';

    public ?int $distributorId = null;

    public string $search = '';

    public int $perPage = 15;

    public bool $showDetailModal = false;

    public ?array $selectedSnapshot = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', StockEntry::class);
    }

    public function updatedSelectedGroup(): void
    {
        $this->distributorId = null;
        $this->resetPage();
    }

    public function updatedDistributorId(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->selectedGroup = 'ALL';
        $this->distributorId = null;
        $this->search = '';
        $this->resetPage();
    }

    public function viewDetail(string $tanggal, int $distributorId): void
    {
        Gate::authorize('viewAny', StockEntry::class);

        $distributor = Distributor::find($distributorId);
        if (! $distributor) {
            return;
        }

        $entries = StockEntry::query()
            ->with(['distributorItem.netsuiteItem', 'uploader'])
            ->where('tanggal', $tanggal)
            ->where('distributor_id', $distributorId)
            ->get();

        if ($entries->isEmpty()) {
            return;
        }

        $totalBtl = 0;
        $totalAmp = 0;
        $totalPcs = 0;
        $mappedCount = 0;
        $unmappedCount = 0;
        $expiringCount = 0;

        foreach ($entries as $e) {
            $sat = strtoupper(trim((string) $e->satuan));
            $q = (float) $e->quantity;

            if (str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL')) {
                $totalBtl += $q;
            } elseif (str_contains($sat, 'AMP')) {
                $totalAmp += $q;
            } else {
                $totalPcs += $q;
            }

            if ($e->distributorItem?->isMapped()) {
                $mappedCount++;
            } else {
                $unmappedCount++;
            }

            if (in_array($e->expiryStatus(), ['critical', 'expired'])) {
                $expiringCount++;
            }
        }

        $items = $entries->map(function ($e) {
            return [
                'id' => $e->id,
                'item_name' => $e->distributorItem?->item_name ?? '—',
                'netsuite_code' => $e->distributorItem?->netsuiteItem?->netsuite_id ?? '—',
                'netsuite_name' => $e->distributorItem?->netsuiteItem?->netsuite_name ?? '—',
                'satuan' => $e->satuan,
                'quantity' => (float) $e->quantity,
                'batch_no' => $e->batch_no ?? '—',
                'expired_date' => $e->expired_date ? $e->expired_date->format('d/m/Y') : '—',
                'expiry_status' => $e->expiryStatus(),
                'is_mapped' => $e->distributorItem?->isMapped() ?? false,
            ];
        })->sortBy('item_name')->values()->all();

        $latestEntry = $entries->sortByDesc('updated_at')->first();

        $this->selectedSnapshot = [
            'tanggal' => $tanggal,
            'tanggal_formatted' => Carbon::parse($tanggal)->isoFormat('dddd, D MMMM Y'),
            'distributor_id' => $distributor->id,
            'distributor_name' => $distributor->name,
            'distributor_code' => $distributor->distributor_code,
            'group' => Dashboard::getDistributorGroup($distributor->distributor_code),
            'total_sku' => $entries->count(),
            'total_quantity' => (float) $entries->sum('quantity'),
            'total_btl' => $totalBtl,
            'total_amp' => $totalAmp,
            'total_pcs' => $totalPcs,
            'mapped_count' => $mappedCount,
            'unmapped_count' => $unmappedCount,
            'expiring_count' => $expiringCount,
            'uploader_name' => $latestEntry?->uploader?->name ?? 'Sistem / Impor',
            'updated_at' => $latestEntry?->updated_at?->format('d M Y H:i') ?? '—',
            'items' => $items,
        ];

        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedSnapshot = null;
    }

    public function exportCsv(string $tanggal, int $distributorId): StreamedResponse
    {
        Gate::authorize('viewAny', StockEntry::class);

        $distributor = Distributor::findOrFail($distributorId);
        $entries = StockEntry::query()
            ->with(['distributorItem.netsuiteItem'])
            ->where('tanggal', $tanggal)
            ->where('distributor_id', $distributorId)
            ->get()
            ->sortBy(fn ($e) => $e->distributorItem?->item_name ?? '');

        $codeSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $distributor->distributor_code);
        $filename = "snapshot_{$codeSafe}_{$tanggal}.csv";

        return response()->streamDownload(function () use ($entries, $distributor, $tanggal) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with proper accents
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Tanggal Snapshot',
                'Kode Distributor',
                'Nama Distributor',
                'Nama Item Distributor',
                'Satuan',
                'Kuantitas',
                'No Batch',
                'Expired Date',
                'Kode Netsuite',
                'Nama Item Netsuite (Satoria)',
                'Status Mapping',
            ]);

            foreach ($entries as $e) {
                $ns = $e->distributorItem?->netsuiteItem;
                fputcsv($handle, [
                    $tanggal,
                    $distributor->distributor_code,
                    $distributor->name,
                    $e->distributorItem?->item_name ?? '—',
                    $e->satuan,
                    $e->quantity,
                    $e->batch_no ?? '—',
                    $e->expired_date ? $e->expired_date->format('Y-m-d') : '—',
                    $ns?->netsuite_id ?? '—',
                    $ns?->netsuite_name ?? '—',
                    $e->distributorItem?->isMapped() ? 'TER-MAPPING' : 'BELUM TER-MAPPING',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        // 1. Cabang yang tersedia berdasarkan grup terpilih
        $groupDistributorQuery = Distributor::query()->where('is_active', true);
        if ($this->selectedGroup !== 'ALL') {
            if ($this->selectedGroup === 'GMP') {
                $groupDistributorQuery->where('distributor_code', 'ilike', 'GMP%');
            } elseif ($this->selectedGroup === 'OTHER') {
                $groupDistributorQuery->where('distributor_code', 'not ilike', 'KFTD%')
                    ->where('distributor_code', 'not ilike', 'SDL%')
                    ->where('distributor_code', 'not ilike', 'UDC%')
                    ->where('distributor_code', 'not ilike', 'GMP%')
                    ->where('distributor_code', 'not ilike', 'MAM%');
            } else {
                $groupDistributorQuery->where('distributor_code', 'ilike', "{$this->selectedGroup}%");
            }
        }
        $availableBranches = $groupDistributorQuery->orderBy('name')->get();
        $scopedDistributorIds = $availableBranches->pluck('id')->all();

        // 2. Query log riwayat snapshot (agregasi per tanggal + distributor_id)
        $snapshots = $this->applySnapshotFilters(
            StockEntry::query()
                ->select(
                    'tanggal',
                    'distributor_id',
                    DB::raw('COUNT(*) as total_sku'),
                    DB::raw('SUM(quantity) as total_quantity'),
                    // Penyunting TERAKHIR snapshot ini.
                    //
                    // Sebelumnya MAX(uploaded_by), yang keliru: itu mengambil
                    // ID user terbesar, bukan orang yang benar-benar terakhir
                    // mengubah. Semantiknya kini sama dengan viewDetail(), yang
                    // memakai entri dengan updated_at terbaru. id dipakai
                    // sebagai pemecah seri karena satu kali simpan menulis
                    // banyak baris pada detik yang sama.
                    DB::raw('(SELECT se2.uploaded_by FROM stock_entries se2
                              WHERE se2.tanggal = stock_entries.tanggal
                                AND se2.distributor_id = stock_entries.distributor_id
                              ORDER BY se2.updated_at DESC, se2.id DESC
                              LIMIT 1) as uploaded_by'),
                    DB::raw('MAX(created_at) as created_at'),
                    DB::raw('MAX(updated_at) as last_updated_at')
                )
                ->groupBy('tanggal', 'distributor_id'),
            $scopedDistributorIds
        )
            ->with(['distributor', 'uploader'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('distributor_id', 'asc')
            ->paginate($this->perPage);

        // 3. Statistik memakai filter YANG SAMA dengan tabel.
        //
        // Sebelumnya keempat angka ini diambil dari query global tanpa filter,
        // sehingga kartu di atas tabel tidak pernah cocok dengan isi tabelnya.
        // Query grouped dibungkus sebagai derived table supaya keempat agregat
        // didapat dalam satu query, bukan empat.
        $statsBase = $this->applySnapshotFilters(
            StockEntry::query()
                ->select('tanggal', 'distributor_id', DB::raw('COUNT(*) as total_sku'))
                ->groupBy('tanggal', 'distributor_id'),
            $scopedDistributorIds
        );

        $agg = DB::query()
            ->fromSub($statsBase, 't')
            ->selectRaw('COUNT(*) as snapshot_count')
            ->selectRaw('COUNT(DISTINCT distributor_id) as distributor_count')
            ->selectRaw('MAX(tanggal) as latest_tanggal')
            ->selectRaw('COALESCE(SUM(total_sku), 0) as row_count')
            ->first();

        return view('livewire.stock.history', [
            'snapshots' => $snapshots,
            'availableBranches' => $availableBranches,
            'selectedGroup' => $this->selectedGroup,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'distributorId' => $this->distributorId,
            'search' => $this->search,
            'showDetailModal' => $this->showDetailModal,
            'selectedSnapshot' => $this->selectedSnapshot,
            'stats' => [
                'total_snapshots' => (int) ($agg->snapshot_count ?? 0),
                'active_distributors' => (int) ($agg->distributor_count ?? 0),
                'latest_date' => $agg->latest_tanggal ?? null,
                'total_rows' => (int) ($agg->row_count ?? 0),
            ],
        ]);
    }

    /**
     * Filter yang WAJIB identik antara tabel snapshot dan kartu statistik.
     *
     * Diekstrak menjadi satu metode supaya keduanya tidak bisa menyimpang —
     * penyebab asli bug statistik yang mengabaikan filter.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, int>  $scopedDistributorIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applySnapshotFilters($query, array $scopedDistributorIds)
    {
        if ($this->distributorId) {
            $query->where('distributor_id', $this->distributorId);
        } elseif ($this->selectedGroup !== 'ALL') {
            $query->whereIn('distributor_id', $scopedDistributorIds ?: [0]);
        }

        if ($this->startDate) {
            $query->where('tanggal', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('tanggal', '<=', $this->endDate);
        }

        if ($this->search) {
            $term = '%'.trim($this->search).'%';

            // Aman tanpa closure pembungkus: Laravel menjalankan callback
            // whereHas lewat callScope(), yang otomatis mengelompokkan where
            // baru dalam tanda kurung sehingga korelasi tidak terlepas.
            // (Berbeda dengan when(), yang TIDAK mengelompokkan.)
            $query->whereHas('distributor', function ($q) use ($term) {
                $q->where('name', 'ilike', $term)
                    ->orWhere('distributor_code', 'ilike', $term);
            });
        }

        return $query;
    }
}
