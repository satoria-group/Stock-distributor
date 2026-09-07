<?php

namespace App\Livewire;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Dashboard Stock', 'subtitle' => 'Stock terkini, alert kadaluarsa, tren, dan ranking distributor/produk.'])]
class Dashboard extends Component
{
    public ?int $distributorId = null;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('dashboard.view');
    }

    private function baseQuery()
    {
        return StockEntry::query()->when($this->distributorId, fn ($q) => $q->where('distributor_id', $this->distributorId));
    }

    /** The two most recent snapshot dates available (optionally scoped to a distributor). */
    private function snapshotDates(int $limit = 14)
    {
        return $this->baseQuery()
            ->select('tanggal')->distinct()
            ->orderByDesc('tanggal')
            ->limit($limit)
            ->pluck('tanggal');
    }

    public function render()
    {
        $dates = $this->snapshotDates();
        $latest = $dates->first();
        $previous = $dates->get(1);

        $currentRows = collect();
        $kpi = [
            'distributors' => Distributor::where('is_active', true)->count(),
            'skus' => \App\Models\NetsuiteItem::count(),
            'unmapped' => DistributorItem::unmapped()->count(),
            'expiring_soon' => 0,
        ];

        $stockTable = collect();
        $expiryAlerts = collect();
        $trend = ['labels' => [], 'values' => []];
        $topMovers = collect();
        $slowMovers = collect();

        if ($latest) {
            $currentRows = $this->baseQuery()
                ->with(['distributor', 'distributorItem.netsuiteItem'])
                ->where('tanggal', $latest)
                ->when($this->search, fn ($q) => $q->whereHas('distributorItem', fn ($qq) => $qq->where('item_name', 'ilike', "%{$this->search}%")))
                ->get();

            $previousByItem = $previous
                ? $this->baseQuery()->where('tanggal', $previous)->pluck('quantity', 'distributor_item_id')
                : collect();

            $stockTable = $currentRows->map(function (StockEntry $row) use ($previousByItem) {
                $prevQty = $previousByItem->get($row->distributor_item_id);
                $delta = $prevQty !== null ? ((float) $row->quantity - (float) $prevQty) : null;
                $deltaPct = ($prevQty && (float) $prevQty != 0.0) ? round($delta / (float) $prevQty * 100, 1) : null;

                return (object) [
                    'entry' => $row,
                    'delta' => $delta,
                    'delta_pct' => $deltaPct,
                ];
            })->sortBy(fn ($r) => $r->entry->distributorItem->item_name)->values();

            $expiryAlerts = $currentRows
                ->filter(fn ($r) => $r->expired_date !== null)
                ->sortBy(fn ($r) => $r->expired_date)
                ->take(12)
                ->values();

            $kpi['expiring_soon'] = $currentRows->filter(fn ($r) => in_array($r->expiryStatus(), ['critical', 'expired']))->count();

            // Trend: total quantity per snapshot date (oldest -> newest), for the chart.
            $trendRaw = $this->baseQuery()
                ->select('tanggal')
                ->selectRaw('SUM(quantity) as total_qty')
                ->whereIn('tanggal', $dates)
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get();

            $trend['labels'] = $trendRaw->map(fn ($r) => Carbon::parse($r->tanggal)->format('d M'))->values()->all();
            $trend['values'] = $trendRaw->map(fn ($r) => (float) $r->total_qty)->values()->all();

            // Ranking: movers between latest and previous snapshot.
            if ($previous) {
                $ranked = $stockTable->filter(fn ($r) => $r->delta !== null && $r->delta != 0)
                    ->sortByDesc(fn ($r) => $r->delta_pct);

                $topMovers = $ranked->take(6)->values();
                $slowMovers = $stockTable->filter(fn ($r) => $r->delta === null || $r->delta == 0)
                    ->sortBy(fn ($r) => $r->entry->distributorItem->item_name)
                    ->take(6)->values();
            }
        }

        return view('livewire.dashboard', [
            'kpi' => $kpi,
            'latestDate' => $latest,
            'previousDate' => $previous,
            'stockTable' => $stockTable,
            'expiryAlerts' => $expiryAlerts,
            'trend' => $trend,
            'topMovers' => $topMovers,
            'slowMovers' => $slowMovers,
            'distributors' => Distributor::orderBy('name')->get(),
        ]);
    }
}
