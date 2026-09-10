<?php

namespace App\Livewire\NetsuiteItems;

use App\Models\NetsuiteItem;
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

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $netsuite_id = '';

    public string $netsuite_name = '';

    public string $default_satuan = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', NetsuiteItem::class);
    }

    public function updatedSearch(): void
    {
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
        $item = NetsuiteItem::findOrFail($id);
        Gate::authorize('update', $item);

        $this->editingId = $item->id;
        $this->netsuite_id = $item->netsuite_id;
        $this->netsuite_name = $item->netsuite_name;
        $this->default_satuan = (string) $item->default_satuan;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'netsuite_id' => ['required', 'string', 'max:100', Rule::unique('netsuite_items', 'netsuite_id')->ignore($this->editingId)],
            'netsuite_name' => ['required', 'string', 'max:255'],
            'default_satuan' => ['nullable', 'string', 'max:50'],
        ]);

        if ($this->editingId) {
            $item = NetsuiteItem::findOrFail($this->editingId);
            Gate::authorize('update', $item);
            $item->update($data);
        } else {
            Gate::authorize('create', NetsuiteItem::class);
            NetsuiteItem::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Data produk Netsuite tersimpan.');
    }

    public function delete(int $id): void
    {
        $item = NetsuiteItem::findOrFail($id);
        Gate::authorize('delete', $item);
        $item->delete();
        session()->flash('status', 'Produk Netsuite dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->netsuite_id = '';
        $this->netsuite_name = '';
        $this->default_satuan = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $items = NetsuiteItem::query()
            ->when($this->search, fn ($q) => $q
                ->where('netsuite_name', 'ilike', "%{$this->search}%")
                ->orWhere('netsuite_id', 'ilike', "%{$this->search}%"))
            ->orderBy('netsuite_name')
            ->paginate(15);

        return view('livewire.netsuite-items.index', [
            'items' => $items,
        ]);
    }
}
