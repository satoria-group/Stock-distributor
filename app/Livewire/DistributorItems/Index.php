<?php

namespace App\Livewire\DistributorItems;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
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

    public ?int $editingId = null;

    public string $item_name = '';

    public string $satuan = '';

    public ?int $netsuite_item_id = null;

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

    public function openEdit(int $id): void
    {
        $item = DistributorItem::findOrFail($id);
        Gate::authorize('update', $item);

        $this->editingId = $item->id;
        $this->item_name = $item->item_name;
        $this->satuan = (string) $item->satuan;
        $this->netsuite_item_id = $item->netsuite_item_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'netsuite_item_id' => ['nullable', 'exists:netsuite_items,id'],
        ]);

        $item = DistributorItem::findOrFail($this->editingId);
        Gate::authorize('update', $item);

        $netsuiteSatuan = $data['netsuite_item_id']
            ? NetsuiteItem::find($data['netsuite_item_id'])?->default_satuan
            : null;

        $item->update([
            ...$data,
            'netsuite_satuan' => $netsuiteSatuan,
        ]);

        $this->showModal = false;
        $this->reset(['editingId', 'item_name', 'satuan', 'netsuite_item_id']);
        session()->flash('status', 'Mapping item tersimpan.');
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

        return view('livewire.distributor-items.index', [
            'items' => $items,
            'distributors' => Distributor::orderBy('name')->get(),
            'netsuiteItems' => NetsuiteItem::orderBy('netsuite_name')->get(),
            'unmappedCount' => DistributorItem::unmapped()->count(),
        ]);
    }
}
