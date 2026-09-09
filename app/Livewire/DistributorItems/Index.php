<?php

namespace App\Livewire\DistributorItems;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Services\ItemMatchingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Mapping Item Distributor', 'subtitle' => 'Pemetaan nama item versi distributor ke produk Netsuite.'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $distributorFilter = '';

    /** all | mapped | unmapped */
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

        $this->selectedBulkIds = array_keys($suggestions);
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
        DB::transaction(function () use ($items, $service, &$approvedCount) {
            foreach ($items as $item) {
                $match = $service->findMatches($item->item_name);
                if ($match['best_match']) {
                    $item->update([
                        'netsuite_item_id' => $match['best_match']->id,
                        'netsuite_satuan' => $match['best_match']->default_satuan,
                    ]);
                    $approvedCount++;
                }
            }
        });

        $this->showBulkModal = false;
        $this->selectedBulkIds = [];
        session()->flash('status', "Berhasil! {$approvedCount} item telah disetujui dan dipetakan secara massal ke Master Netsuite.");
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
        $rules = [
            'item_name' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'netsuite_item_id' => ['nullable', 'exists:netsuite_items,id'],
        ];

        if (! $this->editingId) {
            $rules['distributor_id'] = ['required', 'exists:distributors,id'];
        }

        $data = $this->validate($rules);

        $netsuiteSatuan = $data['netsuite_item_id']
            ? NetsuiteItem::find($data['netsuite_item_id'])?->default_satuan
            : null;

        if ($this->editingId) {
            $item = DistributorItem::findOrFail($this->editingId);
            Gate::authorize('update', $item);

            $item->update([
                'item_name' => $data['item_name'],
                'satuan' => $data['satuan'],
                'netsuite_item_id' => $data['netsuite_item_id'],
                'netsuite_satuan' => $netsuiteSatuan,
            ]);
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
            ->when($this->search, fn ($q) => $q->where('item_name', 'ilike', "%{$this->search}%"))
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
