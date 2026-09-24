<?php

namespace App\Livewire\DistributorItems;

use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Services\ItemMatchingService;
use App\Support\Search;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Mapping Item Distributor', 'subtitle' => 'Pemetaan nama item versi distributor ke produk Netsuite.'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /** all | mapped | unmapped */
    #[Url]
    public string $mappingFilter = 'all';

    public bool $showModal = false;

    public bool $showBulkModal = false;

    public array $selectedBulkIds = [];

    public array $modalSuggestions = [];

    public ?int $editingId = null;

    public string $item_name = '';

    public string $satuan = '';

    public ?int $netsuite_item_id = null;

    public ?int $distributor_id = null;

    /** Pemilik pemetaan pada form: 'g:<id>' grup, atau 'd:<id>' cabang. */
    public string $owner = '';

    /** Penyaring daftar dengan pengkodean yang sama. */
    #[Url]
    public string $ownerFilter = '';

    /**
     * Pemilik pemetaan, dikodekan 'g:<id>' untuk grup atau 'd:<id>' untuk cabang.
     *
     * Satu isian, bukan dua: pemetaan dimiliki grup ATAU satu cabang, tidak
     * pernah keduanya — dua dropdown terpisah hanya akan mengundang kombinasi
     * yang tidak punya arti.
     *
     * @return array{distributor_group_id?: int, distributor_id?: int}|null
     */
    public static function decodeOwner(?string $owner): ?array
    {
        if (! $owner || ! str_contains($owner, ':')) {
            return null;
        }

        [$kind, $id] = explode(':', $owner, 2);
        $id = (int) $id;

        if ($id <= 0) {
            return null;
        }

        return match ($kind) {
            'g' => ['distributor_group_id' => $id],
            'd' => ['distributor_id' => $id],
            default => null,
        };
    }

    public static function encodeOwner(DistributorItem $item): string
    {
        return $item->distributor_group_id
            ? 'g:'.$item->distributor_group_id
            : ($item->distributor_id ? 'd:'.$item->distributor_id : '');
    }

    /** Nama pemilik untuk ditampilkan di daftar. */
    public static function ownerLabel(DistributorItem $item): string
    {
        if ($item->distributorGroup) {
            return $item->distributorGroup->name;
        }

        return $item->distributor?->name ?? '—';
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', DistributorItem::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOwnerFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMappingFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->ownerFilter = '';
        $this->mappingFilter = 'all';
        $this->resetPage();
    }

    public function updatedItemName(): void
    {
        if (trim($this->item_name) !== '') {
            $service = app(ItemMatchingService::class);
            $this->modalSuggestions = $service->findMatches($this->item_name)['top_matches'] ?? [];
        } else {
            $this->modalSuggestions = [];
        }
    }

    public function selectSuggestion(int $netsuiteId): void
    {
        $this->netsuite_item_id = $netsuiteId;
    }

    public function approveMapping(int $distributorItemId, int $netsuiteItemId): void
    {
        $item = DistributorItem::findOrFail($distributorItemId);
        Gate::authorize('update', $item);

        $netsuite = NetsuiteItem::findOrFail($netsuiteItemId);

        $item->update(['netsuite_item_id' => $netsuite->id]);

        session()->flash('status', "1-Click Berhasil: Item '{$item->item_name}' telah disetujui & dipetakan ke [{$netsuite->netsuite_id}] {$netsuite->netsuite_name}.");
    }

    public function openBulkModal(): void
    {
        Gate::authorize('create', DistributorItem::class);

        $service = app(ItemMatchingService::class);
        $unmapped = DistributorItem::unmapped()->get();
        $suggestions = $service->getSuggestionsForCollection($unmapped);

        // Hanya centang otomatis saran berkeyakinan tinggi. Saran medium/low tetap
        // ditampilkan untuk ditinjau, tapi harus dicentang manual oleh Admin.
        $this->selectedBulkIds = array_keys(array_filter(
            $suggestions,
            fn ($s) => $s['score'] >= ItemMatchingService::AUTO_APPROVE_MIN_SCORE
        ));
        $this->showBulkModal = true;
    }

    public function toggleAllBulk(array $availableIds): void
    {
        if (count($this->selectedBulkIds) === count($availableIds)) {
            $this->selectedBulkIds = [];
        } else {
            $this->selectedBulkIds = $availableIds;
        }
    }

    public function approveSelectedBulk(): void
    {
        Gate::authorize('create', DistributorItem::class);

        if (empty($this->selectedBulkIds)) {
            $this->addError('bulk', 'Pilih minimal satu item untuk disetujui.');

            return;
        }

        $service = app(ItemMatchingService::class);
        $items = DistributorItem::whereIn('id', $this->selectedBulkIds)->unmapped()->get();

        $approvedCount = 0;
        $skippedCount = 0;
        DB::transaction(function () use ($items, $service, &$approvedCount, &$skippedCount) {
            foreach ($items as $item) {
                $match = $service->findMatches($item->item_name);

                // Jangan pernah menerapkan tebakan berkeyakinan rendah secara massal —
                // item ini tetap "belum ter-mapping" dan harus ditinjau manual.
                if (! $match['best_match'] || $match['score'] < ItemMatchingService::AUTO_APPROVE_MIN_SCORE) {
                    $skippedCount++;

                    continue;
                }

                Gate::authorize('update', $item);

                $item->update(['netsuite_item_id' => $match['best_match']->id]);
                $approvedCount++;
            }
        });

        $this->showBulkModal = false;
        $this->selectedBulkIds = [];

        $message = "Berhasil! {$approvedCount} item telah disetujui dan dipetakan secara massal ke Master Netsuite.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} item dilewati karena keyakinan kecocokannya di bawah "
                .ItemMatchingService::AUTO_APPROVE_MIN_SCORE.'% — silakan petakan manual lewat tombol Edit.';
        }
        session()->flash('status', $message);
    }

    public function openCreate(): void
    {
        Gate::authorize('create', DistributorItem::class);
        $this->editingId = null;
        // Pemilik mengikuti penyaring yang sedang dipakai — paling sering itu
        // memang yang dimaksud operator saat menekan Tambah.
        $this->owner = $this->ownerFilter;
        $this->item_name = '';
        $this->satuan = '';
        $this->netsuite_item_id = null;
        $this->modalSuggestions = [];
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $item = DistributorItem::findOrFail($id);
        Gate::authorize('update', $item);

        $this->editingId = $item->id;
        $this->distributor_id = $item->distributor_id;
        $this->owner = self::encodeOwner($item);
        $this->item_name = $item->item_name;
        $this->satuan = (string) $item->satuan;
        $this->netsuite_item_id = $item->netsuite_item_id;

        $service = app(ItemMatchingService::class);
        $this->modalSuggestions = $service->findMatches($item->item_name)['top_matches'] ?? [];

        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        // Pemilik tidak boleh berpindah lewat form edit: memindahkan pemetaan
        // antar grup berarti memindahkan seluruh riwayat snapshot yang
        // menunjuknya, dan itu keputusan yang terlalu besar untuk sebuah
        // dropdown.
        $ownerAttrs = $this->editingId
            ? self::decodeOwner(self::encodeOwner(DistributorItem::findOrFail($this->editingId)))
            : self::decodeOwner($this->owner);

        if (! $ownerAttrs) {
            $this->addError('owner', 'Pilih dulu pemiliknya: sebuah grup distributor, atau satu cabang sebagai pengecualian.');

            return;
        }

        $rules = [
            'item_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('distributor_items', 'item_name')
                    ->where(fn ($q) => $q->where($ownerAttrs))
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'satuan' => ['nullable', 'string', 'max:50'],
            'netsuite_item_id' => ['nullable', 'exists:netsuite_items,id'],
        ];

        $data = $this->validate($rules, [
            'item_name.unique' => 'Nama item ini sudah terdaftar untuk pemilik tersebut.',
        ]);

        if ($this->editingId) {
            $item = DistributorItem::findOrFail($this->editingId);
            Gate::authorize('update', $item);

            try {
                $item->update([
                    'item_name' => $data['item_name'],
                    'satuan' => $data['satuan'],
                    'netsuite_item_id' => $data['netsuite_item_id'],
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $this->addError('item_name', 'Nama item ini sudah terdaftar untuk distributor tersebut.');

                return;
            }
        } else {
            Gate::authorize('create', DistributorItem::class);

            $existing = DistributorItem::withTrashed()
                ->where($ownerAttrs)
                ->whereRaw('LOWER(TRIM(item_name)) = ?', [mb_strtolower(trim($data['item_name']))])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'item_name' => $data['item_name'],
                    'satuan' => $data['satuan'],
                    'netsuite_item_id' => $data['netsuite_item_id'],
                ]);
            } else {
                try {
                    DistributorItem::create($ownerAttrs + [
                        'item_name' => $data['item_name'],
                        'satuan' => $data['satuan'],
                        'netsuite_item_id' => $data['netsuite_item_id'],
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    $existing = DistributorItem::withTrashed()
                        ->where($ownerAttrs)
                        ->whereRaw('LOWER(TRIM(item_name)) = ?', [mb_strtolower(trim($data['item_name']))])
                        ->first();
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update([
                            'item_name' => $data['item_name'],
                            'satuan' => $data['satuan'],
                            'netsuite_item_id' => $data['netsuite_item_id'],
                        ]);
                    }
                }
            }
        }

        $this->showModal = false;
        $this->reset(['editingId', 'distributor_id', 'owner', 'item_name', 'satuan', 'netsuite_item_id', 'modalSuggestions']);
        session()->flash('status', 'Mapping item tersimpan.');
    }

    public function delete(int $id): void
    {
        $item = DistributorItem::findOrFail($id);
        Gate::authorize('delete', $item);
        $item->delete();
        session()->flash('status', 'Mapping item dihapus.');
    }

    public function render()
    {
        $items = DistributorItem::query()
            ->with(['distributor', 'distributorGroup', 'netsuiteItem'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('item_name', 'ilike', Search::contains($this->search))
                        ->orWhereHas('netsuiteItem', function ($ns) {
                            $ns->where('netsuite_name', 'ilike', Search::contains($this->search))
                               ->orWhere('netsuite_id', 'ilike', Search::contains($this->search));
                        });
                });
            })
            ->when(self::decodeOwner($this->ownerFilter), fn ($q, $owner) => $q->where($owner))
            ->when($this->mappingFilter === 'mapped', fn ($q) => $q->mapped())
            ->when($this->mappingFilter === 'unmapped', fn ($q) => $q->unmapped())
            ->orderBy('item_name')
            ->paginate(20);

        $service = app(ItemMatchingService::class);
        $unmappedOnPage = $items->getCollection()->filter(fn ($i) => ! $i->isMapped());
        $suggestions = $service->getSuggestionsForCollection($unmappedOnPage);

        // Bulk suggestions across all unmapped items (for toolbar badge & bulk review modal)
        $allUnmapped = DistributorItem::unmapped()->with(['distributor', 'distributorGroup'])->get();
        $bulkSuggestions = $service->getSuggestionsForCollection($allUnmapped);

        return view('livewire.distributor-items.index', [
            'items' => $items,
            'distributors' => Distributor::orderBy('name')->get(),
            'distributorGroups' => DistributorGroup::ordered()->get(),
            'netsuiteItems' => NetsuiteItem::orderBy('netsuite_name')->get(),
            'unmappedCount' => $allUnmapped->count(),
            'suggestions' => $suggestions,
            'bulkSuggestions' => $bulkSuggestions,
            'allUnmapped' => $allUnmapped,
        ]);
    }
}
