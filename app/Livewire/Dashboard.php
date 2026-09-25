<?php

namespace App\Livewire;

use App\Models\Distributor;
use App\Models\DistributorGroup;
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

    public string $batchFilter = '';

    public int $perPage = 15;

    public string $sortBy = 'item_name';

    public string $sortDir = 'asc';

    // Tab 2: Monitoring Kedaluwarsa (FEFO)
    public ?int $fefoBranchId = null;

    public string $fefoSatuanFilter = '';

    public string $expiryRiskFilter = 'all'; // 'all', 'critical', 'warning', 'safe', 'expired'

    public string $expirySearch = '';

    public string $fefoSortBy = 'days';

    public string $fefoSortDir = 'asc';

    public string $fefoChartUnit = 'ALL'; // 'ALL', 'BTL', 'AMP', 'PCS'

    // Donut Chart Metric (Nilai Finansial Rp vs Volume Fisik)
    public string $donutMetric = 'value'; // 'value', 'qty'

    // Trend Stock On Hand Line Chart
    public string $trendUnit = 'ALL'; // 'ALL', 'BTL', 'AMP', 'PCS'

    public int $trendPeriod = 30; // 7, 30, 90

    // Tab 3: Kepatuhan Upload Cabang
    public string $complianceDate = '';

    public string $complianceStatus = 'all'; // 'all', 'submitted', 'missing'

    public string $complianceSearch = '';

    public int $complianceTrendPeriod = 14; // 7, 14, 30

    // Tab 4: Stok Macet & Slow-Moving (Dead Stock Alert)
    public int $stagnantPeriod = 30; // 14, 30, 60

    public string $stagnantRiskFilter = 'all'; // 'all', 'dead', 'slow', 'critical_ed'

    public ?int $stagnantBranchId = null;

    public string $stagnantSatuanFilter = '';

    public string $stagnantSearch = '';

    public string $stagnantSortBy = 'days_stagnant'; // 'days_stagnant', 'quantity', 'item_name', 'expired_date', 'turnover_pct', 'distributor'

    public string $stagnantSortDir = 'desc';

    public function mount(): void
    {
        Gate::authorize('dashboard.view');
    }

    public function switchTab(string $tab, ?string $subFilter = null): void
    {
        if (in_array($tab, ['stock', 'expiry', 'compliance', 'stagnant'], true)) {
            $this->activeTab = $tab;
            if ($tab === 'expiry' && $subFilter !== null) {
                $this->expiryRiskFilter = $subFilter;
            }
            if ($tab === 'compliance' && $subFilter !== null) {
                $this->complianceStatus = $subFilter;
            }
            if ($tab === 'stagnant' && $subFilter !== null) {
                $this->stagnantRiskFilter = $subFilter;
            }
            $this->resetPage();
        }
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatedFefoBranchId(): void
    {
        $this->resetPage();
    }

    public function updatedFefoSatuanFilter(): void
    {
        $this->fefoChartUnit = in_array($this->fefoSatuanFilter, ['BTL', 'AMP', 'PCS']) ? $this->fefoSatuanFilter : 'ALL';
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

    public function updatedFefoSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedFefoSortDir(): void
    {
        $this->resetPage();
    }

    public function updatedComplianceDate(): void
    {
        if ($this->complianceDate && preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', trim($this->complianceDate), $m)) {
            $this->complianceDate = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
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

    public function setFefoSort(string $column): void
    {
        if ($this->fefoSortBy === $column) {
            $this->fefoSortDir = $this->fefoSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->fefoSortBy = $column;
            $this->fefoSortDir = 'asc';
        }
        $this->resetPage();
    }

    public function setTrendUnit(string $unit): void
    {
        if (in_array($unit, ['ALL', 'BTL', 'AMP', 'PCS'])) {
            $this->trendUnit = $unit;
        }
    }

    public function setTrendPeriod(int $period): void
    {
        if (in_array($period, [7, 30, 90])) {
            $this->trendPeriod = $period;
        }
    }

    public function setFefoChartUnit(string $unit): void
    {
        if (in_array($unit, ['ALL', 'BTL', 'AMP', 'PCS'])) {
            $this->fefoChartUnit = $unit;
            $this->fefoSatuanFilter = $unit === 'ALL' ? '' : $unit;
        }
    }

    public function setBatchFilter(string $batch): void
    {
        $batch = trim($batch);
        if ($this->batchFilter === $batch) {
            $this->batchFilter = '';
        } else {
            $this->batchFilter = $batch;
        }
        $this->resetPage();
    }

    public function clearBatchFilter(): void
    {
        $this->batchFilter = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->resetStockFilters();
    }

    public function resetStockFilters(): void
    {
        $this->selectedBranchId = null;
        $this->satuanFilter = '';
        $this->trendUnit = 'ALL';
        $this->search = '';
        $this->batchFilter = '';
        $this->sortBy = 'item_name';
        $this->sortDir = 'asc';
        $this->resetPage();
    }

    public function resetFefoFilters(): void
    {
        $this->fefoBranchId = null;
        $this->fefoSatuanFilter = '';
        $this->fefoChartUnit = 'ALL';
        $this->expiryRiskFilter = 'all';
        $this->expirySearch = '';
        $this->fefoSortBy = 'days';
        $this->fefoSortDir = 'asc';
        $this->resetPage();
    }

    public function resetComplianceFilters(): void
    {
        $this->complianceStatus = 'all';
        $this->complianceSearch = '';
        $this->complianceDate = '';
        $this->complianceTrendPeriod = 14;
        $this->resetPage();
    }

    public function setComplianceTrendPeriod(int $period): void
    {
        if (in_array($period, [7, 14, 30], true)) {
            $this->complianceTrendPeriod = $period;
        }
    }

    public function setDonutMetric(string $metric): void
    {
        if (in_array($metric, ['value', 'qty'])) {
            $this->donutMetric = $metric;
        }
    }

    public function updatedStagnantPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedStagnantRiskFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStagnantBranchId(): void
    {
        $this->resetPage();
    }

    public function updatedStagnantSatuanFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStagnantSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStagnantSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedStagnantSortDir(): void
    {
        $this->resetPage();
    }

    public function setStagnantSort(string $column): void
    {
        if ($this->stagnantSortBy === $column) {
            $this->stagnantSortDir = $this->stagnantSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->stagnantSortBy = $column;
            $this->stagnantSortDir = in_array($column, ['days_stagnant', 'quantity', 'turnover_pct']) ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function resetStagnantFilters(): void
    {
        $this->stagnantPeriod = 30;
        $this->stagnantRiskFilter = 'all';
        $this->stagnantBranchId = null;
        $this->stagnantSatuanFilter = '';
        $this->stagnantSearch = '';
        $this->stagnantSortBy = 'days_stagnant';
        $this->stagnantSortDir = 'desc';
        $this->resetPage();
    }

    public function updatedSelectedGroup(): void
    {
        $this->selectedBranchId = null;
        $this->fefoBranchId = null;
        $this->stagnantBranchId = null;
        $this->resetPage();
    }

    /**
     * Ganti grup distributor.
     *
     * Dibuat sebagai metode tersendiri (bukan $set) supaya bisa dijadikan
     * wire:target oleh indikator loading pada grafik: wire:target mencocokkan
     * nama METODE yang dipanggil, sedangkan $set('selectedGroup', ...) tercatat
     * sebagai pemanggilan "$set" — yang juga dipakai 20+ aksi lain di halaman
     * ini, sehingga tidak bisa dibedakan.
     *
     * Logika reset disamakan dengan updatedSelectedGroup(), karena hook
     * updated* TIDAK ikut terpanggil saat properti diubah dari dalam metode.
     */
    public function setGroup(string $group): void
    {
        if ($this->selectedGroup === $group) {
            return;
        }

        $this->selectedGroup = $group;
        $this->selectedBranchId = null;
        $this->fefoBranchId = null;
        $this->stagnantBranchId = null;
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
        $this->trendUnit = in_array($this->satuanFilter, ['BTL', 'AMP', 'PCS']) ? $this->satuanFilter : 'ALL';
        $this->resetPage();
    }

    /**
     * Grup usaha aktif, dibaca sekali per permintaan.
     *
     * Dulu pengelompokan ditebak dari awalan kode distributor lewat daftar yang
     * ditulis tetap di sini — akibatnya 202 dari 289 distributor jatuh ke
     * keranjang 'OTHER', termasuk grup besar seperti RNI, PPI, dan TSJ yang
     * sama sekali tidak terlihat di dashboard. Sekarang pengelompokan dibaca
     * dari tabel: menambah grup tidak lagi berarti mengubah kode.
     *
     * @return \Illuminate\Support\Collection<string, DistributorGroup>  nama grup => grup
     */
    public static function distributorGroups(): \Illuminate\Support\Collection
    {
        // Memo SEPANJANG SATU PERMINTAAN: satu render dashboard memanggilnya
        // ribuan kali dari dalam filter koleksi.
        //
        // Disimpan di container, bukan variabel static: static bertahan selama
        // proses hidup, sehingga grup yang baru dibuat tidak akan terlihat pada
        // proses berumur panjang — pekerja antrean, Octane, dan rangkaian test
        // yang berbagi satu proses.
        $key = 'dashboard.distributor-groups';

        if (app()->bound($key)) {
            return app($key);
        }

        $groups = DistributorGroup::active()->ordered()->get()->keyBy('name');
        app()->instance($key, $groups);

        return $groups;
    }

    /**
     * Kunci grup yang dipakai filter dan chart: nama grup, atau 'OTHER' untuk
     * distributor yang memang belum/tidak bergrup.
     *
     * @return array<int, string>
     */
    public static function groupKeys(): array
    {
        return array_merge(self::distributorGroups()->keys()->all(), ['OTHER']);
    }

    /** Nama grup untuk satu distributor. */
    public static function getDistributorGroup(?Distributor $distributor): string
    {
        $name = $distributor?->group?->name;

        // Grup yang dinonaktifkan tidak lagi muncul sebagai kategori sendiri;
        // anggotanya ikut 'Lainnya' supaya angkanya tidak hilang diam-diam.
        return ($name && self::distributorGroups()->has($name)) ? $name : 'OTHER';
    }

    /** Label yang ditampilkan untuk sebuah kunci grup. */
    public static function getDistributorGroupLabel(string $group): string
    {
        return $group === 'OTHER' ? DistributorGroup::UNGROUPED_LABEL : $group;
    }

    /**
     * Warna seragam & konsisten antar semua chart (Stacked Bar, Donut, List).
     */
    public static function getDistributorGroupColor(string $group): string
    {
        return self::distributorGroups()->get($group)?->colorOrDefault()
            ?? DistributorGroup::UNGROUPED_COLOR;
    }

    /**
     * Format nama cabang agar bersih, ringkas, dan mudah dibaca tanpa prefix PT/Cabang berulang.
     */
    public static function formatBranchDisplayName(?string $name, ?string $code = ''): string
    {
        if (! $name) {
            return $code ?: '-';
        }

        $patterns = [
            '/^PT\.?\s*KIMIA\s+FARMA\s+TRADING\s*&\s*DISTRIBUTION\s*(CABANG\s*)?[-:]?\s*/i' => 'KFTD ',
            '/^PT\.?\s*SATORIA\s+DISTRIBUSI\s+LESTARI\s*[-:]?\s*(CABANG\s*)?/i' => 'SDL ',
            '/^PT\.?\s*INDOFARMA\s+GLOBAL\s+MEDIKA\s*[-:]?\s*(CABANG\s*)?/i' => 'IGM ',
            '/^PT\.?\s*GLOBAL\s+MITRA\s+PRIMA\s*[-:]?\s*(CABANG\s*)?/i' => 'GMP ',
            '/^PT\.?\s*MITRA\s+ALFAR\s+MEDICALINDO\s*[-:]?\s*(CABANG\s*)?/i' => 'MAM ',
            '/^PT\.?\s*MENJANGAN\s+ENAM\s*[-:]?\s*(CABANG\s*)?/i' => 'MAM ',
            '/^PT\.?\s*/i' => '',
        ];

        $cleaned = preg_replace(array_keys($patterns), array_values($patterns), trim($name));
        $cleaned = preg_replace('/\s+/', ' ', (string) $cleaned);

        return trim($cleaned) ?: $name;
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

        // 'OTHER' = belum bergrup, termasuk anggota grup yang dinonaktifkan.
        if ($this->selectedGroup === 'OTHER') {
            return $query->where(function ($q) {
                $q->whereNull('distributor_group_id')
                    ->orWhereNotIn('distributor_group_id', self::distributorGroups()->pluck('id')->all() ?: [0]);
            });
        }

        $groupId = self::distributorGroups()->get($this->selectedGroup)?->id;

        // Grup yang tidak dikenal (mis. dihapus setelah filter tersimpan di
        // URL) tidak boleh diam-diam berubah makna jadi "semua distributor".
        return $query->where('distributor_group_id', $groupId ?? 0);
    }

    /**
     * @param  array<int, int>  $scopedDistributorIds
     */
    private function latestSnapshotPerDistributor(array $scopedDistributorIds): Collection
    {
        // Scope diterapkan untuk SEMUA grup, termasuk 'ALL'.
        //
        // Sebelumnya 'ALL' melewati filter ini sepenuhnya, sehingga KPI dan
        // total stok ikut menghitung distributor NON-AKTIF — padahal
        // $scopedDistributorIds (dan daftar cabang di sampingnya) berasal dari
        // scopedBranchQuery() yang memfilter is_active = true. Akibatnya
        // menjumlahkan seluruh tab grup satu per satu tidak pernah sama dengan
        // angka di tab ALL.
        $ids = array_values(array_map('intval', $scopedDistributorIds ?: [0]));

        // Bentuk LATERAL, bukan GROUP BY.
        //
        // "MAX(tanggal) GROUP BY distributor_id" memaksa PostgreSQL membaca
        // SELURUH index stock_entries hanya untuk mencari ~300 nilai maksimum —
        // PostgreSQL tidak punya skip scan. Biayanya tumbuh linear terhadap
        // besar tabel: diukur 0,8 ms pada 3 ribu baris, tapi 125 ms pada 700
        // ribu baris. Query ini jalan di SEMUA tab, jadi ia rem utama dashboard
        // seiring data menumpuk.
        //
        // Versi LATERAL melakukan satu lompatan index per distributor (index
        // (distributor_id, tanggal) yang sudah ada, dibaca mundur). Pada 700
        // ribu baris: 131,9 ms -> 0,9 ms.
        if (DB::getDriverName() === 'pgsql') {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            return collect(DB::select(
                "SELECT d.id AS distributor_id, m.max_tanggal
                 FROM distributors d
                 CROSS JOIN LATERAL (
                     SELECT MAX(se.tanggal) AS max_tanggal
                     FROM stock_entries se
                     WHERE se.distributor_id = d.id
                 ) m
                 WHERE d.id IN ({$placeholders})
                   AND m.max_tanggal IS NOT NULL",
                $ids
            ));
        }

        return StockEntry::query()
            ->select('distributor_id', DB::raw('MAX(tanggal) as max_tanggal'))
            ->whereIn('distributor_id', $ids)
            ->groupBy('distributor_id')
            ->get();
    }

    /**
     * Tanggal snapshot TERAKHIR SEBELUM snapshot terkini, per distributor.
     * Dipakai untuk komparasi Delta (Δ) pada tab Posisi Stok.
     */
    private function previousSnapshotDates(Collection $latestPerDist): Collection
    {
        if ($latestPerDist->isEmpty()) {
            return collect();
        }

        if (DB::getDriverName() !== 'pgsql') {
            return StockEntry::query()
                ->select('distributor_id', DB::raw('MAX(tanggal) as prev_tanggal'))
                ->where(function ($q) use ($latestPerDist) {
                    foreach ($latestPerDist as $ld) {
                        $q->orWhere(fn ($sub) => $sub->where('distributor_id', $ld->distributor_id)
                            ->where('tanggal', '<', $ld->max_tanggal));
                    }
                })
                ->groupBy('distributor_id')
                ->get();
        }

        // Pasangan (distributor, tanggal terkini) dikirim sebagai tabel VALUES,
        // lalu tiap barisnya di-LATERAL-kan ke satu lompatan index.
        $values = [];
        $bindings = [];
        foreach ($latestPerDist as $ld) {
            $values[] = '(?::bigint, ?::date)';
            $bindings[] = (int) $ld->distributor_id;
            $bindings[] = (string) $ld->max_tanggal;
        }

        return collect(DB::select(
            'SELECT p.distributor_id, m.prev_tanggal
             FROM (VALUES '.implode(',', $values).') AS p(distributor_id, max_tanggal)
             CROSS JOIN LATERAL (
                 SELECT MAX(se.tanggal) AS prev_tanggal
                 FROM stock_entries se
                 WHERE se.distributor_id = p.distributor_id
                   AND se.tanggal < p.max_tanggal
             ) m
             WHERE m.prev_tanggal IS NOT NULL',
            $bindings
        ));
    }

    private function entriesForLatestSnapshots(Collection $latestPerDist): Collection
    {
        if ($latestPerDist->isEmpty()) {
            return collect();
        }

        return StockEntry::query()
            ->with(['distributor.group', 'distributorItem.netsuiteItem.dplPrice'])
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
                $unitPrice = (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0);
                $totalValue = (float) $e->quantity * $unitPrice;

                return (object) [
                    'entry' => $e,
                    'days' => $e->daysToExpiry(),
                    'tier' => $tier,
                    'label' => $meta['label'],
                    'badgeClass' => $meta['badge'],
                    'action' => $meta['action'],
                    'unit_price' => $unitPrice,
                    'total_value' => $totalValue,
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

        $isDesc = $this->fefoSortDir === 'desc';

        return $rows->sort(function ($a, $b) use ($isDesc) {
            switch ($this->fefoSortBy) {
                case 'total_value':
                    $valA = (float) $a->total_value;
                    $valB = (float) $b->total_value;
                    break;
                case 'item_name':
                    $valA = strtolower($a->entry->displayName());
                    $valB = strtolower($b->entry->displayName());
                    break;
                case 'distributor':
                    $valA = strtolower((string) ($a->entry->distributor?->name ?? ''));
                    $valB = strtolower((string) ($b->entry->distributor?->name ?? ''));
                    break;
                case 'batch_no':
                    $valA = strtolower((string) ($a->entry->batch_no ?? ''));
                    $valB = strtolower((string) ($b->entry->batch_no ?? ''));
                    break;
                case 'expired_date':
                    $valA = $a->entry->expired_date ? $a->entry->expired_date->timestamp : ($isDesc ? 0 : PHP_INT_MAX);
                    $valB = $b->entry->expired_date ? $b->entry->expired_date->timestamp : ($isDesc ? 0 : PHP_INT_MAX);
                    break;
                case 'quantity':
                    $valA = (float) $a->entry->quantity;
                    $valB = (float) $b->entry->quantity;
                    break;
                case 'tier':
                    $tierOrder = ['expired' => 1, 'critical' => 2, 'warning' => 3, 'safe' => 4];
                    $valA = $tierOrder[$a->tier] ?? 99;
                    $valB = $tierOrder[$b->tier] ?? 99;
                    break;
                case 'days':
                default:
                    $valA = (int) $a->days;
                    $valB = (int) $b->days;
                    break;
            }

            if ($valA == $valB) {
                return $a->days <=> $b->days;
            }

            return ($valA < $valB xor $isDesc) ? -1 : 1;
        })->values();
    }

    /**
     * Hitung pergerakan posisi stok fisik harian (Trend Stock On Hand).
     * Berbasis snapshot harian, terpisah per satuan (tidak dicampur),
     * dan mengabaikan missing dates (return null agar tidak turun ke 0).
     */
    public function calculateStockTrend(array $scopedDistributorIds, ?string $latestSnapshotDate): array
    {
        // 1. Tanggal Acuan (berdasarkan latest snapshot aktif atau hari ini)
        $endDate = $latestSnapshotDate ?: Carbon::today()->toDateString();
        $startDate = Carbon::parse($endDate)->subDays($this->trendPeriod - 1)->toDateString();

        // 2. Query agregasi langsung ke database
        $trendQuery = StockEntry::query()
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($this->selectedBranchId) {
            $trendQuery->where('distributor_id', $this->selectedBranchId);
        } else {
            $trendQuery->whereIn('distributor_id', $scopedDistributorIds ?: [0]);
        }

        if ($this->batchFilter !== '') {
            $trendQuery->where('batch_no', $this->batchFilter);
        }

        // 3. Filter Satuan (ALL, BTL, AMP, PCS) sesuai filter utama tabel ($satuanFilter)
        if ($this->satuanFilter !== '') {
            $unit = in_array($this->satuanFilter, ['BTL', 'AMP', 'PCS']) ? $this->satuanFilter : 'ALL';
        } else {
            $unit = in_array($this->trendUnit, ['ALL', 'BTL', 'AMP', 'PCS']) ? $this->trendUnit : 'ALL';
        }
        $this->trendUnit = $unit;

        // Satuan yang sama dengan yang tampil di tabel (StockEntry::displayUnit()):
        // Satuan Default NetSuite, bila kosong satuan baris stok.
        $unitSql = "UPPER(COALESCE(NULLIF((SELECT n.default_satuan FROM distributor_items di JOIN netsuite_items n ON n.id = di.netsuite_item_id WHERE di.id = stock_entries.distributor_item_id), ''), stock_entries.satuan, ''))";

        if ($unit === 'ALL') {
            $unitLabel = 'Semua Satuan';
        } elseif ($unit === 'BTL') {
            $trendQuery->where(function ($q) use ($unitSql) {
                $q->whereRaw("{$unitSql} LIKE ?", ['%BTL%'])
                  ->orWhereRaw("{$unitSql} LIKE ?", ['%BOTOL%']);
            });
            $unitLabel = 'Botol (BTL)';
        } elseif ($unit === 'AMP') {
            $trendQuery->where(function ($q) use ($unitSql) {
                $q->whereRaw("{$unitSql} LIKE ?", ['%AMP%']);
            });
            $unitLabel = 'Ampul (AMP)';
        } else {
            $trendQuery->where(function ($q) use ($unitSql) {
                $q->whereRaw("{$unitSql} NOT LIKE ?", ['%BTL%'])
                  ->whereRaw("{$unitSql} NOT LIKE ?", ['%BOTOL%'])
                  ->whereRaw("{$unitSql} NOT LIKE ?", ['%AMP%']);
            });
            $unitLabel = 'Pcs / Box (PCS)';
        }

        $dailySums = $trendQuery
            ->select('tanggal', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('total_qty', 'tanggal')
            ->mapWithKeys(fn ($qty, $date) => [Carbon::parse($date)->toDateString() => (float) $qty])
            ->all();

        // 4. Bangun timeline kalender harian dalam rentang periode
        $curr = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $labels = [];
        $fullDates = [];
        $dataPoints = [];
        $activeDays = 0;
        $firstQty = null;
        $latestQty = null;

        while ($curr->lte($end)) {
            $dateStr = $curr->toDateString();
            $labels[] = $curr->translatedFormat('d M');
            $fullDates[] = $curr->translatedFormat('d F Y');

            if (isset($dailySums[$dateStr])) {
                $qty = (float) $dailySums[$dateStr];
                $dataPoints[] = $qty;
                $activeDays++;
                if ($firstQty === null) {
                    $firstQty = $qty;
                }
                $latestQty = $qty;
            } else {
                // Missing date: kembalikan null untuk jeda grafik line (bukan 0)
                $dataPoints[] = null;
            }

            $curr->addDay();
        }

        // 5. Hitung Delta & Perubahan Persentase
        $delta = null;
        $deltaPct = null;
        if ($firstQty !== null && $latestQty !== null) {
            $delta = $latestQty - $firstQty;
            if ($firstQty > 0) {
                $deltaPct = round(($delta / $firstQty) * 100, 1);
            }
        }

        return [
            'labels' => $labels,
            'full_dates' => $fullDates,
            'data' => $dataPoints,
            'unit' => $this->trendUnit,
            'unit_label' => $unitLabel,
            'period' => $this->trendPeriod,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_date_formatted' => Carbon::parse($startDate)->translatedFormat('d M Y'),
            'end_date_formatted' => Carbon::parse($endDate)->translatedFormat('d M Y'),
            'active_days' => $activeDays,
            'total_period_days' => $this->trendPeriod,
            'first_qty' => $firstQty,
            'latest_qty' => $latestQty,
            'delta' => $delta,
            'delta_pct' => $deltaPct,
        ];
    }

    /**
     * Hitung tren kepatuhan upload laporan stok cabang harian (Daily Compliance Tracker).
     * Menghasilkan histori jumlah cabang yang sudah lapor vs belum lapor serta persentase kepatuhan harian.
     */
    public function calculateComplianceTrend(array $scopedDistributorIds, ?string $targetComplianceDate): array
    {
        $totalBranches = count($scopedDistributorIds);
        if ($totalBranches === 0) {
            return [
                'labels' => [],
                'full_dates' => [],
                'submitted' => [],
                'missing' => [],
                'rates' => [],
                'total_branches' => 0,
                'avg_rate' => 0.0,
                'period' => $this->complianceTrendPeriod,
                'start_date' => '',
                'end_date' => '',
                'start_date_formatted' => '-',
                'end_date_formatted' => '-',
                'has_data' => false,
            ];
        }

        $endDate = $targetComplianceDate ?: Carbon::today()->toDateString();
        $startDate = Carbon::parse($endDate)->subDays($this->complianceTrendPeriod - 1)->toDateString();

        $submittedByDate = StockEntry::query()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('distributor_id', $scopedDistributorIds)
            ->select('tanggal', DB::raw('COUNT(DISTINCT distributor_id) as submitted_count'))
            ->groupBy('tanggal')
            ->pluck('submitted_count', 'tanggal')
            ->mapWithKeys(fn ($cnt, $d) => [Carbon::parse($d)->toDateString() => (int) $cnt])
            ->all();

        $curr = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $labels = [];
        $fullDates = [];
        $submitted = [];
        $missing = [];
        $rates = [];
        $totalRateSum = 0.0;
        $hasAnyUpload = false;

        while ($curr->lte($end)) {
            $dateStr = $curr->toDateString();
            $labels[] = $curr->translatedFormat('d M');
            $fullDates[] = $curr->translatedFormat('l, d F Y');

            $subCount = $submittedByDate[$dateStr] ?? 0;
            if ($subCount > 0) {
                $hasAnyUpload = true;
            }
            $missCount = max(0, $totalBranches - $subCount);
            $rate = $totalBranches > 0 ? round(($subCount / $totalBranches) * 100, 1) : 0.0;

            $submitted[] = $subCount;
            $missing[] = $missCount;
            $rates[] = $rate;
            $totalRateSum += $rate;

            $curr->addDay();
        }

        $avgRate = count($rates) > 0 ? round($totalRateSum / count($rates), 1) : 0.0;

        return [
            'labels' => $labels,
            'full_dates' => $fullDates,
            'submitted' => $submitted,
            'missing' => $missing,
            'rates' => $rates,
            'total_branches' => $totalBranches,
            'avg_rate' => $avgRate,
            'period' => $this->complianceTrendPeriod,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_date_formatted' => Carbon::parse($startDate)->translatedFormat('d M Y'),
            'end_date_formatted' => Carbon::parse($endDate)->translatedFormat('d M Y'),
            'has_data' => $hasAnyUpload,
        ];
    }

    /**
     * Hitung horizon umur kedaluwarsa makro (Macro Expiry Horizon Breakdown).
     * Membagi stok fisik (BTL / AMP / PCS) ke dalam 5 zona farmasi:
     * < 1 bln (expired), 1-3 bln (kritis), 3-6 bln (waspada), 6-12 bln (perhatian), > 12 bln (aman).
     */
    public function calculateFefoHorizon(Collection $allCurrentEntries, array $scopedDistributorIds): array
    {
        if ($this->fefoSatuanFilter !== '') {
            $unit = in_array($this->fefoSatuanFilter, ['BTL', 'AMP', 'PCS']) ? $this->fefoSatuanFilter : 'ALL';
        } else {
            $unit = in_array($this->fefoChartUnit, ['ALL', 'BTL', 'AMP', 'PCS']) ? $this->fefoChartUnit : 'ALL';
        }
        $this->fefoChartUnit = $unit;
        $unitLabel = match ($unit) {
            'BTL' => 'Botol (BTL)',
            'AMP' => 'Ampul (AMP)',
            'PCS' => 'Pcs / Box (PCS)',
            default => 'Semua Satuan',
        };

        // Filter entri stok berdasarkan satuan dan ketersediaan expired_date
        $matchingEntries = $allCurrentEntries->filter(function ($entry) use ($unit) {
            if ($entry->expired_date === null) {
                return false;
            }
            if ($unit === 'ALL') {
                return true;
            }
            $sat = strtoupper(trim((string) $entry->displayUnit()));
            if ($unit === 'BTL') {
                return str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL');
            } elseif ($unit === 'AMP') {
                return str_contains($sat, 'AMP');
            } else {
                return ! str_contains($sat, 'BTL') && ! str_contains($sat, 'BOTOL') && ! str_contains($sat, 'AMP');
            }
        });

        // Filter jika ada cabang spesifik yang dipilih di Tab FEFO atau filter utama
        $effectiveBranchId = $this->fefoBranchId ?: $this->selectedBranchId;
        if ($effectiveBranchId) {
            $matchingEntries = $matchingEntries->filter(fn ($e) => (int) $e->distributor_id === (int) $effectiveBranchId);
        }

        // Definisi 5 Tier Horizon Kedaluwarsa Standar Supply Chain Farmasi
        $tierDefs = [
            'expired' => [
                'label' => '< 1 Bulan / Expired',
                'color' => '#dc2626',
            ],
            'critical' => [
                'label' => '1 - 3 Bulan (< 90 Hari)',
                'color' => '#e11d48',
            ],
            'warning' => [
                'label' => '3 - 6 Bulan (90 - 180 Hari)',
                'color' => '#f59e0b',
            ],
            'caution' => [
                'label' => '6 - 12 Bulan (180 - 365 Hari)',
                'color' => '#06b6d4',
            ],
            'safe' => [
                'label' => '> 12 Bulan (> 365 Hari)',
                'color' => '#10b981',
            ],
        ];

        // 1. Hitung Agregat Nasional (Total Qty, Total Batch, dan % Tiap Tier)
        $national = [];
        $totalQty = 0.0;
        $totalBatches = 0;

        foreach ($tierDefs as $k => $def) {
            $national[$k] = [
                'id' => $k,
                'label' => $def['label'],
                'color' => $def['color'],
                'qty' => 0.0,
                'pct' => 0.0,
                'batches' => 0,
            ];
        }

        foreach ($matchingEntries as $entry) {
            $q = (float) $entry->quantity;
            $days = $entry->daysToExpiry();

            if ($days < 30) {
                $tier = 'expired';
            } elseif ($days <= StockEntry::CRITICAL_DAYS) {
                $tier = 'critical';
            } elseif ($days <= StockEntry::WARNING_DAYS) {
                $tier = 'warning';
            } elseif ($days <= 365) {
                $tier = 'caution';
            } else {
                $tier = 'safe';
            }

            $national[$tier]['qty'] += $q;
            $national[$tier]['batches']++;
            $totalQty += $q;
            $totalBatches++;
        }

        foreach ($national as $k => &$item) {
            $item['pct'] = $totalQty > 0 ? round(($item['qty'] / $totalQty) * 100, 1) : 0.0;
        }
        unset($item);

        // 2. Tentukan Labels & Baris Horizon (Distributor Group vs Branch)
        if ($effectiveBranchId) {
            $branch = Distributor::find($effectiveBranchId);
            $labels = [$branch?->name ?? 'Cabang '.$effectiveBranchId];
            $entityMapping = [
                $labels[0] => $matchingEntries,
            ];
        } elseif ($this->selectedGroup !== 'ALL') {
            // Tampilkan per Cabang dalam grup yang dipilih
            $groupedByBranch = $matchingEntries->groupBy(fn ($e) => $e->distributor?->name ?? 'Lainnya');
            $sortedBranches = $groupedByBranch->sortByDesc(fn ($entries) => $entries->sum('quantity'))->take(12);
            $labels = $sortedBranches->keys()->values()->all();
            $entityMapping = [];
            foreach ($labels as $lbl) {
                $entityMapping[$lbl] = $groupedByBranch->get($lbl, collect());
            }
            if (empty($labels)) {
                $labels = [self::getDistributorGroupLabel($this->selectedGroup)];
                $entityMapping[$labels[0]] = collect();
            }
        } else {
            // Tampilan Nasional: 6 Grup Distributor
            $labels = self::groupKeys();
            $entityMapping = [];
            foreach ($labels as $grp) {
                $entityMapping[$grp] = $matchingEntries->filter(function ($e) use ($grp) {
                    return self::getDistributorGroup($e->distributor) === $grp;
                });
            }
        }

        // 3. Bangun 5 Datasets Bertumpuk (Stacked Datasets) untuk Chart.js
        $datasets = [];
        foreach ($tierDefs as $tierKey => $def) {
            $tierDataPoints = [];
            foreach ($labels as $lbl) {
                $entriesForEntity = $entityMapping[$lbl] ?? collect();
                $sumTier = 0.0;
                foreach ($entriesForEntity as $e) {
                    $days = $e->daysToExpiry();
                    $matchTier = match (true) {
                        $days < 30 => 'expired',
                        $days <= StockEntry::CRITICAL_DAYS => 'critical',
                        $days <= StockEntry::WARNING_DAYS => 'warning',
                        $days <= 365 => 'caution',
                        default => 'safe',
                    };
                    if ($matchTier === $tierKey) {
                        $sumTier += (float) $e->quantity;
                    }
                }
                $tierDataPoints[] = round($sumTier, 2);
            }

            $datasets[] = [
                'label' => $def['label'],
                'data' => $tierDataPoints,
                'backgroundColor' => $def['color'],
                'borderRadius' => 3,
                'stack' => 'horizon',
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'unit' => $unit,
            'unit_label' => $unitLabel,
            'total_qty' => $totalQty,
            'total_batches' => $totalBatches,
            'national' => $national,
            'has_data' => $totalQty > 0,
        ];
    }

    /**
     * Hitung indikator stok macet (dead stock) dan pergerakan lambat (slow-moving)
     * berdasarkan perbandingan kuantitas antar snapshot dalam jendela hari evaluasi.
     */
    public function calculateStagnantStock(Collection $latestEntries, array $scopedDistributorIds, ?string $latestSnapshotDate): Collection
    {
        $branchSnapshotDate = null;
        if ($this->stagnantBranchId) {
            $maxDate = StockEntry::where('distributor_id', $this->stagnantBranchId)->max('tanggal');
            if ($maxDate) {
                $branchSnapshotDate = Carbon::parse($maxDate)->toDateString();
            }
        }
        $endDate = $branchSnapshotDate ?: ($latestSnapshotDate ?: Carbon::today()->toDateString());
        $startDate = Carbon::parse($endDate)->subDays($this->stagnantPeriod - 1)->toDateString();

        // Analisis stagnan sengaja bekerja pada level ITEM per cabang, bukan per
        // batch: yang ditanyakan adalah "apakah produk ini bergerak?", dan batch
        // baru datang-pergi secara wajar tanpa berarti stoknya mandek.
        //
        // Karena stok kini dicatat per batch, beberapa baris batch dijumlahkan
        // dulu menjadi satu angka per (cabang, item, tanggal) — sehingga makna
        // perhitungan ini tetap sama seperti sebelum pemecahan batch.
        $activeEntries = $latestEntries
            ->filter(fn ($e) => (float) $e->quantity > 0)
            ->groupBy(fn ($e) => "{$e->distributor_id}-{$e->distributor_item_id}")
            ->map(function ($group) {
                $first = $group->first();
                $agg = clone $first;
                $agg->quantity = $group->sum(fn ($e) => (float) $e->quantity);

                return $agg;
            })
            ->values();

        if ($activeEntries->isEmpty()) {
            return collect();
        }

        // Ambil riwayat snapshot dalam jendela evaluasi, dijumlahkan per
        // (cabang, item, tanggal) supaya satu tanggal tetap menghasilkan satu
        // angka meski itemnya punya beberapa batch.
        $history = StockEntry::query()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('distributor_id', $scopedDistributorIds ?: [0])
            ->select('distributor_id', 'distributor_item_id', 'tanggal', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('distributor_id', 'distributor_item_id', 'tanggal')
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy(fn ($r) => "{$r->distributor_id}-{$r->distributor_item_id}");

        $rows = collect();

        foreach ($activeEntries as $entry) {
            $key = "{$entry->distributor_id}-{$entry->distributor_item_id}";
            $snapshots = $history->get($key, collect());

            $qLatest = (float) $entry->quantity;
            if ($snapshots->isEmpty()) {
                $qFirst = $qLatest;
                $firstDate = $entry->tanggal ? $entry->tanggal->toDateString() : $endDate;
                $snapshotsCount = 1;
            } else {
                $firstSnapshot = $snapshots->first();
                $qFirst = (float) $firstSnapshot->quantity;
                $firstDate = $firstSnapshot->tanggal ? $firstSnapshot->tanggal->toDateString() : $endDate;
                $snapshotsCount = $snapshots->count();
            }

            $totalOutflow = 0.0;
            $prevQ = null;
            $lastOutflowDate = null;

            foreach ($snapshots as $s) {
                $q = (float) $s->quantity;
                if ($prevQ !== null && $q < $prevQ) {
                    $outflow = $prevQ - $q;
                    $totalOutflow += $outflow;
                    $lastOutflowDate = $s->tanggal ? $s->tanggal->toDateString() : null;
                }
                $prevQ = $q;
            }

            $daysObserved = (int) Carbon::parse($firstDate)->diffInDays(Carbon::parse($endDate));

            if ($lastOutflowDate) {
                $daysStagnant = (int) Carbon::parse($lastOutflowDate)->diffInDays(Carbon::parse($endDate));
            } else {
                $daysStagnant = max(1, $daysObserved);
            }

            $turnoverPct = $qFirst > 0 ? round(($totalOutflow / $qFirst) * 100, 1) : 0.0;

            // Klasifikasi:
            // 1. Dead Stock: Tidak ada penurunan stok sama sekali dan qLatest >= qFirst
            // 2. Slow Moving: Ada penurunan stok tapi perputaran < 10% dalam periode
            // 3. Normal: Perputaran >= 10%
            if ($totalOutflow <= 0 && $qLatest >= $qFirst) {
                $status = 'dead_stock';
            } elseif ($totalOutflow > 0 && $turnoverPct < 10.0) {
                $status = 'slow_moving';
            } else {
                $status = 'normal';
            }

            // Abaikan item yang pergerakannya normal
            if ($status === 'normal') {
                continue;
            }

            $isNearEd = in_array($entry->expiryStatus(), ['critical', 'warning', 'expired'])
                || ($entry->daysToExpiry() !== null && $entry->daysToExpiry() <= StockEntry::WARNING_DAYS);

            if ($status === 'dead_stock' && $isNearEd) {
                $actionText = 'Prioritas Retur / Penjualan Cepat';
                $actionColor = 'rose';
            } elseif ($status === 'dead_stock') {
                $actionText = 'Relokasi Antar Cabang';
                $actionColor = 'amber';
            } elseif ($status === 'slow_moving' && $isNearEd) {
                $actionText = 'Push Sales / Evaluasi ED';
                $actionColor = 'orange';
            } else {
                $actionText = 'Evaluasi Kuota Restock';
                $actionColor = 'sky';
            }

            $unitPrice = (float) ($entry->distributorItem?->netsuiteItem?->unit_price ?? 0.0);
            $lockedCapital = $qLatest * $unitPrice;

            $rows->push((object) [
                'entry' => $entry,
                'distributor' => $entry->distributor,
                'distributorItem' => $entry->distributorItem,
                'qLatest' => $qLatest,
                'qFirst' => $qFirst,
                'totalOutflow' => $totalOutflow,
                'turnoverPct' => $turnoverPct,
                'daysStagnant' => $daysStagnant,
                'snapshotsCount' => $snapshotsCount,
                'status' => $status,
                'isNearEd' => $isNearEd,
                'actionText' => $actionText,
                'actionColor' => $actionColor,
                'unit_price' => $unitPrice,
                'locked_capital' => $lockedCapital,
            ]);
        }

        return $rows;
    }

    private function applyStagnantFilters(Collection $rows): Collection
    {
        if ($this->stagnantBranchId) {
            $rows = $rows->filter(fn ($r) => (int) $r->entry->distributor_id === (int) $this->stagnantBranchId);
        }

        if ($this->stagnantSatuanFilter) {
            $rows = $rows->filter(function ($r) {
                $sat = strtoupper(trim((string) $r->entry->displayUnit()));
                if ($this->stagnantSatuanFilter === 'BTL') {
                    return str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL');
                }
                if ($this->stagnantSatuanFilter === 'AMP') {
                    return str_contains($sat, 'AMP');
                }
                if ($this->stagnantSatuanFilter === 'PCS') {
                    return ! str_contains($sat, 'BTL') && ! str_contains($sat, 'BOTOL') && ! str_contains($sat, 'AMP');
                }

                return true;
            });
        }

        if ($this->stagnantRiskFilter === 'dead') {
            $rows = $rows->where('status', 'dead_stock');
        } elseif ($this->stagnantRiskFilter === 'slow') {
            $rows = $rows->where('status', 'slow_moving');
        } elseif ($this->stagnantRiskFilter === 'critical_ed') {
            $rows = $rows->filter(fn ($r) => in_array($r->status, ['dead_stock', 'slow_moving']) && $r->isNearEd);
        } else {
            // 'all': dead_stock dan slow_moving
            $rows = $rows->filter(fn ($r) => in_array($r->status, ['dead_stock', 'slow_moving']));
        }

        if (trim($this->stagnantSearch) !== '') {
            $term = mb_strtolower(trim($this->stagnantSearch));
            $rows = $rows->filter(function ($r) use ($term) {
                $name = mb_strtolower($r->distributorItem?->item_name ?? '');
                $code = mb_strtolower($r->distributorItem?->source_item_id ?? '');
                $ns = mb_strtolower($r->distributorItem?->netsuiteItem?->netsuite_name ?? '');
                $batch = mb_strtolower($r->entry->batch_no ?? '');
                $dist = mb_strtolower($r->distributor?->name ?? '');

                return str_contains($name, $term)
                    || str_contains($code, $term)
                    || str_contains($ns, $term)
                    || str_contains($batch, $term)
                    || str_contains($dist, $term);
            });
        }

        $isDesc = ($this->stagnantSortDir === 'desc');

        return $rows->sort(function ($a, $b) use ($isDesc) {
            switch ($this->stagnantSortBy) {
                case 'total_value':
                    $valA = (float) $a->locked_capital;
                    $valB = (float) $b->locked_capital;
                    break;
                case 'unit_price':
                    $valA = (float) $a->unit_price;
                    $valB = (float) $b->unit_price;
                    break;
                case 'quantity':
                    $valA = (float) $a->qLatest;
                    $valB = (float) $b->qLatest;
                    break;
                case 'distributor':
                    $valA = strtolower((string) ($a->distributor?->name ?? ''));
                    $valB = strtolower((string) ($b->distributor?->name ?? ''));
                    break;
                case 'expired_date':
                    $valA = $a->entry->expired_date ? $a->entry->expired_date->timestamp : 0;
                    $valB = $b->entry->expired_date ? $b->entry->expired_date->timestamp : 0;
                    break;
                case 'turnover_pct':
                    $valA = (float) $a->turnoverPct;
                    $valB = (float) $b->turnoverPct;
                    break;
                case 'days_stagnant':
                    $valA = (int) $a->daysStagnant;
                    $valB = (int) $b->daysStagnant;
                    break;
                case 'item_name':
                default:
                    $valA = strtolower($a->entry->displayName());
                    $valB = strtolower($b->entry->displayName());
                    break;
            }

            if ($valA == $valB) {
                return 0;
            }

            return ($valA < $valB xor $isDesc) ? -1 : 1;
        })->values();
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
        if ($this->activeTab === 'stock' && $latestPerDist->isNotEmpty()) {
            // Sama seperti latestSnapshotPerDistributor(): rantai OR + GROUP BY
            // memaksa pemindaian index penuh. Versi LATERAL hanya melakukan satu
            // lompatan index per distributor. Terukur 256 ms -> di bawah 5 ms
            // pada 705 ribu baris.
            $prevDates = $this->previousSnapshotDates($latestPerDist);

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

                // Batch ikut jadi kunci, mengikuti grain baris tabel.
                //
                // Tanpa batch, keyBy() hanya menyimpan baris TERAKHIR per item —
                // sehingga sejak stok dicatat per batch, setiap baris batch akan
                // dibandingkan dengan angka batch lain secara acak.
                $previousQuantities = $previousEntries->keyBy(
                    fn ($e) => "{$e->distributor_id}-{$e->distributor_item_id}-".mb_strtoupper(trim((string) $e->batch_no))
                );
            }
        }

        // 5. Perhitungan KPI Satuan Farmasi & Metrik Operasional
        $kpi = [
            'total_all' => 0,
            'total_btl' => 0,
            'total_amp' => 0,
            'total_pcs' => 0,
            'total_sku' => $allCurrentEntries->pluck('distributor_item_id')->unique()->count(),
            'total_branches' => $allCurrentEntries->pluck('distributor_id')->unique()->count(),
            'expiring_soon' => 0,
            'unmapped' => 0,
            'total_value' => 0.0,
        ];

        foreach ($allCurrentEntries as $entry) {
            $sat = strtoupper(trim((string) $entry->displayUnit()));
            $q = (float) $entry->quantity;
            $unitPrice = (float) ($entry->distributorItem?->netsuiteItem?->unit_price ?? 0.0);
            $kpi['total_value'] += ($q * $unitPrice);
            $kpi['total_all'] += $q;

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

        $isNationalSummary = ($this->selectedGroup === 'ALL');

        if ($isNationalSummary) {
            // Stacked Bar Chart per Distributor Group (Looker Studio Style)
            $distGroups = self::groupKeys();

            foreach ($distGroups as $dg) {
                $dataPoints = [];
                foreach ($topProductsMap as $productName => $pData) {
                    $qtyInGroup = $pData['entries']
                        ->filter(fn ($e) => self::getDistributorGroup($e->distributor) === $dg)
                        ->sum('quantity');
                    $dataPoints[] = (float) $qtyInGroup;
                }

                $chartTopProducts['datasets'][] = [
                    'label' => self::getDistributorGroupLabel($dg),
                    'data' => $dataPoints,
                    'backgroundColor' => self::getDistributorGroupColor($dg),
                    'stack' => 'stack0',
                ];
            }
        } else {
            // Single Horizontal Bar untuk distributor grup tertentu
            $dataPoints = [];
            foreach ($topProductsMap as $productName => $pData) {
                $dataPoints[] = (float) $pData['total'];
            }

            $labelName = self::getDistributorGroupLabel($this->selectedGroup);

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
            'metric' => $this->donutMetric,
            'total' => 0.0,
        ];

        $totalDonut = 0.0;

        if ($isNationalSummary) {
            $distGroups = self::groupKeys();
            $totals = [];

            foreach ($distGroups as $dg) {
                $entriesForGroup = $allCurrentEntries->filter(fn ($e) => self::getDistributorGroup($e->distributor) === $dg);
                if ($this->donutMetric === 'value') {
                    $sum = (float) $entriesForGroup->sum(function ($e) {
                        $unitPrice = (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0);
                        return (float) $e->quantity * $unitPrice;
                    });
                } else {
                    $sum = (float) $entriesForGroup->sum('quantity');
                }
                $totals[$dg] = $sum;
                $totalDonut += $sum;
            }

            foreach ($distGroups as $dg) {
                if ($totals[$dg] > 0) {
                    $chartDonut['labels'][] = self::getDistributorGroupLabel($dg);
                    $chartDonut['data'][] = round((float) $totals[$dg], 2);
                    $chartDonut['colors'][] = self::getDistributorGroupColor($dg);
                }
            }
        } else {
            // Jika memilih 1 distributor grup, tampilkan komposisi per sediaan yang ada stoknya (> 0)
            if ($this->donutMetric === 'value') {
                $valBtl = (float) $allCurrentEntries->filter(function ($e) {
                    $sat = strtoupper(trim((string) $e->displayUnit()));
                    return str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL');
                })->sum(fn ($e) => (float) $e->quantity * (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0));

                $valAmp = (float) $allCurrentEntries->filter(function ($e) {
                    $sat = strtoupper(trim((string) $e->displayUnit()));
                    return str_contains($sat, 'AMP');
                })->sum(fn ($e) => (float) $e->quantity * (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0));

                $valPcs = (float) $allCurrentEntries->filter(function ($e) {
                    $sat = strtoupper(trim((string) $e->displayUnit()));
                    return ! str_contains($sat, 'BTL') && ! str_contains($sat, 'BOTOL') && ! str_contains($sat, 'AMP');
                })->sum(fn ($e) => (float) $e->quantity * (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0));

                $sediaanData = [
                    'Botol (Btl)' => $valBtl,
                    'Ampul (Amp)' => $valAmp,
                    'Pcs / Box' => $valPcs,
                ];
            } else {
                $sediaanData = [
                    'Botol (Btl)' => (float) $kpi['total_btl'],
                    'Ampul (Amp)' => (float) $kpi['total_amp'],
                    'Pcs / Box' => (float) $kpi['total_pcs'],
                ];
            }

            $totalDonut = array_sum($sediaanData);
            $sediaanColors = [
                'Botol (Btl)' => '#0d6d5f',
                'Ampul (Amp)' => '#06b6d4',
                'Pcs / Box' => '#f59e0b',
            ];
            foreach ($sediaanData as $sName => $sVal) {
                if ($sVal > 0) {
                    $chartDonut['labels'][] = $sName;
                    $chartDonut['data'][] = round((float) $sVal, 2);
                    $chartDonut['colors'][] = $sediaanColors[$sName];
                }
            }
        }

        $chartDonut['total'] = $totalDonut;

        // 7b. Ranking Top 5 Cabang Terbesar (Branch Capital / Physical Allocation)
        $branchGroups = $allCurrentEntries->groupBy('distributor_id');
        $totalUniverse = $this->donutMetric === 'value'
            ? (float) $allCurrentEntries->sum(fn ($e) => (float) $e->quantity * (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0))
            : (float) $allCurrentEntries->sum('quantity');

        $topBranches = $branchGroups->map(function ($entries) use ($totalUniverse) {
            $dist = $entries->first()->distributor;
            $val = (float) $entries->sum(fn ($e) => (float) $e->quantity * (float) ($e->distributorItem?->netsuiteItem?->unit_price ?? 0.0));
            $qty = (float) $entries->sum('quantity');
            $metricVal = $this->donutMetric === 'value' ? $val : $qty;
            $pct = $totalUniverse > 0 ? round(($metricVal / $totalUniverse) * 100, 1) : 0.0;
            $grp = self::getDistributorGroup($dist);

            return (object) [
                'id' => $dist?->id,
                'name' => $dist?->name ?? ('Cabang #'.$entries->first()->distributor_id),
                'short_name' => self::formatBranchDisplayName($dist?->name, $dist?->distributor_code),
                'code' => $dist?->distributor_code ?? '',
                'group' => $grp,
                'color' => self::getDistributorGroupColor($grp),
                'total_value' => $val,
                'total_qty' => $qty,
                'metric_val' => $metricVal,
                'pct' => $pct,
            ];
        })
        ->filter(fn ($b) => $b->metric_val > 0)
        ->sortByDesc('metric_val')
        ->take(5)
        ->values();

        // 8. Filter Tabel Detail Stock (Tab 1: Posisi Stok On-Hand)
        $filteredEntries = $allCurrentEntries;

        if ($this->selectedBranchId) {
            $filteredEntries = $filteredEntries->filter(fn ($e) => (int) $e->distributor_id === (int) $this->selectedBranchId);
        }

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
                $sat = strtoupper(trim((string) $e->displayUnit()));
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

        if ($this->batchFilter !== '') {
            $batchTerm = mb_strtolower(trim($this->batchFilter));
            $filteredEntries = $filteredEntries->filter(function ($e) use ($batchTerm) {
                return mb_strtolower(trim((string) $e->batch_no)) === $batchTerm;
            });
        }

        // Jumlah baris hasil filter dipakai badge pada tombol tab, yang tampil di
        // SEMUA tab — jadi tetap harus dihitung walau tabelnya tidak dirender.
        // (map() mempertahankan jumlah elemen, jadi nilainya identik dengan
        // $mappedTableRows->count() seperti perhitungan sebelumnya.)
        $totalRows = $filteredEntries->count();

        // Tabel detail hanya dirender di dalam blok tab "stock" pada blade. Saat
        // tab lain aktif, map + sort + paginate di bawah ini hasilnya tidak pernah
        // ditampilkan — kosongkan sumbernya agar seluruh langkah itu jadi no-op.
        if ($this->activeTab !== 'stock') {
            $filteredEntries = collect();
        }

        // Map data tabel dengan komparasi Delta
        $mappedTableRows = $filteredEntries->map(function (StockEntry $entry) use ($previousQuantities) {
            $key = "{$entry->distributor_id}-{$entry->distributor_item_id}-".mb_strtoupper(trim((string) $entry->batch_no));
            $prev = $previousQuantities->get($key);
            $prevQty = $prev ? (float) $prev->quantity : null;
            $delta = $prevQty !== null ? ((float) $entry->quantity - $prevQty) : null;
            $deltaPct = ($prevQty && $prevQty != 0.0) ? round($delta / $prevQty * 100, 1) : null;
            $unitPrice = (float) ($entry->distributorItem?->netsuiteItem?->unit_price ?? 0.0);
            $totalValue = (float) $entry->quantity * $unitPrice;

            return (object) [
                'entry' => $entry,
                'delta' => $delta,
                'delta_pct' => $deltaPct,
                'unit_price' => $unitPrice,
                'total_value' => $totalValue,
            ];
        });

        // 9. Interactive Column Sorting
        $isDesc = $this->sortDir === 'desc';
        $mappedTableRows = $mappedTableRows->sort(function ($a, $b) use ($isDesc) {
            switch ($this->sortBy) {
                case 'total_value':
                    $valA = (float) $a->total_value;
                    $valB = (float) $b->total_value;
                    break;
                case 'unit_price':
                    $valA = (float) $a->unit_price;
                    $valB = (float) $b->unit_price;
                    break;
                case 'quantity':
                    $valA = (float) $a->entry->quantity;
                    $valB = (float) $b->entry->quantity;
                    break;
                case 'satuan':
                    $valA = strtolower((string) $a->entry->displayUnit());
                    $valB = strtolower((string) $b->entry->displayUnit());
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
                    $valA = strtolower($a->entry->displayName());
                    $valB = strtolower($b->entry->displayName());
                    break;
            }

            if ($valA == $valB) {
                return 0;
            }

            return ($valA < $valB xor $isDesc) ? -1 : 1;
        })->values();

        // Paginate secara manual
        $page = $this->getPage();
        $slice = $mappedTableRows->slice(($page - 1) * $this->perPage, $this->perPage)->values();

        $stockTablePaginated = new LengthAwarePaginator(
            $slice,
            $totalRows,
            $this->perPage,
            $page,
            ['path' => '#', 'pageName' => 'page']
        );

        // 10. Tab 2: Monitoring Kedaluwarsa (FEFO Watchlist)
        if ($this->activeTab === 'expiry') {
            $fefoAllRows = $this->fefoRowsFrom($allCurrentEntries);

            if ($this->fefoBranchId) {
                $fefoAllRows = $fefoAllRows->filter(fn ($r) => (int) $r->entry->distributor_id === (int) $this->fefoBranchId);
            }

            if ($this->fefoSatuanFilter) {
                $fefoAllRows = $fefoAllRows->filter(function ($r) {
                    $sat = strtoupper(trim((string) $r->entry->displayUnit()));
                    if ($this->fefoSatuanFilter === 'BTL') {
                        return str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL');
                    }
                    if ($this->fefoSatuanFilter === 'AMP') {
                        return str_contains($sat, 'AMP');
                    }
                    if ($this->fefoSatuanFilter === 'PCS') {
                        return ! str_contains($sat, 'BTL') && ! str_contains($sat, 'BOTOL') && ! str_contains($sat, 'AMP');
                    }

                    return true;
                });
            }

            $fefoSummary = [
                'total' => $fefoAllRows->count(),
                'expired' => $fefoAllRows->where('tier', 'expired')->count(),
                'critical' => $fefoAllRows->where('tier', 'critical')->count(),
                'warning' => $fefoAllRows->where('tier', 'warning')->count(),
                'safe' => $fefoAllRows->where('tier', 'safe')->count(),
                'expired_qty' => (float) $fefoAllRows->where('tier', 'expired')->sum(fn ($r) => (float) $r->entry->quantity),
                'critical_qty' => (float) $fefoAllRows->where('tier', 'critical')->sum(fn ($r) => (float) $r->entry->quantity),
                'warning_qty' => (float) $fefoAllRows->where('tier', 'warning')->sum(fn ($r) => (float) $r->entry->quantity),
                'safe_qty' => (float) $fefoAllRows->where('tier', 'safe')->sum(fn ($r) => (float) $r->entry->quantity),
                'total_qty_at_risk' => (float) $fefoAllRows->whereIn('tier', ['expired', 'critical', 'warning'])->sum(fn ($r) => (float) $r->entry->quantity),
                'total_risk_value' => (float) $fefoAllRows->whereIn('tier', ['expired', 'critical'])->sum(fn ($r) => (float) $r->total_value),
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

            // 10b. Tab 2: Macro Expiry Horizon Breakdown
            $chartFefoHorizon = $this->calculateFefoHorizon($allCurrentEntries, $scopedDistributorIds);
        } else {
            // Tab 2 tidak aktif: Gunakan agregat instan in-memory untuk badge
            $fefoTotalCount = $allCurrentEntries->filter(fn ($r) => $r->expired_date !== null)->count();
            $fefoSummary = [
                'total' => $fefoTotalCount,
                'expired' => 0,
                'critical' => $kpi['expiring_soon'],
                'warning' => 0,
                'safe' => 0,
                'expired_qty' => 0.0,
                'critical_qty' => 0.0,
                'warning_qty' => 0.0,
                'safe_qty' => 0.0,
                'total_qty_at_risk' => 0.0,
                'total_risk_value' => 0.0,
            ];
            $fefoTablePaginated = new LengthAwarePaginator(
                collect(),
                $fefoTotalCount,
                $this->perPage,
                1,
                ['path' => '#', 'pageName' => 'page']
            );
            $chartFefoHorizon = [
                'labels' => [],
                'datasets' => [],
                'unit' => $this->fefoSatuanFilter ?: $this->fefoChartUnit,
                'unit_label' => match ($this->fefoSatuanFilter ?: $this->fefoChartUnit) {
                    'BTL' => 'Botol (BTL)',
                    'AMP' => 'Ampul (AMP)',
                    'PCS' => 'Pcs / Box (PCS)',
                    default => 'Semua Satuan',
                },
                'total_qty' => 0.0,
                'total_batches' => 0,
                'national' => [],
                'has_data' => false,
            ];
        }

        // 11. Tab 3: Kepatuhan Upload Cabang (Compliance Tracker)
        $targetComplianceDate = $this->complianceDate ?: ($latestSnapshotDate ?: Carbon::today()->toDateString());

        if ($this->activeTab === 'compliance') {
            $onTargetDate = StockEntry::query()
                ->select('distributor_id', DB::raw('COUNT(*) as total_rows'), DB::raw('SUM(quantity) as total_qty'))
                ->where('tanggal', $targetComplianceDate)
                // Dibatasi ke cabang yang sedang di-scope; sebelumnya
                // mengagregasi SELURUH distributor termasuk yang non-aktif.
                ->whereIn('distributor_id', $scopedDistributorIds ?: [0])
                ->groupBy('distributor_id')
                ->get()
                ->keyBy('distributor_id');

            // Tanggal upload terakhir dipakai ulang dari $latestPerDist yang
            // sudah dihitung di langkah 2 — nilainya identik.
            //
            // Sebelumnya di sini ada query "MAX(tanggal) GROUP BY
            // distributor_id" TANPA filter apa pun: agregat seluruh tabel,
            // dijalankan KEDUA KALINYA pada request yang sama. Itulah sebabnya
            // tab Kepatuhan terasa lebih lambat dari tab lain — ia membayar
            // query termahal di dashboard dua kali.
            $lastUploadDates = $latestPerDist->keyBy('distributor_id');

            $complianceAllRows = $availableBranches->map(function ($b) use ($onTargetDate, $lastUploadDates, $targetComplianceDate) {
                $today = $onTargetDate->get($b->id);
                $hasSubmitted = $today !== null;
                // Sumbernya kini $latestPerDist, jadi nama kolomnya max_tanggal.
                $lastDate = $lastUploadDates->get($b->id)?->max_tanggal;

                $daysOverdue = null;
                if (! $hasSubmitted && $lastDate) {
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
        } else {
            // Tab 3 tidak aktif: hitung persentase kepatuhan secara cepat via COUNT DISTINCT
            $submittedCount = StockEntry::query()
                ->where('tanggal', $targetComplianceDate)
                ->whereIn('distributor_id', $scopedDistributorIds ?: [0])
                ->distinct('distributor_id')
                ->count('distributor_id');

            $totalBranchesCount = count($availableBranches);
            $complianceRate = $totalBranchesCount > 0 ? round(($submittedCount / $totalBranchesCount) * 100, 1) : 0;

            $complianceSummary = [
                'total_branches' => $totalBranchesCount,
                'total_submitted' => $submittedCount,
                'total_missing' => max(0, $totalBranchesCount - $submittedCount),
                'compliance_rate' => $complianceRate,
                'target_date' => $targetComplianceDate,
            ];
            $complianceTablePaginated = new LengthAwarePaginator(
                collect(),
                $totalBranchesCount,
                $this->perPage,
                1,
                ['path' => '#', 'pageName' => 'page']
            );
        }

        // 12. Line Chart: Trend Stock On Hand (Snapshot Historis per Satuan)
        $chartTrend = $this->calculateStockTrend($scopedDistributorIds, $latestSnapshotDate);

        // 12b. Combo Chart: Tren Kepatuhan Laporan Harian (Daily Compliance Tracker)
        //
        // Canvas-nya (#chart-compliance-trend) hanya dirender di dalam blok tab
        // "compliance", jadi menghitungnya saat tab lain aktif murni terbuang —
        // termasuk satu query agregasi ke stock_entries.
        if ($this->activeTab === 'compliance') {
            $chartComplianceTrend = $this->calculateComplianceTrend($scopedDistributorIds, $targetComplianceDate);
        } else {
            $chartComplianceTrend = [
                'labels' => [],
                'full_dates' => [],
                'submitted' => [],
                'missing' => [],
                'rates' => [],
                'total_branches' => 0,
                'avg_rate' => 0.0,
                'period' => $this->complianceTrendPeriod,
                'start_date' => '',
                'end_date' => '',
                'start_date_formatted' => '-',
                'end_date_formatted' => '-',
                'has_data' => false,
            ];
        }

        // 13. Tab 4: Stok Macet & Slow-Moving (Dead Stock Alert)
        if ($this->activeTab === 'stagnant') {
            $stagnantAllRows = $this->calculateStagnantStock($allCurrentEntries, $scopedDistributorIds, $latestSnapshotDate);

            $stagnantSummary = [
                'total' => $stagnantAllRows->count(),
                'dead' => $stagnantAllRows->where('status', 'dead_stock')->count(),
                'slow' => $stagnantAllRows->where('status', 'slow_moving')->count(),
                'critical_ed' => $stagnantAllRows->where('isNearEd', true)->count(),
                'total_qty' => (float) $stagnantAllRows->sum('qLatest'),
                'avg_days' => $stagnantAllRows->count() > 0 ? (int) round($stagnantAllRows->avg('daysStagnant')) : 0,
                'total_locked_capital' => (float) $stagnantAllRows->sum('locked_capital'),
            ];

            $stagnantFiltered = $this->applyStagnantFilters($stagnantAllRows);

            $stagnantTotal = $stagnantFiltered->count();
            $stagnantSlice = $stagnantFiltered->slice(($page - 1) * $this->perPage, $this->perPage)->values();
            $stagnantTablePaginated = new LengthAwarePaginator(
                $stagnantSlice,
                $stagnantTotal,
                $this->perPage,
                $page,
                ['path' => '#', 'pageName' => 'page']
            );
        } else {
            // Tab 4 tidak aktif: lewati query riwayat 30 hari & nested loop yang sangat berat
            $stagnantSummary = [
                'total' => 0,
                'dead' => 0,
                'slow' => 0,
                'critical_ed' => 0,
                'total_qty' => 0.0,
                'avg_days' => 0,
                'total_locked_capital' => 0.0,
            ];
            $stagnantTablePaginated = new LengthAwarePaginator(
                collect(),
                0,
                $this->perPage,
                1,
                ['path' => '#', 'pageName' => 'page']
            );
        }

        // Dispatch browser event agar chart selalu sinkron dengan data terfilter
        $this->dispatch('charts-updated', [
            'top' => $chartTopProducts,
            'donut' => $chartDonut,
            'trend' => $chartTrend,
            'compliance' => $chartComplianceTrend,
        ]);

        return view('livewire.dashboard', [
            'kpi' => $kpi,
            'latestDate' => $latestSnapshotDate,
            'availableBranches' => $availableBranches,
            'stockTable' => $stockTablePaginated,
            'chartTopProducts' => $chartTopProducts,
            'chartDonut' => $chartDonut,
            'donutMetric' => $this->donutMetric,
            'topBranches' => $topBranches,
            'chartTrend' => $chartTrend,
            'trendUnit' => $this->trendUnit,
            'trendPeriod' => $this->trendPeriod,
            'chartFefoHorizon' => $chartFefoHorizon,
            'fefoChartUnit' => $this->fefoChartUnit,
            'chartComplianceTrend' => $chartComplianceTrend,
            'complianceTrendPeriod' => $this->complianceTrendPeriod,
            'totalDisplayRows' => $totalRows,
            'activeTab' => $this->activeTab,
            'selectedGroup' => $this->selectedGroup,
            'selectedBranchId' => $this->selectedBranchId,
            'search' => $this->search,
            'satuanFilter' => $this->satuanFilter,
            'batchFilter' => $this->batchFilter,
            'sortBy' => $this->sortBy,
            'sortDir' => $this->sortDir,
            'perPage' => $this->perPage,
            'fefoBranchId' => $this->fefoBranchId,
            'fefoSatuanFilter' => $this->fefoSatuanFilter,
            'expiryRiskFilter' => $this->expiryRiskFilter,
            'expirySearch' => $this->expirySearch,
            'fefoSortBy' => $this->fefoSortBy,
            'fefoSortDir' => $this->fefoSortDir,
            'complianceDate' => $this->complianceDate,
            'complianceStatus' => $this->complianceStatus,
            'complianceSearch' => $this->complianceSearch,
            'stagnantPeriod' => $this->stagnantPeriod,
            'stagnantRiskFilter' => $this->stagnantRiskFilter,
            'stagnantBranchId' => $this->stagnantBranchId,
            'stagnantSatuanFilter' => $this->stagnantSatuanFilter,
            'stagnantSearch' => $this->stagnantSearch,
            'stagnantSortBy' => $this->stagnantSortBy,
            'stagnantSortDir' => $this->stagnantSortDir,
            'fefoTable' => $fefoTablePaginated,
            'fefoSummary' => $fefoSummary,
            'complianceTable' => $complianceTablePaginated,
            'complianceSummary' => $complianceSummary,
            'stagnantTable' => $stagnantTablePaginated,
            'stagnantSummary' => $stagnantSummary,
        ]);
    }

    public function exportStagnantCsv(): StreamedResponse
    {
        Gate::authorize('dashboard.view');

        $scopedDistributorIds = $this->scopedBranchQuery()->pluck('id')->all();
        $latestPerDist = $this->latestSnapshotPerDistributor($scopedDistributorIds);
        $latestSnapshotDate = $latestPerDist->max('max_tanggal');
        $allCurrentEntries = $this->entriesForLatestSnapshots($latestPerDist);

        $stagnantAll = $this->calculateStagnantStock($allCurrentEntries, $scopedDistributorIds, $latestSnapshotDate);
        $filtered = $this->applyStagnantFilters($stagnantAll);

        $filename = 'stock-macet-slow-moving-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($filtered) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Distributor',
                'Kode Item Distributor',
                'Nama Produk Distributor',
                'Master NetSuite',
                'Satuan',
                'No. Batch',
                'Expired Date',
                'Status ED',
                'Harga Satuan (Rp)',
                'Stok Terkini',
                'Total Nilai Stok (Rp)',
                'Stok Awal Periode',
                'Total Outflow',
                'Turnover (%)',
                'Hari Stagnan',
                'Status Pergerakan',
                'Rekomendasi Tindakan',
            ]);

            foreach ($filtered as $row) {
                fputcsv($handle, [
                    $row->distributor?->name ?? '-',
                    $row->distributorItem?->source_item_id ?? '-',
                    $row->distributorItem?->item_name ?? '-',
                    $row->distributorItem?->netsuiteItem?->netsuite_name ?? '-',
                    $row->entry->displayUnit(),
                    $row->entry->batch_no ?? '-',
                    $row->entry->expired_date ? $row->entry->expired_date->format('d/m/Y') : '-',
                    $row->entry->expiryStatus(),
                    $row->unit_price > 0 ? $row->unit_price : 0,
                    $row->qLatest,
                    $row->locked_capital > 0 ? $row->locked_capital : 0,
                    $row->qFirst,
                    $row->totalOutflow,
                    $row->turnoverPct . '%',
                    $row->daysStagnant,
                    $row->status === 'dead_stock' ? 'Macet Total' : ($row->status === 'slow_moving' ? 'Pergerakan Lambat' : 'Normal'),
                    $row->actionText,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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

        $fefoRows = $this->fefoRowsFrom($this->entriesForLatestSnapshots($latestPerDist));

        if ($this->fefoBranchId) {
            $fefoRows = $fefoRows->filter(fn ($r) => (int) $r->entry->distributor_id === (int) $this->fefoBranchId);
        }

        if ($this->fefoSatuanFilter) {
            $fefoRows = $fefoRows->filter(function ($r) {
                $sat = strtoupper(trim((string) $r->entry->displayUnit()));
                if ($this->fefoSatuanFilter === 'BTL') {
                    return str_contains($sat, 'BTL') || str_contains($sat, 'BOTOL');
                }
                if ($this->fefoSatuanFilter === 'AMP') {
                    return str_contains($sat, 'AMP');
                }
                if ($this->fefoSatuanFilter === 'PCS') {
                    return ! str_contains($sat, 'BTL') && ! str_contains($sat, 'BOTOL') && ! str_contains($sat, 'AMP');
                }

                return true;
            });
        }

        $mapped = $this->applyFefoFilters($fefoRows);

        // Cakupan filter ikut di nama file agar penerima tahu ini data tersaring.
        $scopeLabel = $this->fefoBranchId
            ? ($this->scopedBranchQuery()->find($this->fefoBranchId)?->distributor_code ?? 'cabang')
            : $this->selectedGroup;
        $scopeLabel = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $scopeLabel);

        if ($this->fefoSatuanFilter) {
            $scopeLabel .= '_'.$this->fefoSatuanFilter;
        }

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
                'Harga Satuan (Rp)',
                'Estimasi Nilai (Rp)',
            ]);

            foreach ($mapped as $r) {
                $e = $r->entry;
                $ns = $e->distributorItem?->netsuiteItem;
                fputcsv($handle, [
                    self::getDistributorGroup($e->distributor),
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
                    $e->displayUnit(),
                    $r->unit_price > 0 ? $r->unit_price : 0,
                    $r->total_value > 0 ? $r->total_value : 0,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
