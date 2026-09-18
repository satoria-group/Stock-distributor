<?php

namespace App\Livewire\Stock;

use App\Livewire\Dashboard;
use App\Models\Distributor;
use App\Models\StockEntry;
use App\Models\StockSnapshotActivity;
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

    public bool $showDeleteModal = false;

    public ?string $deleteTanggal = null;

    public ?int $deleteDistributorId = null;

    public ?string $deleteDistributorName = null;

    public ?string $deleteDistributorCode = null;

    public ?int $deleteTotalSku = null;

    public ?float $deleteTotalQuantity = null;

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
        $this->startDate = $this->normalizeDateInput($this->startDate);
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->endDate = $this->normalizeDateInput($this->endDate);
        $this->resetPage();
    }

    private function normalizeDateInput(string $val): string
    {
        $val = trim($val);
        if ($val === '') {
            return '';
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return $val;
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
                'expired_date' => $e->expired_date ? $e->expired_date->translatedFormat('d M Y') : '—',
                'expiry_status' => $e->expiryStatus(),
                'is_mapped' => $e->distributorItem?->isMapped() ?? false,
            ];
        })->sortBy('item_name')->values()->all();

        $latestEntry = $entries->sortByDesc('updated_at')->first();

        $activities = StockSnapshotActivity::with('user')
            ->where('tanggal', $tanggal)
            ->where('distributor_id', $distributorId)
            ->orderBy('created_at', 'asc')
            ->get();

        $hasAutomation = $activities->contains(fn ($a) => $a->action === 'automation')
            || $entries->contains(fn ($e) => $e->uploaded_by === null);

        $reviewAct = $activities->where('action', 'review')->last();
        $isReviewed = ! is_null($reviewAct);
        $reviewerName = $reviewAct?->user?->name;

        $uploaderDisplay = $hasAutomation
            ? ($isReviewed ? 'Auto: ' . ($reviewerName ?: 'Reviewer') : 'Otomasi Email')
            : ($latestEntry?->uploader?->name ?? 'Sistem / Impor');

        $this->selectedSnapshot = [
            'tanggal' => $tanggal,
            'tanggal_formatted' => Carbon::parse($tanggal)->translatedFormat('d M Y'),
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
            'is_automation' => $hasAutomation,
            'is_reviewed' => $isReviewed,
            'reviewer_name' => $reviewerName,
            'uploader_name' => $uploaderDisplay,
            'updated_at' => $latestEntry?->updated_at?->translatedFormat('d M Y, H:i') ?? '—',
            'items' => $items,
            'activities' => $activities->map(function ($act) {
                return [
                    'id' => $act->id,
                    'action' => $act->action,
                    'user_name' => $act->user?->name ?? ($act->action === 'automation' ? 'Otomasi Sistem' : 'Sistem'),
                    'description' => $act->description,
                    'created_at' => $act->created_at ? $act->created_at->translatedFormat('d M Y, H:i') : '—',
                ];
            })->all(),
        ];

        $this->showDetailModal = true;
    }

    public function markAsReviewed(string $tanggal, int $distributorId): void
    {
        Gate::authorize('viewAny', StockEntry::class);

        $distributor = Distributor::find($distributorId);
        if (! $distributor) {
            return;
        }

        $exists = StockEntry::where('tanggal', $tanggal)
            ->where('distributor_id', $distributorId)
            ->exists();

        if (! $exists) {
            return;
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $userName = $user?->name ?? 'Reviewer';

        StockSnapshotActivity::create([
            'tanggal' => $tanggal,
            'distributor_id' => $distributorId,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'action' => 'review',
            'description' => "Ditinjau dan disetujui oleh {$userName}",
            'metadata' => [
                'reviewer_id' => \Illuminate\Support\Facades\Auth::id(),
                'reviewer_name' => $userName,
                'reviewed_at' => now()->toIso8601String(),
            ],
        ]);

        if ($this->showDetailModal && $this->selectedSnapshot &&
            $this->selectedSnapshot['tanggal'] === $tanggal &&
            $this->selectedSnapshot['distributor_id'] === $distributorId) {
            $this->viewDetail($tanggal, $distributorId);
        }

        $tanggalFormatted = Carbon::parse($tanggal)->translatedFormat('d M Y');
        session()->flash('status', "Snapshot {$distributor->name} tanggal {$tanggalFormatted} berhasil ditandai telah di-review oleh {$userName}.");
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedSnapshot = null;
    }

    public function confirmDeleteSnapshot(string $tanggal, int $distributorId): void
    {
        Gate::authorize('delete', StockEntry::class);

        $distributor = Distributor::find($distributorId);
        if (! $distributor) {
            return;
        }

        $entries = StockEntry::query()
            ->where('tanggal', $tanggal)
            ->where('distributor_id', $distributorId);

        $count = (clone $entries)->count();
        if ($count === 0) {
            return;
        }

        $sumQty = (float) (clone $entries)->sum('quantity');

        $this->deleteTanggal = $tanggal;
        $this->deleteDistributorId = $distributorId;
        $this->deleteDistributorName = $distributor->name;
        $this->deleteDistributorCode = $distributor->distributor_code;
        $this->deleteTotalSku = $count;
        $this->deleteTotalQuantity = $sumQty;
        $this->showDeleteModal = true;
    }

    public function cancelDeleteSnapshot(): void
    {
        $this->showDeleteModal = false;
        $this->deleteTanggal = null;
        $this->deleteDistributorId = null;
        $this->deleteDistributorName = null;
        $this->deleteDistributorCode = null;
        $this->deleteTotalSku = null;
        $this->deleteTotalQuantity = null;
    }

    public function deleteSnapshot(): void
    {
        Gate::authorize('delete', StockEntry::class);

        if (! $this->deleteTanggal || ! $this->deleteDistributorId) {
            $this->cancelDeleteSnapshot();

            return;
        }

        $distributorName = $this->deleteDistributorName ?? 'Distributor';
        $distributorCode = $this->deleteDistributorCode ?? '';
        $tanggalFormatted = Carbon::parse($this->deleteTanggal)->translatedFormat('d M Y');

        $deletedCount = 0;
        DB::transaction(function () use (&$deletedCount) {
            $deletedCount = StockEntry::query()
                ->where('tanggal', $this->deleteTanggal)
                ->where('distributor_id', $this->deleteDistributorId)
                ->delete();

            StockSnapshotActivity::query()
                ->where('tanggal', $this->deleteTanggal)
                ->where('distributor_id', $this->deleteDistributorId)
                ->delete();
        });

        $this->cancelDeleteSnapshot();
        $this->showDetailModal = false;
        $this->selectedSnapshot = null;

        $targetLabel = $distributorCode ? "{$distributorName} ({$distributorCode})" : $distributorName;
        session()->flash('status', "Snapshot stok {$targetLabel} tanggal {$tanggalFormatted} berhasil dihapus ({$deletedCount} baris entri).");
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
            ->orderBy(DB::raw('MAX(updated_at)'), 'desc')
            ->paginate($this->perPage);

        // 2.1 Enrich snapshot collection dengan data audit aktivitas & status review
        $pairs = $snapshots->getCollection()->map(function ($s) {
            return [
                'tanggal' => $s->tanggal->format('Y-m-d'),
                'distributor_id' => (int) $s->distributor_id,
            ];
        });

        $allActivities = collect();
        if ($pairs->isNotEmpty()) {
            $allActivities = StockSnapshotActivity::with('user')
                ->where(function ($q) use ($pairs) {
                    foreach ($pairs as $p) {
                        $q->orWhere(function ($sub) use ($p) {
                            $sub->where('tanggal', $p['tanggal'])
                                ->where('distributor_id', $p['distributor_id']);
                        });
                    }
                })
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy(fn ($a) => $a->tanggal->format('Y-m-d') . '_' . $a->distributor_id);
        }

        foreach ($snapshots->getCollection() as $s) {
            $key = $s->tanggal->format('Y-m-d') . '_' . $s->distributor_id;
            $acts = $allActivities->get($key, collect());

            $isAutomation = $acts->contains(fn ($a) => $a->action === 'automation') || ($s->uploaded_by === null);
            $reviewAct = $acts->where('action', 'review')->last();
            $isReviewed = ! is_null($reviewAct);
            $reviewerName = $reviewAct?->user?->name;

            if ($isAutomation) {
                $uploaderDisplay = $isReviewed ? ('Auto: ' . ($reviewerName ?: 'Reviewer')) : 'Otomasi Email';
            } else {
                $editors = $acts->whereIn('action', ['upload', 'edit', 'merge'])->map(fn ($a) => $a->user?->name)->filter()->unique()->values();
                if ($editors->count() > 1) {
                    $uploaderDisplay = $editors->first() . ' (Edit: ' . $editors->last() . ')';
                } else {
                    $uploaderDisplay = $s->uploader?->name ?? 'Sistem / Impor';
                }
            }

            $s->is_automation = $isAutomation;
            $s->is_reviewed = $isReviewed;
            $s->reviewer_name = $reviewerName;
            $s->uploader_display = $uploaderDisplay;
            $s->activity_count = $acts->count();
        }

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
