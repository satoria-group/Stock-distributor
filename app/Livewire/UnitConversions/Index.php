<?php

namespace App\Livewire\UnitConversions;

use App\Models\NetsuiteItem;
use App\Models\UnitConversion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Konversi Satuan', 'subtitle' => 'Satuan dari berkas distributor yang dikonversi saat impor, mis. 1 BOX = 50 PCS.'])]
class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $from_unit = '';

    public string $to_unit = 'PCS';

    public string $factor = '';

    public function mount(): void
    {
        Gate::authorize('unit-conversions.view');
    }

    public function openCreate(): void
    {
        Gate::authorize('unit-conversions.manage');
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        Gate::authorize('unit-conversions.manage');
        $c = UnitConversion::findOrFail($id);

        $this->editingId = $c->id;
        $this->from_unit = $c->from_unit;
        $this->to_unit = $c->to_unit;
        $this->factor = rtrim(rtrim(number_format($c->factor, 4, '.', ''), '0'), '.');
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('unit-conversions.manage');

        $this->from_unit = UnitConversion::normalizeUnit($this->from_unit);

        $data = $this->validate([
            'from_unit' => [
                'required', 'string', 'max:50',
                Rule::unique('unit_conversions', 'from_unit')->ignore($this->editingId),
                Rule::notIn([UnitConversion::normalizeUnit($this->to_unit)]),
            ],
            'to_unit' => ['required', Rule::in(NetsuiteItem::UNITS)],
            'factor' => ['required', 'numeric', 'gt:0'],
        ], [
            'from_unit.unique' => 'Satuan ini sudah punya aturan konversi.',
            'from_unit.not_in' => 'Satuan asal tidak boleh sama dengan satuan tujuan.',
        ], [
            'from_unit' => 'satuan dari berkas',
            'to_unit' => 'satuan tujuan',
            'factor' => 'faktor',
        ]);

        $this->editingId
            ? UnitConversion::findOrFail($this->editingId)->update($data)
            : UnitConversion::create($data);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Konversi satuan tersimpan. Berlaku untuk impor berikutnya.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('unit-conversions.manage');
        UnitConversion::findOrFail($id)->delete();
        session()->flash('status', 'Konversi satuan dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->from_unit = '';
        $this->to_unit = 'PCS';
        $this->factor = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.unit-conversions.index', [
            'conversions' => UnitConversion::orderBy('from_unit')->get(),
        ]);
    }
}
