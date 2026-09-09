<?php

namespace App\Livewire;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Dashboard Stock Distributor', 'subtitle' => 'Ringkasan posisi stok on hand, analitik per sediaan, dan kontrol logistik'])]
class Dashboard extends Component
{
    use WithPagination;

    public string $selectedGroup = 'ALL';

    public ?int $selectedBranchId = null;

    public string $search = '';

    public string $satuanFilter = '';

    public int $perPage = 15;

    public function mount(): void
    {
        Gate::authorize('dashboard.view');
    }

    public function updatedSelectedGroup(): void
    {
        $this->selectedBranchId = null;
        $this->resetPage();
    }

    public function updatedSelectedBranchId(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSatuanFilter(): void
    {
        $this->resetPage();
    }

    public static function getDistributorGroup(?string $code): string
    {
        if (! $code) {
            return 'OTHER';
        }
        $code = strtoupper(trim($code));
        if (str_starts_with($code, 'KFTD')) {
            return 'KFTD';
        }
        if (str_starts_with($code, 'SDL')) {
            return 'SDL';
        }
        if (str_starts_with($code, 'UDC')) {
            return 'UDC';
        }
        if (str_starts_with($code, 'GMP')) {
            return 'GMP';
        }
        if (str_starts_with($code, 'MAM')) {
            return 'MAM';
        }

        return 'OTHER';
    }

    public function render()
    {
        // 1. Ambil daftar cabang yang relevan dengan grup terpilih (Case-insensitive)
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

        // 2. Ambil snapshot tanggal terbaru dari masing-masing distributor
        $latestPerDistQuery = StockEntry::query()
            ->select('distributor_id', DB::raw('MAX(tanggal) as max_tanggal'))
            ->groupBy('distributor_id');

        if ($this->selectedBranchId) {
            $latestPerDistQuery->where('distributor_id', $this->selectedBranchId);
        } elseif ($this->selectedGroup !== 'ALL') {
            $latestPerDistQuery->whereIn('distributor_id', $scopedDistributorIds ?: [0]);
        }

        $latestPerDist = $latestPerDistQuery->get();
        $latestSnapshotDate = $latestPerDist->max('max_tanggal');

        // 3. Ambil baris stok terkini berdasarkan snapshot terbaru masing-masing distributor
        $allCurrentEntries = collect();
        if ($latestPerDist->isNotEmpty()) {
            $allCurrentEntries = StockEntry::query()
                ->with(['distributor', 'distributorItem.netsuiteItem'])
                ->where(function ($query) use ($latestPerDist) {
                    foreach ($latestPerDist as $ld) {
                        $query->orWhere(function ($sub) use ($ld) {
                            $sub->where('distributor_id', $ld->distributor_id)
                                ->where('tanggal', $ld->max_tanggal);
                        });
                    }
                })
                ->get();
        }

        // 4. Hitung snapshot sebelumnya untuk komparasi Delta (Δ) tanpa N+1 query
        $previousQuantities = collect();
        if ($latestPerDist->isNotEmpty()) {
            $prevDates = StockEntry::query()
                ->select('distributor_id', DB::raw('MAX(tanggal) as prev_tanggal'))
                ->where(function ($q) use ($latestPerDist) {
                    foreach ($latestPerDist as $ld) {
                        $q->orWhere(function ($sub) use ($ld) {
                            $sub->where('distributor_id', $ld->distributor_id)
                                ->where('tanggal', '<', $ld->max_tanggal);
                        });
                    }
                })
                ->groupBy('distributor_id')
                ->get();

            if ($prevDates->isNotEmpty()) {
                $previousEntries = StockEntry::query()
                    ->where(function ($query) use ($prevDates) {
                        foreach ($prevDates as $pd) {
                            $query->orWhere(function ($sub) use ($pd) {
                                $sub->where('distributor_id', $pd->distributor_id)
                                    ->where('tanggal', $pd->prev_tanggal);
                            });
                        }
                    })
                    ->get();

                // Key by "distributor_id-distributor_item_id"
                $previousQuantities = $previousEntries->keyBy(fn ($e) => "{$e->distributor_id}-{$e->distributor_item_id}");
            }
        }

        // 5. Perhitungan KPI Satuan Farmasi & Metrik Operasional
        $kpi = [
            'total_btl' => 0,
            'total_amp' => 0,
            'total_pcs' => 0,
            'total_sku' => $allCurrentEntries->pluck('distributor_item_id')->unique()->count(),
            'total_branches' => $allCurrentEntries->pluck('distributor_id')->unique()->count(),
            'expiring_soon' => 0,
            'unmapped' => 0,
        ];

        foreach ($allCurrentEntries as $entry) {
            $sat = strtoupper(trim((string) $entry->satuan));
            $q = (float) $entry->quantity;

            if (str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL')) {
                $kpi['total_btl'] += $q;
            } elseif (str_contains($sat, 'AMP')) {
                $kpi['total_amp'] += $q;
            } else {
                $kpi['total_pcs'] += $q;
            }

            if (in_array($entry->expiryStatus(), ['critical', 'expired'])) {
                $kpi['expiring_soon']++;
            }

            if (! $entry->distributorItem?->isMapped()) {
                $kpi['unmapped']++;
            }
        }

        // 6. Data Grafik: Top 10 Produk Berdasarkan Kuantitas
        $topProductsMap = $allCurrentEntries
            ->groupBy(fn ($e) => $e->distributorItem?->item_name ?? 'Item Tidak Dikenal')
            ->map(fn ($group) => [
                'total' => $group->sum('quantity'),
                'entries' => $group,
            ])
            ->sortByDesc('total')
            ->take(10);

        $chartTopProducts = [
            'labels' => $topProductsMap->keys()->values()->all(),
            'datasets' => [],
        ];

        $isNationalSummary = ($this->selectedGroup === 'ALL' && ! $this->selectedBranchId);

        if ($isNationalSummary) {
            // Stacked Bar Chart per Distributor Group (Looker Studio Style)
            $distGroups = ['KFTD', 'SDL', 'UDC', 'GMP', 'MAM', 'OTHER'];
            $distGroupColors = [
                'KFTD' => '#3b82f6',  // Blue
                'SDL' => '#f97316',   // Orange
                'UDC' => '#a855f7',   // Purple
                'GMP' => '#84cc16',   // Lime/Green
                'MAM' => '#06b6d4',   // Cyan
                'OTHER' => '#eab308', // Amber/Yellow
            ];

            foreach ($distGroups as $dg) {
                $dataPoints = [];
                foreach ($topProductsMap as $productName => $pData) {
                    $qtyInGroup = $pData['entries']
                        ->filter(fn ($e) => self::getDistributorGroup($e->distributor?->distributor_code) === $dg)
                        ->sum('quantity');
                    $dataPoints[] = (float) $qtyInGroup;
                }

                $chartTopProducts['datasets'][] = [
                    'label' => $dg === 'OTHER' ? 'Lainnya' : $dg,
                    'data' => $dataPoints,
                    'backgroundColor' => $distGroupColors[$dg],
                    'stack' => 'stack0',
                ];
            }
        } else {
            // Single Horizontal Bar untuk distributor / cabang tertentu
            $dataPoints = [];
            foreach ($topProductsMap as $productName => $pData) {
                $dataPoints[] = (float) $pData['total'];
            }

            $labelName = $this->selectedBranchId
                ? ($availableBranches->firstWhere('id', $this->selectedBranchId)?->name ?? 'Cabang')
                : ($this->selectedGroup === 'OTHER' ? 'Distributor Lainnya' : $this->selectedGroup);

            $chartTopProducts['datasets'][] = [
                'label' => 'Total Qty (' . $labelName . ')',
                'data' => $dataPoints,
                'backgroundColor' => '#0d6d5f',
                'borderRadius' => 4,
            ];
        }

        // 7. Data Grafik: Distribusi Stok per Distributor (Donut Chart)
        $chartDonut = [
            'labels' => [],
            'data' => [],
            'colors' => [],
        ];

        if ($isNationalSummary) {
            $distGroups = ['KFTD', 'SDL', 'UDC', 'GMP', 'MAM', 'OTHER'];
            $colors = ['#3b82f6', '#f97316', '#a855f7', '#84cc16', '#06b6d4', '#eab308'];
            $totals = [];

            foreach ($distGroups as $dg) {
                $totals[$dg] = $allCurrentEntries
                    ->filter(fn ($e) => self::getDistributorGroup($e->distributor?->distributor_code) === $dg)
                    ->sum('quantity');
            }

            foreach ($distGroups as $idx => $dg) {
                if ($totals[$dg] > 0) {
                    $chartDonut['labels'][] = $dg === 'OTHER' ? 'Lainnya' : $dg;
                    $chartDonut['data'][] = (float) $totals[$dg];
                    $chartDonut['colors'][] = $colors[$idx];
                }
            }
        } else {
            // Jika memilih 1 distributor grup / cabang, tampilkan komposisi per sediaan yang ada stoknya (> 0)
            $sediaanData = [
                'Botol (Btl)' => (float) $kpi['total_btl'],
                'Ampul (Amp)' => (float) $kpi['total_amp'],
                'Pcs / Box' => (float) $kpi['total_pcs'],
            ];
            $sediaanColors = [
                'Botol (Btl)' => '#0d6d5f',
                'Ampul (Amp)' => '#06b6d4',
                'Pcs / Box' => '#f59e0b',
            ];
            foreach ($sediaanData as $sName => $sVal) {
                if ($sVal > 0) {
                    $chartDonut['labels'][] = $sName;
                    $chartDonut['data'][] = $sVal;
                    $chartDonut['colors'][] = $sediaanColors[$sName];
                }
            }
        }

        // 8. Filter Tabel Detail Stock
        $filteredEntries = $allCurrentEntries;

        if ($this->search) {
            $term = mb_strtolower(trim($this->search));
            $filteredEntries = $filteredEntries->filter(function ($e) use ($term) {
                $name = mb_strtolower($e->distributorItem?->item_name ?? '');
                $distName = mb_strtolower($e->distributor?->name ?? '');
                $batch = mb_strtolower($e->batch_no ?? '');

                return str_contains($name, $term) || str_contains($distName, $term) || str_contains($batch, $term);
            });
        }

        if ($this->satuanFilter) {
            $filteredEntries = $filteredEntries->filter(function ($e) {
                $sat = strtoupper(trim((string) $e->satuan));
                if ($this->satuanFilter === 'BTL') {
                    return str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL');
                }
                if ($this->satuanFilter === 'AMP') {
                    return str_contains($sat, 'AMP');
                }
                if ($this->satuanFilter === 'PCS') {
                    return ! str_contains($sat, 'BTL') && ! str_contains($sat, 'BOTOL') && ! str_contains($sat, 'AMP');
                }

                return true;
            });
        }

        // Map data tabel dengan komparasi Delta
        $mappedTableRows = $filteredEntries->map(function (StockEntry $entry) use ($previousQuantities) {
            $key = "{$entry->distributor_id}-{$entry->distributor_item_id}";
            $prev = $previousQuantities->get($key);
            $prevQty = $prev ? (float) $prev->quantity : null;
            $delta = $prevQty !== null ? ((float) $entry->quantity - $prevQty) : null;
            $deltaPct = ($prevQty && $prevQty != 0.0) ? round($delta / $prevQty * 100, 1) : null;

            return (object) [
                'entry' => $entry,
                'delta' => $delta,
                'delta_pct' => $deltaPct,
            ];
        })->sortBy(fn ($r) => $r->entry->distributorItem?->item_name ?? '')->values();

        // Paginate secara manual
        $page = $this->getPage();
        $totalRows = $mappedTableRows->count();
        $slice = $mappedTableRows->slice(($page - 1) * $this->perPage, $this->perPage)->values();

        $stockTablePaginated = new LengthAwarePaginator(
            $slice,
            $totalRows,
            $this->perPage,
            $page,
            ['path' => '#', 'pageName' => 'page']
        );

        // 9. Early Warnings
        $expiryAlerts = $allCurrentEntries
            ->filter(fn ($r) => $r->expired_date !== null && in_array($r->expiryStatus(), ['critical', 'warning', 'expired']))
            ->sortBy(fn ($r) => $r->expired_date)
            ->take(8)
            ->values();

        // Dispatch browser event agar chart selalu sinkron dengan data terfilter
        $this->dispatch('charts-updated', [
            'top' => $chartTopProducts,
            'donut' => $chartDonut,
        ]);

        return view('livewire.dashboard', [
            'kpi' => $kpi,
            'latestDate' => $latestSnapshotDate,
            'availableBranches' => $availableBranches,
            'stockTable' => $stockTablePaginated,
            'chartTopProducts' => $chartTopProducts,
            'chartDonut' => $chartDonut,
            'expiryAlerts' => $expiryAlerts,
            'totalDisplayRows' => $totalRows,
        ]);
    }
}
