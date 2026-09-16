<?php

namespace App\Livewire\DistributorItems;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Services\ItemMatchingService;
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

    #[Url]
    public string $distributorFilter = '';

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

    public function mount(): void
    {
        Gate::authorize('viewAny', DistributorItem::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDistributorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMappingFilter(): void
    {
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

        $item->update([
            'netsuite_item_id' => $netsuite->id,
            'netsuite_satuan' => $netsuite->default_satuan,
        ]);

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

                $item->update([
                    'netsuite_item_id' => $match['best_match']->id,
                    'netsuite_satuan' => $match['best_match']->default_satuan,
                ]);
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
        $this->distributor_id = $this->distributorFilter ? (int) $this->distributorFilter : null;
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
        $distributorId = $this->editingId
            ? DistributorItem::findOrFail($this->editingId)->distributor_id
            : $this->distributor_id;

        $rules = [
            'item_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('distributor_items', 'item_name')
                    ->where('distributor_id', $distributorId)
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'satuan' => ['nullable', 'string', 'max:50'],
            'netsuite_item_id' => ['nullable', 'exists:netsuite_items,id'],
        ];

        if (! $this->editingId) {
            $rules['distributor_id'] = ['required', 'exists:distributors,id'];
        }

        $data = $this->validate($rules, [
            'item_name.unique' => 'Nama item ini sudah terdaftar untuk distributor tersebut.',
        ]);

        $netsuiteSatuan = $data['netsuite_item_id']
            ? NetsuiteItem::find($data['netsuite_item_id'])?->default_satuan
            : null;

        if ($this->editingId) {
            $item = DistributorItem::findOrFail($this->editingId);
            Gate::authorize('update', $item);

            try {
                $item->update([
                    'item_name' => $data['item_name'],
                    'satuan' => $data['satuan'],
                    'netsuite_item_id' => $data['netsuite_item_id'],
                    'netsuite_satuan' => $netsuiteSatuan,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $this->addError('item_name', 'Nama item ini sudah terdaftar untuk distributor tersebut.');

                return;
            }
        } else {
            Gate::authorize('create', DistributorItem::class);

            $existing = DistributorItem::withTrashed()
                ->where('distributor_id', $data['distributor_id'])
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
                    'netsuite_satuan' => $netsuiteSatuan,
                ]);
            } else {
                try {
                    DistributorItem::create([
                        'distributor_id' => $data['distributor_id'],
                        'item_name' => $data['item_name'],
                        'satuan' => $data['satuan'],
                        'netsuite_item_id' => $data['netsuite_item_id'],
                        'netsuite_satuan' => $netsuiteSatuan,
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    $existing = DistributorItem::withTrashed()
                        ->where('distributor_id', $data['distributor_id'])
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
                            'netsuite_satuan' => $netsuiteSatuan,
                        ]);
                    }
                }
            }
        }

        $this->showModal = false;
        $this->reset(['editingId', 'distributor_id', 'item_name', 'satuan', 'netsuite_item_id', 'modalSuggestions']);
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
            ->with(['distributor', 'netsuiteItem'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('item_name', 'ilike', "%{$this->search}%")
                        ->orWhereHas('netsuiteItem', function ($ns) {
                            $ns->where('netsuite_name', 'ilike', "%{$this->search}%")
                               ->orWhere('netsuite_id', 'ilike', "%{$this->search}%");
                        });
                });
            })
            ->when($this->distributorFilter, fn ($q) => $q->where('distributor_id', $this->distributorFilter))
            ->when($this->mappingFilter === 'mapped', fn ($q) => $q->mapped())
            ->when($this->mappingFilter === 'unmapped', fn ($q) => $q->unmapped())
            ->orderBy('item_name')
            ->paginate(20);

        $service = app(ItemMatchingService::class);
        $unmappedOnPage = $items->getCollection()->filter(fn ($i) => ! $i->isMapped());
        $suggestions = $service->getSuggestionsForCollection($unmappedOnPage);

        // Bulk suggestions across all unmapped items (for toolbar badge & bulk review modal)
        $allUnmapped = DistributorItem::unmapped()->with('distributor')->get();
        $bulkSuggestions = $service->getSuggestionsForCollection($allUnmapped);

        return view('livewire.distributor-items.index', [
            'items' => $items,
            'distributors' => Distributor::orderBy('name')->get(),
            'netsuiteItems' => NetsuiteItem::orderBy('netsuite_name')->get(),
            'unmappedCount' => $allUnmapped->count(),
            'suggestions' => $suggestions,
            'bulkSuggestions' => $bulkSuggestions,
            'allUnmapped' => $allUnmapped,
        ]);
    }
}
