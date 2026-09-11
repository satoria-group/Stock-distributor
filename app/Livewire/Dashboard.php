<?php

namespace App\Livewire;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app', ['title' => 'Dashboard Stock Distributor', 'subtitle' => 'Ringkasan posisi stok on hand, analitik per sediaan, dan kontrol logistik'])]
class Dashboard extends Component
{
    use WithPagination;

    /**
     * Presentasi per tier kedaluwarsa. Ambang batasnya sendiri milik
     * StockEntry::expiryStatus(); di sini hanya label, warna, dan tindakan.
     * Label harus tetap sejalan dengan StockEntry::CRITICAL_DAYS / WARNING_DAYS.
     */
    private const FEFO_TIER_META = [
        'expired' => [
            'label' => 'Sudah Expired',
            'badge' => 'bg-red-100 text-red-800 border-red-300',
            'action' => 'Karantina & Siapkan Retur',
        ],
        'critical' => [
            'label' => 'Kritis (< 3 Bulan)',
            'badge' => 'bg-rose-100 text-rose-800 border-rose-300',
            'action' => 'Prioritas Pengeluaran (FEFO) Segera',
        ],
        'warning' => [
            'label' => 'Waspada (3 - 6 Bulan)',
            'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
            'action' => 'Monitoring & Akselerasi Penjualan',
        ],
        'safe' => [
            'label' => 'Aman (> 6 Bulan)',
            'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'action' => 'Stok Terkendali Sesuai Rencana',
        ],
    ];

    // Main Navigation Tabs: 'stock' (Posisi Stok), 'expiry' (FEFO), 'compliance' (Kepatuhan Upload)
    public string $activeTab = 'stock';

    public string $selectedGroup = 'ALL';

    public ?int $selectedBranchId = null;

    public string $search = '';

    public string $satuanFilter = '';

    public int $perPage = 15;

    public string $sortBy = 'item_name';

    public string $sortDir = 'asc';

    // Tab 2: Monitoring Kedaluwarsa (FEFO)
    public string $expiryRiskFilter = 'all'; // 'all', 'critical', 'warning', 'safe', 'expired'

    public string $expirySearch = '';

    // Tab 3: Kepatuhan Upload Cabang
    public string $complianceDate = '';

    public string $complianceStatus = 'all'; // 'all', 'submitted', 'missing'

    public string $complianceSearch = '';

    public function mount(): void
    {
        Gate::authorize('dashboard.view');
    }

    public function switchTab(string $tab, ?string $subFilter = null): void
    {
        $this->activeTab = $tab;
        if ($tab === 'expiry' && $subFilter) {
            $this->expiryRiskFilter = $subFilter;
        }
        if ($tab === 'compliance' && $subFilter) {
            $this->complianceStatus = $subFilter;
        }
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatedExpiryRiskFilter(): void
    {
        $this->resetPage();
    }

    public function updatedExpirySearch(): void
    {
        $this->resetPage();
    }

    public function updatedComplianceDate(): void
    {
        $this->resetPage();
    }

    public function updatedComplianceStatus(): void
    {
        $this->resetPage();
    }

    public function updatedComplianceSearch(): void
    {
        $this->resetPage();
    }

    public function setSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->selectedBranchId = null;
        $this->satuanFilter = '';
        $this->search = '';
        $this->sortBy = 'item_name';
        $this->sortDir = 'asc';
        $this->expiryRiskFilter = 'all';
        $this->expirySearch = '';
        $this->complianceStatus = 'all';
        $this->complianceSearch = '';
        $this->resetPage();
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

    /**
     * Cabang aktif sesuai grup terpilih. Diekstrak agar render() dan export
     * memakai definisi scope yang sama persis.
     */
    private function scopedBranchQuery()
    {
        $query = Distributor::query()->where('is_active', true);

        if ($this->selectedGroup === 'ALL') {
            return $query;
        }

        if ($this->selectedGroup === 'OTHER') {
            foreach (['KFTD', 'SDL', 'UDC', 'GMP', 'MAM'] as $prefix) {
                $query->where('distributor_code', 'not ilike', $prefix.'%');
            }

            return $query;
        }

        return $query->where('distributor_code', 'ilike', "{$this->selectedGroup}%");
    }

    /**
     * @param  array<int, int>  $scopedDistributorIds
     */
    private function latestSnapshotPerDistributor(array $scopedDistributorIds): Collection
    {
        $query = StockEntry::query()
            ->select('distributor_id', DB::raw('MAX(tanggal) as max_tanggal'))
            ->groupBy('distributor_id');

        if ($this->selectedBranchId) {
            $query->where('distributor_id', $this->selectedBranchId);
        } elseif ($this->selectedGroup !== 'ALL') {
            $query->whereIn('distributor_id', $scopedDistributorIds ?: [0]);
        }

        return $query->get();
    }

    private function entriesForLatestSnapshots(Collection $latestPerDist): Collection
    {
        if ($latestPerDist->isEmpty()) {
            return collect();
        }

        return StockEntry::query()
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

    /**
     * Petakan entri stok menjadi baris FEFO bertier. Ambang batas sepenuhnya
     * milik StockEntry::expiryStatus(); di sini hanya presentasi.
     */
    private function fefoRowsFrom(Collection $entries): Collection
    {
        return $entries
            ->filter(fn ($r) => $r->expired_date !== null)
            ->map(function ($e) {
                $tier = $e->expiryStatus();
                $meta = self::FEFO_TIER_META[$tier] ?? self::FEFO_TIER_META['safe'];

                return (object) [
                    'entry' => $e,
                    'days' => $e->daysToExpiry(),
                    'tier' => $tier,
                    'label' => $meta['label'],
                    'badgeClass' => $meta['badge'],
                    'action' => $meta['action'],
                ];
            });
    }

    /**
     * Filter tier + pencarian pada tab FEFO.
     *
     * Dipakai bersama oleh tabel dan export CSV. Sebelumnya export hanya
     * menerapkan selectedBranchId sehingga pengguna mengunduh jauh lebih banyak
     * data daripada yang tampil di layar; menyatukannya di sini membuat
     * penyimpangan itu mustahil terulang.
     */
    private function applyFefoFilters(Collection $rows): Collection
    {
        if ($this->expiryRiskFilter !== 'all') {
            $rows = $rows->where('tier', $this->expiryRiskFilter);
        }

        if (trim($this->expirySearch) !== '') {
            $term = mb_strtolower(trim($this->expirySearch));
            $rows = $rows->filter(function ($r) use ($term) {
                foreach ([
                    $r->entry->distributorItem?->item_name,
                    $r->entry->distributorItem?->netsuiteItem?->netsuite_name,
                    $r->entry->distributor?->name,
                    $r->entry->distributor?->distributor_code,
                    $r->entry->batch_no,
                ] as $field) {
                    if ($field !== null && str_contains(mb_strtolower($field), $term)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $rows->sortBy('days')->values();
    }

    public function render()
    {
        // 1. Ambil daftar cabang yang relevan dengan grup terpilih (Case-insensitive)
        $availableBranches = $this->scopedBranchQuery()->orderBy('name')->get();
        $scopedDistributorIds = $availableBranches->pluck('id')->all();

        // 2. Ambil snapshot tanggal terbaru dari masing-masing distributor
        $latestPerDist = $this->latestSnapshotPerDistributor($scopedDistributorIds);
        $latestSnapshotDate = $latestPerDist->max('max_tanggal');

        // 3. Ambil baris stok terkini berdasarkan snapshot terbaru masing-masing distributor
        $allCurrentEntries = $this->entriesForLatestSnapshots($latestPerDist);

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

        // 6. Data Grafik: Top 10 Produk Berdasarkan Kuantitas (Terkonsolidasi Master Netsuite)
        $topProductsMap = $allCurrentEntries
            ->groupBy(function ($e) {
                $ns = $e->distributorItem?->netsuiteItem;
                if ($ns && ! empty($ns->netsuite_name)) {
                    return trim($ns->netsuite_name);
                }

                return trim((string) ($e->distributorItem?->item_name ?? 'Item Tidak Dikenal'));
            })
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
                $nsName = mb_strtolower($e->distributorItem?->netsuiteItem?->netsuite_name ?? '');
                $nsCode = mb_strtolower($e->distributorItem?->netsuiteItem?->netsuite_id ?? '');
                $distName = mb_strtolower($e->distributor?->name ?? '');
                $batch = mb_strtolower($e->batch_no ?? '');

                return str_contains($name, $term)
                    || str_contains($nsName, $term)
                    || str_contains($nsCode, $term)
                    || str_contains($distName, $term)
                    || str_contains($batch, $term);
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
        });

        // 9. Interactive Column Sorting
        $isDesc = $this->sortDir === 'desc';
        $mappedTableRows = $mappedTableRows->sort(function ($a, $b) use ($isDesc) {
            switch ($this->sortBy) {
                case 'quantity':
                    $valA = (float) $a->entry->quantity;
                    $valB = (float) $b->entry->quantity;
                    break;
                case 'satuan':
                    $valA = strtolower((string) ($a->entry->satuan ?? ''));
                    $valB = strtolower((string) ($b->entry->satuan ?? ''));
                    break;
                case 'distributor':
                    $valA = strtolower((string) ($a->entry->distributor?->name ?? ''));
                    $valB = strtolower((string) ($b->entry->distributor?->name ?? ''));
                    break;
                case 'delta':
                    $valA = $a->delta_pct !== null ? (float) $a->delta_pct : ($isDesc ? -9999999 : 9999999);
                    $valB = $b->delta_pct !== null ? (float) $b->delta_pct : ($isDesc ? -9999999 : 9999999);
                    break;
                case 'expired_date':
                    $valA = $a->entry->expired_date ? $a->entry->expired_date->timestamp : ($isDesc ? 0 : PHP_INT_MAX);
                    $valB = $b->entry->expired_date ? $b->entry->expired_date->timestamp : ($isDesc ? 0 : PHP_INT_MAX);
                    break;
                case 'batch_no':
                    $valA = strtolower((string) ($a->entry->batch_no ?? ''));
                    $valB = strtolower((string) ($b->entry->batch_no ?? ''));
                    break;
                case 'item_name':
                default:
                    $valA = strtolower((string) ($a->entry->distributorItem?->item_name ?? ''));
                    $valB = strtolower((string) ($b->entry->distributorItem?->item_name ?? ''));
                    break;
            }

            if ($valA == $valB) {
                return 0;
            }

            return ($valA < $valB xor $isDesc) ? -1 : 1;
        })->values();

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

        // 10. Tab 2: Monitoring Kedaluwarsa (FEFO Watchlist)
        $fefoAllRows = $this->fefoRowsFrom($allCurrentEntries);

        $fefoSummary = [
            'total' => $fefoAllRows->count(),
            'expired' => $fefoAllRows->where('tier', 'expired')->count(),
            'critical' => $fefoAllRows->where('tier', 'critical')->count(),
            'warning' => $fefoAllRows->where('tier', 'warning')->count(),
            'safe' => $fefoAllRows->where('tier', 'safe')->count(),
            'total_qty_at_risk' => (float) $fefoAllRows->whereIn('tier', ['expired', 'critical', 'warning'])->sum(fn ($r) => (float) $r->entry->quantity),
        ];

        $fefoFiltered = $this->applyFefoFilters($fefoAllRows);

        $fefoTotal = $fefoFiltered->count();
        $fefoSlice = $fefoFiltered->slice(($page - 1) * $this->perPage, $this->perPage)->values();
        $fefoTablePaginated = new LengthAwarePaginator(
            $fefoSlice,
            $fefoTotal,
            $this->perPage,
            $page,
            ['path' => '#', 'pageName' => 'page']
        );

        // 11. Tab 3: Kepatuhan Upload Cabang (Compliance Tracker)
        $targetComplianceDate = $this->complianceDate ?: ($latestSnapshotDate ?: Carbon::today()->toDateString());

        // Jumlah baris & kuantitas HANYA untuk tanggal kepatuhan yang dipilih.
        //
        // Sebelumnya keduanya adalah agregat sepanjang masa (COUNT/SUM tanpa
        // batas tanggal) namun ditampilkan bersebelahan dengan "upload
        // terakhir", sehingga terbaca seolah angka hari itu.
        //
        // Keberadaan distributor di koleksi ini sekaligus menandakan ia sudah
        // setor pada tanggal tersebut, jadi query $submittedDistributorIds yang
        // terpisah tidak lagi diperlukan.
        $onTargetDate = StockEntry::query()
            ->select('distributor_id', DB::raw('COUNT(*) as total_rows'), DB::raw('SUM(quantity) as total_qty'))
            ->where('tanggal', $targetComplianceDate)
            ->groupBy('distributor_id')
            ->get()
            ->keyBy('distributor_id');

        // "Upload terakhir" memang bersifat sepanjang masa — itulah maknanya.
        $lastUploadDates = StockEntry::query()
            ->select('distributor_id', DB::raw('MAX(tanggal) as last_date'))
            ->groupBy('distributor_id')
            ->get()
            ->keyBy('distributor_id');

        $complianceAllRows = $availableBranches->map(function ($b) use ($onTargetDate, $lastUploadDates, $targetComplianceDate) {
            $today = $onTargetDate->get($b->id);
            $hasSubmitted = $today !== null;
            $lastDate = $lastUploadDates->get($b->id)?->last_date;

            $daysOverdue = null;
            if (! $hasSubmitted && $lastDate) {
                // diffInDays bertanda: positif hanya bila upload terakhir
                // memang SEBELUM tanggal target. Tanpa argumen false, memilih
                // tanggal kepatuhan yang lebih awal dari upload terakhir
                // menghasilkan "terlambat N hari" yang tidak masuk akal.
                $diff = (int) Carbon::parse($lastDate)->diffInDays(Carbon::parse($targetComplianceDate), false);
                $daysOverdue = $diff > 0 ? $diff : null;
            }

            return (object) [
                'distributor' => $b,
                'hasSubmitted' => $hasSubmitted,
                'lastDate' => $lastDate,
                'daysOverdue' => $daysOverdue,
                'totalRows' => (int) ($today?->total_rows ?? 0),
                'totalQty' => (float) ($today?->total_qty ?? 0),
            ];
        });

        $complianceSummary = [
            'total_branches' => $complianceAllRows->count(),
            'total_submitted' => $complianceAllRows->where('hasSubmitted', true)->count(),
            'total_missing' => $complianceAllRows->where('hasSubmitted', false)->count(),
            'compliance_rate' => $complianceAllRows->count() > 0 ? round(($complianceAllRows->where('hasSubmitted', true)->count() / $complianceAllRows->count()) * 100, 1) : 0,
            'target_date' => $targetComplianceDate,
        ];

        $complianceFiltered = $complianceAllRows;
        if ($this->complianceStatus === 'submitted') {
            $complianceFiltered = $complianceFiltered->where('hasSubmitted', true);
        } elseif ($this->complianceStatus === 'missing') {
            $complianceFiltered = $complianceFiltered->where('hasSubmitted', false);
        }

        if (trim($this->complianceSearch) !== '') {
            $term = mb_strtolower(trim($this->complianceSearch));
            $complianceFiltered = $complianceFiltered->filter(function ($r) use ($term) {
                $name = mb_strtolower($r->distributor->name ?? '');
                $code = mb_strtolower($r->distributor->distributor_code ?? '');

                return str_contains($name, $term) || str_contains($code, $term);
            });
        }

        $complianceFiltered = $complianceFiltered->sort(function ($a, $b) {
            if ($a->hasSubmitted !== $b->hasSubmitted) {
                return $a->hasSubmitted ? 1 : -1;
            }

            return strcmp($a->distributor->distributor_code, $b->distributor->distributor_code);
        })->values();

        $complianceTotal = $complianceFiltered->count();
        $complianceSlice = $complianceFiltered->slice(($page - 1) * $this->perPage, $this->perPage)->values();
        $complianceTablePaginated = new LengthAwarePaginator(
            $complianceSlice,
            $complianceTotal,
            $this->perPage,
            $page,
            ['path' => '#', 'pageName' => 'page']
        );

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
            'activeTab' => $this->activeTab,
            'selectedGroup' => $this->selectedGroup,
            'selectedBranchId' => $this->selectedBranchId,
            'search' => $this->search,
            'satuanFilter' => $this->satuanFilter,
            'sortBy' => $this->sortBy,
            'sortDir' => $this->sortDir,
            'perPage' => $this->perPage,
            'expiryRiskFilter' => $this->expiryRiskFilter,
            'expirySearch' => $this->expirySearch,
            'complianceDate' => $this->complianceDate,
            'complianceStatus' => $this->complianceStatus,
            'complianceSearch' => $this->complianceSearch,
            'fefoTable' => $fefoTablePaginated,
            'fefoSummary' => $fefoSummary,
            'complianceTable' => $complianceTablePaginated,
            'complianceSummary' => $complianceSummary,
        ]);
    }

    public function exportNearEdCsv(): StreamedResponse
    {
        Gate::authorize('dashboard.view');

        // Memakai pipeline yang SAMA dengan tabel FEFO di render(), sehingga isi
        // CSV persis mencerminkan apa yang dilihat pengguna: scope grup/cabang,
        // filter tier, dan kata pencarian.
        $scopedDistributorIds = $this->scopedBranchQuery()->pluck('id')->all();
        $latestPerDist = $this->latestSnapshotPerDistributor($scopedDistributorIds);

        $mapped = $this->applyFefoFilters(
            $this->fefoRowsFrom($this->entriesForLatestSnapshots($latestPerDist))
        );

        // Cakupan filter ikut di nama file agar penerima tahu ini data tersaring.
        $scopeLabel = $this->selectedBranchId
            ? ($this->scopedBranchQuery()->find($this->selectedBranchId)?->distributor_code ?? 'cabang')
            : $this->selectedGroup;
        $scopeLabel = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $scopeLabel);

        if ($this->expiryRiskFilter !== 'all') {
            $scopeLabel .= '_'.$this->expiryRiskFilter;
        }

        $filename = 'laporan_near_ed_'.$scopeLabel.'_'.Carbon::today()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($mapped) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Grup',
                'Kode Distributor',
                'Nama Cabang / Distributor',
                'Nama Item Distributor',
                'Kode Netsuite',
                'Nama Produk Netsuite',
                'Nomor Batch',
                'Tanggal Kedaluwarsa',
                'Sisa Hari',
                'Status Kedaluwarsa',
                'Kuantitas',
                'Satuan',
            ]);

            foreach ($mapped as $r) {
                $e = $r->entry;
                $ns = $e->distributorItem?->netsuiteItem;
                fputcsv($handle, [
                    self::getDistributorGroup($e->distributor?->distributor_code),
                    $e->distributor?->distributor_code,
                    $e->distributor?->name,
                    $e->distributorItem?->item_name ?? '—',
                    $ns?->netsuite_id ?? '—',
                    $ns?->netsuite_name ?? 'Belum Mapping',
                    $e->batch_no ?? '—',
                    $e->expired_date ? $e->expired_date->format('Y-m-d') : '—',
                    $r->days,
                    $r->label,
                    $e->quantity,
                    $e->satuan,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
