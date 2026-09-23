<?php

namespace App\Livewire\Distributors;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\DistributorTemplateGroup;
use App\Models\StockEntry;
use App\Support\Search;
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

    public ?string $sender_email = null;

    public bool $is_active = true;

    public ?int $template_group_id = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Distributor::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
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
        $this->sender_email = $distributor->sender_email;
        $this->is_active = $distributor->is_active;
        $this->template_group_id = $distributor->template_group_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'distributor_code' => ['required', 'string', 'max:50', Rule::unique('distributors', 'distributor_code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'sender_email' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    if (! empty($value)) {
                        $emails = array_filter(array_map('trim', explode(',', $value)));
                        foreach ($emails as $email) {
                            if (str_starts_with($email, '@')) {
                                $domain = substr($email, 1);
                                if (! filter_var('test@' . $domain, FILTER_VALIDATE_EMAIL)) {
                                    $fail("Format domain wildcard '{$email}' tidak valid.");
                                }
                            } else {
                                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    $fail("Format email '{$email}' tidak valid.");
                                }
                            }
                        }
                    }
                },
            ],
            'is_active' => ['boolean'],
            'template_group_id' => ['nullable', 'integer', Rule::exists('distributor_template_groups', 'id')->whereNull('deleted_at')],
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

        $itemCount = DistributorItem::where('distributor_id', $id)->count();
        $entryCount = StockEntry::where('distributor_id', $id)->count();
        if ($itemCount > 0 || $entryCount > 0) {
            session()->flash('error', "Tidak bisa dihapus: distributor ini masih punya {$itemCount} item mapping dan {$entryCount} baris snapshot stok.");
            return;
        }

        $distributor->delete();
        session()->flash('status', 'Distributor dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->distributor_code = '';
        $this->name = '';
        $this->sender_email = null;
        $this->is_active = true;
        $this->template_group_id = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $distributors = Distributor::query()
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'ilike', Search::contains($this->search))
                    ->orWhere('distributor_code', 'ilike', Search::contains($this->search))
                    ->orWhere('sender_email', 'ilike', Search::contains($this->search));
            }))
            ->with('templateGroup:id,name')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.distributors.index', [
            'distributors' => $distributors,
            'templateGroups' => DistributorTemplateGroup::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
