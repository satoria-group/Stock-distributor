<?php

namespace App\Livewire\NetsuiteItems;

use App\Models\DistributorItem;
use App\Models\DplPriceProduct;
use App\Models\NetsuiteItem;
use App\Support\Search;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Master Produk (Netsuite)', 'subtitle' => 'Daftar produk induk beserta kode Netsuite-nya.'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $priceFilter = 'all'; // 'all', 'with_price', 'without_price'

    public string $sortBy = 'netsuite_name'; // 'netsuite_name', 'netsuite_id', 'price'

    public string $sortDirection = 'asc'; // 'asc', 'desc'

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $netsuite_id = '';

    public string $netsuite_name = '';

    public string $default_satuan = '';

    public string $price = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', NetsuiteItem::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPriceFilter(): void
    {
        $this->resetPage();
    }

    public function setPriceFilter(string $filter): void
    {
        $this->priceFilter = $filter;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->priceFilter = 'all';
        $this->sortBy = 'netsuite_name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $column === 'price' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', NetsuiteItem::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $item = NetsuiteItem::with('dplPrice')->findOrFail($id);
        Gate::authorize('update', $item);

        $this->editingId = $item->id;
        $this->netsuite_id = $item->netsuite_id;
        $this->netsuite_name = $item->netsuite_name;
        $this->default_satuan = (string) $item->default_satuan;
        $this->price = $item->unit_price > 0 ? (string) (int) $item->unit_price : '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'netsuite_id' => ['required', 'string', 'max:100', Rule::unique('netsuite_items', 'netsuite_id')->ignore($this->editingId)],
            'netsuite_name' => ['required', 'string', 'max:255'],
            'default_satuan' => ['nullable', 'string', 'max:50'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $itemData = [
            'netsuite_id' => $data['netsuite_id'],
            'netsuite_name' => $data['netsuite_name'],
            'default_satuan' => $data['default_satuan'],
        ];

        if ($this->editingId) {
            $item = NetsuiteItem::findOrFail($this->editingId);
            Gate::authorize('update', $item);
            $item->update($itemData);
        } else {
            Gate::authorize('create', NetsuiteItem::class);
            $item = NetsuiteItem::create($itemData);
        }

        if (isset($data['price']) && $data['price'] !== '' && $data['price'] !== null) {
            $numPrice = (float) $data['price'];
            $exists = DB::table('dpl_price_product')
                ->where('netsuite_item_id', $item->id)
                ->where('id_price_region', 1)
                ->exists();

            if ($exists) {
                DB::table('dpl_price_product')
                    ->where('netsuite_item_id', $item->id)
                    ->where('id_price_region', 1)
                    ->update([
                        'price' => $numPrice,
                        'price_reguler' => (int) $numPrice,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('dpl_price_product')->insert([
                    'id_product' => $item->netsuite_id,
                    'id_price_region' => 1,
                    'price' => $numPrice,
                    'price_reguler' => (int) $numPrice,
                    'netsuite_id' => $item->netsuite_id,
                    'netsuite_item_id' => $item->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Data produk Netsuite tersimpan.');
    }

    public function delete(int $id): void
    {
        $item = NetsuiteItem::findOrFail($id);
        Gate::authorize('delete', $item);

        $mappedCount = DistributorItem::where('netsuite_item_id', $id)->count();
        if ($mappedCount > 0) {
            session()->flash('error', "Tidak bisa dihapus: produk ini masih dipetakan ke {$mappedCount} item distributor. Lepas pemetaannya terlebih dahulu.");
            return;
        }

        $item->delete();
        session()->flash('status', 'Produk Netsuite dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->netsuite_id = '';
        $this->netsuite_name = '';
        $this->default_satuan = '';
        $this->price = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $totalCount = NetsuiteItem::count();
        $withPriceCount = NetsuiteItem::whereHas('dplPrices', fn ($q) => $q->where('price', '>', 0))->count();
        $withoutPriceCount = NetsuiteItem::whereDoesntHave('dplPrices', fn ($q) => $q->where('price', '>', 0))->count();

        $query = NetsuiteItem::query()
            ->with('dplPrice')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('netsuite_name', 'ilike', Search::contains($this->search))
                    ->orWhere('netsuite_id', 'ilike', Search::contains($this->search));
            }))
            ->when($this->priceFilter === 'with_price', fn ($q) => $q->whereHas('dplPrices', fn ($sub) => $sub->where('price', '>', 0)))
            ->when($this->priceFilter === 'without_price', fn ($q) => $q->whereDoesntHave('dplPrices', fn ($sub) => $sub->where('price', '>', 0)));

        if ($this->sortBy === 'price') {
            $priceSub = DplPriceProduct::selectRaw('COALESCE(price, 0)')
                ->whereColumn('dpl_price_product.netsuite_item_id', 'netsuite_items.id')
                ->where('id_price_region', 1)
                ->limit(1);

            $query->orderBy($priceSub, $this->sortDirection)
                  ->orderBy('netsuite_name', 'asc');
        } elseif (in_array($this->sortBy, ['netsuite_id', 'netsuite_name', 'default_satuan'])) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        } else {
            $query->orderBy('netsuite_name', 'asc');
        }

        $items = $query->paginate(15);

        return view('livewire.netsuite-items.index', [
            'items' => $items,
            'totalCount' => $totalCount,
            'withPriceCount' => $withPriceCount,
            'withoutPriceCount' => $withoutPriceCount,
        ]);
    }
}

