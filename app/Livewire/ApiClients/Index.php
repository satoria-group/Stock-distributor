<?php

namespace App\Livewire\ApiClients;

use App\Models\ApiClient;
use App\Models\DistributorGroup;
use App\Models\StockApiLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Akses API Distributor', 'subtitle' => 'Token untuk distributor yang mengirim stok langsung dari sistemnya (push API).'])]
class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $distributor_group_id = null;

    public string $allowed_ips = '';

    /** Token asli, ditampilkan SEKALI setelah dibuat. */
    public ?string $plainToken = null;

    public function mount(): void
    {
        Gate::authorize('api-clients.manage');
    }

    public function openCreate(): void
    {
        Gate::authorize('api-clients.manage');
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        Gate::authorize('api-clients.manage');
        $c = ApiClient::findOrFail($id);

        $this->editingId = $c->id;
        $this->name = $c->name;
        $this->distributor_group_id = $c->distributor_group_id;
        $this->allowed_ips = (string) $c->allowed_ips;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('api-clients.manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'distributor_group_id' => ['required', 'exists:distributor_groups,id'],
            'allowed_ips' => ['nullable', 'string', 'max:1000', function ($attr, $value, $fail) {
                foreach (array_filter(array_map('trim', explode(',', (string) $value))) as $ip) {
                    if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                        $fail("'{$ip}' bukan alamat IP yang valid.");
                    }
                }
            }],
        ], [], [
            'name' => 'nama',
            'distributor_group_id' => 'grup usaha',
            'allowed_ips' => 'IP yang diizinkan',
        ]);

        $data['allowed_ips'] = implode(',', array_filter(array_map('trim', explode(',', (string) $data['allowed_ips'])))) ?: null;

        if ($this->editingId) {
            ApiClient::findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Akses API diperbarui.');
        } else {
            [, $this->plainToken] = ApiClient::register($data);
            session()->flash('status', 'Akses API dibuat. Salin token di bawah — token hanya ditampilkan sekali.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function regenerate(int $id): void
    {
        Gate::authorize('api-clients.manage');
        $this->plainToken = ApiClient::findOrFail($id)->issueToken();
        session()->flash('status', 'Token baru dibuat dan token lama langsung tidak berlaku. Salin token di bawah — hanya ditampilkan sekali.');
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('api-clients.manage');
        $c = ApiClient::findOrFail($id);
        $c->update(['is_active' => ! $c->is_active]);
        session()->flash('status', $c->is_active ? 'Akses API diaktifkan.' : 'Akses API dinonaktifkan. Kiriman berikutnya akan ditolak.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('api-clients.manage');
        ApiClient::findOrFail($id)->delete();
        session()->flash('status', 'Akses API dihapus.');
    }

    public function dismissToken(): void
    {
        $this->plainToken = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->distributor_group_id = null;
        $this->allowed_ips = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.api-clients.index', [
            'clients' => ApiClient::with('group')->orderBy('name')->get(),
            'groups' => DistributorGroup::orderBy('name')->get(['id', 'name']),
            'logs' => StockApiLog::with('client')->latest()->limit(50)->get(),
            'endpoint' => url('/api/v1/stock'),
        ]);
    }
}
