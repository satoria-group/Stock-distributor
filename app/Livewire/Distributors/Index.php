<?php

namespace App\Livewire\Distributors;

use App\Models\Distributor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Master Distributor', 'subtitle' => 'Kelola daftar distributor yang mengirim laporan stock harian.'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $distributor_code = '';

    public string $name = '';

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', Distributor::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Distributor::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $distributor = Distributor::findOrFail($id);
        Gate::authorize('update', $distributor);

        $this->editingId = $distributor->id;
        $this->distributor_code = $distributor->distributor_code;
        $this->name = $distributor->name;
        $this->is_active = $distributor->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'distributor_code' => ['required', 'string', 'max:50', Rule::unique('distributors', 'distributor_code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editingId) {
            $distributor = Distributor::findOrFail($this->editingId);
            Gate::authorize('update', $distributor);
            $distributor->update($data);
        } else {
            Gate::authorize('create', Distributor::class);
            Distributor::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Data distributor tersimpan.');
    }

    public function delete(int $id): void
    {
        $distributor = Distributor::findOrFail($id);
        Gate::authorize('delete', $distributor);
        $distributor->delete();
        session()->flash('status', 'Distributor dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->distributor_code = '';
        $this->name = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $distributors = Distributor::query()
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('distributor_code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.distributors.index', [
            'distributors' => $distributors,
        ]);
    }
}
