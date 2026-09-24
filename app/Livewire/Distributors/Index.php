<?php

namespace App\Livewire\Distributors;

use App\Models\Distributor;
use App\Models\DistributorGroup;
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

    public ?int $distributor_group_id = null;

    // ── Tab & penyaringan ────────────────────────────────────────────────

    /** 'distributors' atau 'groups' */
    public string $tab = 'distributors';

    /** Penyaring daftar: id grup, 'none' untuk yang belum bergrup, atau '' semua. */
    public string $groupFilter = '';

    /** Penyaring status: 'active', 'inactive', atau '' semua. */
    public string $statusFilter = '';

    // ── Penetapan massal ─────────────────────────────────────────────────

    /** @var array<int, int> id distributor yang dicentang */
    public array $selected = [];

    public ?int $bulkGroupId = null;

    /** @var array<int, array{name: string, prefix: string, count: int}> usulan dari awalan kode */
    public array $suggestions = [];

    // ── Form grup ────────────────────────────────────────────────────────

    public bool $showGroupModal = false;

    public ?int $editingGroupId = null;

    public string $group_name = '';

    public ?string $group_color = null;

    public ?string $group_notes = null;

    public ?string $group_sender_email = null;

    public ?int $group_template_id = null;

    public bool $group_is_active = true;

    public ?string $group_sort_order = null;

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'groups' ? 'groups' : 'distributors';
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedGroupFilter(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    // ── Penetapan massal ─────────────────────────────────────────────────

    /**
     * Tetapkan distributor yang dicentang ke sebuah grup.
     *
     * Ada 289 distributor; menetapkannya satu per satu lewat form berarti
     * pekerjaan ini tidak akan pernah selesai. Karena itu penetapan massal
     * bukan pemanis, melainkan syarat agar fiturnya terpakai.
     */
    public function assignSelectedToGroup(): void
    {
        Gate::authorize('create', Distributor::class);

        $ids = array_values(array_filter(array_map('intval', $this->selected)));

        if ($ids === []) {
            session()->flash('error', 'Belum ada distributor yang dicentang.');

            return;
        }

        $groupId = $this->bulkGroupId ?: null;
        $groupName = $groupId ? DistributorGroup::find($groupId)?->name : null;

        if ($groupId && ! $groupName) {
            session()->flash('error', 'Grup tujuan tidak ditemukan.');

            return;
        }

        Distributor::whereIn('id', $ids)->update(['distributor_group_id' => $groupId]);

        $count = count($ids);
        $this->selected = [];

        session()->flash('status', $groupName
            ? "{$count} distributor dipindahkan ke grup {$groupName}."
            : "{$count} distributor dilepas dari grupnya.");
    }

    /** Centang seluruh baris pada halaman yang sedang tampil. */
    public function selectAllOnPage(array $ids): void
    {
        $this->selected = array_values(array_unique(array_merge($this->selected, array_map('intval', $ids))));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Usulkan grup untuk distributor yang belum bergrup, berdasarkan awalan kode.
     *
     * Ini SEKADAR usulan yang ditinjau manusia, bukan aturan yang dipakai
     * sistem: setelah disetujui, keanggotaannya tersimpan sebagai data dan
     * awalan kode tidak lagi berpengaruh apa pun. Tanpa bantuan ini,
     * mengelompokkan ratusan distributor berarti ratusan kali klik.
     */
    public function suggestGroups(): void
    {
        Gate::authorize('create', Distributor::class);

        $ungrouped = Distributor::whereNull('distributor_group_id')->get(['id', 'distributor_code']);
        $existing = DistributorGroup::pluck('name')->map(fn ($n) => mb_strtoupper($n))->all();

        $byPrefix = [];
        foreach ($ungrouped as $distributor) {
            // Awalan = bagian huruf di depan kode, sebelum angka atau nama kota.
            preg_match('/^[A-Za-z]+/', (string) $distributor->distributor_code, $m);
            $prefix = mb_strtoupper($m[0] ?? '');

            if (mb_strlen($prefix) < 3) {
                continue;
            }

            // Kode seperti UDCBANDUNG: yang menandai grup adalah 3-4 huruf
            // pertamanya, bukan seluruh rentetan huruf sampai nama kotanya.
            $prefix = mb_substr($prefix, 0, 4);
            $byPrefix[$prefix][] = $distributor->id;
        }

        $suggestions = [];
        foreach ($byPrefix as $prefix => $ids) {
            // Awalan beranggota satu hampir pasti distributor tunggal, bukan
            // grup bercabang — tidak diusulkan supaya tidak melahirkan puluhan
            // grup beranggota satu.
            if (count($ids) < 2 || in_array($prefix, $existing, true)) {
                continue;
            }

            $suggestions[] = ['name' => $prefix, 'prefix' => $prefix, 'count' => count($ids)];
        }

        usort($suggestions, fn ($a, $b) => $b['count'] <=> $a['count']);

        $this->suggestions = $suggestions;

        if ($suggestions === []) {
            session()->flash('status', 'Tidak ada usulan grup baru — distributor yang belum bergrup tampaknya memang berdiri sendiri.');
        }
    }

    /** Buat grup dari satu usulan, lalu isi anggotanya. */
    public function applySuggestion(string $prefix): void
    {
        Gate::authorize('create', Distributor::class);

        $prefix = mb_strtoupper(trim($prefix));
        if ($prefix === '') {
            return;
        }

        $group = DistributorGroup::firstOrCreate(
            ['name' => $prefix],
            [
                'color' => DistributorGroup::nextAvailableColor(),
                'sort_order' => (int) DistributorGroup::max('sort_order') + 1,
            ]
        );

        $count = Distributor::whereNull('distributor_group_id')
            ->where('distributor_code', 'ilike', $prefix.'%')
            ->update(['distributor_group_id' => $group->id]);

        $this->suggestions = array_values(array_filter(
            $this->suggestions,
            fn ($s) => $s['prefix'] !== $prefix
        ));

        session()->flash('status', "Grup {$prefix} dibuat dengan {$count} distributor.");
    }

    public function dismissSuggestions(): void
    {
        $this->suggestions = [];
    }

    // ── CRUD grup ────────────────────────────────────────────────────────

    public function openGroupCreate(): void
    {
        Gate::authorize('create', Distributor::class);
        $this->resetGroupForm();
        $this->group_color = DistributorGroup::nextAvailableColor();
        $this->showGroupModal = true;
    }

    public function openGroupEdit(int $id): void
    {
        Gate::authorize('create', Distributor::class);

        $group = DistributorGroup::findOrFail($id);
        $this->resetGroupForm();

        $this->editingGroupId = $group->id;
        $this->group_name = $group->name;
        $this->group_color = $group->color;
        $this->group_notes = $group->notes;
        $this->group_sender_email = $group->sender_email;
        $this->group_template_id = $group->template_group_id;
        $this->group_is_active = (bool) $group->is_active;
        $this->group_sort_order = (string) $group->sort_order;

        $this->showGroupModal = true;
    }

    public function saveGroup(): void
    {
        Gate::authorize('create', Distributor::class);

        $data = $this->validate([
            'group_name' => ['required', 'string', 'max:100', Rule::unique('distributor_groups', 'name')->ignore($this->editingGroupId)],
            'group_color' => ['nullable', Rule::in(array_keys(DistributorGroup::COLORS))],
            'group_notes' => ['nullable', 'string', 'max:500'],
            'group_sender_email' => ['nullable', 'string', 'max:500'],
            'group_template_id' => ['nullable', 'integer', Rule::exists('distributor_template_groups', 'id')->whereNull('deleted_at')],
            'group_is_active' => ['boolean'],
            'group_sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [], [
            'group_name' => 'nama grup',
            'group_color' => 'warna',
            'group_sort_order' => 'urutan',
            'group_sender_email' => 'email whitelist grup',
        ]);

        $payload = [
            'name' => $data['group_name'],
            'color' => $data['group_color'] ?: DistributorGroup::nextAvailableColor(),
            'notes' => $data['group_notes'] ?: null,
            'sender_email' => $data['group_sender_email'] ?: null,
            'template_group_id' => $data['group_template_id'] ?: null,
            'is_active' => $this->group_is_active,
            'sort_order' => (int) ($data['group_sort_order'] ?? 0),
        ];

        if ($this->editingGroupId) {
            DistributorGroup::findOrFail($this->editingGroupId)->update($payload);
        } else {
            DistributorGroup::create($payload);
        }

        $this->showGroupModal = false;
        $this->resetGroupForm();
        session()->flash('status', 'Grup distributor tersimpan.');
    }

    public function toggleGroupActive(int $id): void
    {
        Gate::authorize('create', Distributor::class);

        $group = DistributorGroup::findOrFail($id);
        $group->update(['is_active' => ! $group->is_active]);

        session()->flash('status', $group->is_active
            ? "Grup {$group->name} diaktifkan."
            : "Grup {$group->name} dinonaktifkan. Anggotanya terhitung sebagai \"Lainnya\" di dashboard.");
    }

    public function deleteGroup(int $id): void
    {
        Gate::authorize('create', Distributor::class);

        $group = DistributorGroup::findOrFail($id);
        $count = $group->distributors()->count();

        // Anggotanya dilepas, bukan ikut terhapus: menghapus grup tidak boleh
        // pernah berarti kehilangan master distributor.
        $group->distributors()->update(['distributor_group_id' => null]);
        $group->delete();

        session()->flash('status', "Grup {$group->name} dihapus. {$count} distributor kembali tanpa grup.");
    }

    private function resetGroupForm(): void
    {
        $this->editingGroupId = null;
        $this->group_name = '';
        $this->group_color = null;
        $this->group_notes = null;
        $this->group_sender_email = null;
        $this->group_template_id = null;
        $this->group_is_active = true;
        $this->group_sort_order = null;
        $this->resetErrorBag();
    }

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
        $this->groupFilter = '';
        $this->statusFilter = '';
        $this->selected = [];
        $this->resetPage();
    }

    /** Ada penyaring yang sedang aktif? Dipakai menandai tombol reset. */
    public function getFiltersActiveProperty(): bool
    {
        return $this->search !== '' || $this->groupFilter !== '' || $this->statusFilter !== '';
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
        $this->distributor_group_id = $distributor->distributor_group_id;
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
            'distributor_group_id' => ['nullable', 'integer', Rule::exists('distributor_groups', 'id')->whereNull('deleted_at')],
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

    /**
     * Aktifkan atau nonaktifkan satu distributor langsung dari daftar.
     *
     * Status ini bukan sekadar penanda: distributor nonaktif DITOLAK di kedua
     * jalur impor — upload manual maupun otomasi email — jadi menonaktifkannya
     * berarti menghentikan aliran datanya, bukan sekadar menyembunyikannya.
     */
    public function toggleDistributorActive(int $id): void
    {
        $distributor = Distributor::findOrFail($id);
        Gate::authorize('update', $distributor);

        $distributor->update(['is_active' => ! $distributor->is_active]);

        session()->flash('status', $distributor->is_active
            ? "{$distributor->name} diaktifkan dan kembali bisa mengirim data stok."
            : "{$distributor->name} dinonaktifkan. Pengunggahan data stoknya akan ditolak.");
    }

    /** Aktifkan atau nonaktifkan seluruh distributor yang dicentang. */
    public function bulkSetActive(bool $active): void
    {
        Gate::authorize('create', Distributor::class);

        $ids = array_values(array_filter(array_map('intval', $this->selected)));

        if ($ids === []) {
            session()->flash('error', 'Belum ada distributor yang dicentang.');

            return;
        }

        Distributor::whereIn('id', $ids)->update(['is_active' => $active]);

        $count = count($ids);
        $this->selected = [];

        session()->flash('status', $active
            ? "{$count} distributor diaktifkan."
            : "{$count} distributor dinonaktifkan. Pengunggahan data stok mereka akan ditolak.");
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
        $this->distributor_group_id = null;
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
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->groupFilter === 'none', fn ($q) => $q->whereNull('distributor_group_id'))
            ->when($this->groupFilter !== '' && $this->groupFilter !== 'none', fn ($q) => $q->where('distributor_group_id', (int) $this->groupFilter))
            ->with(['templateGroup:id,name', 'group:id,name,color,template_group_id', 'group.templateGroup:id,name'])
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.distributors.index', [
            'distributors' => $distributors,
            'templateGroups' => DistributorTemplateGroup::orderBy('name')->get(['id', 'name']),
            'groups' => DistributorGroup::ordered()->withCount('distributors')->with('templateGroup:id,name')->get(),
            'groupOptions' => DistributorGroup::ordered()->get(['id', 'name', 'color']),
            'ungroupedCount' => Distributor::whereNull('distributor_group_id')->count(),
            'activeCount' => Distributor::where('is_active', true)->count(),
            'inactiveCount' => Distributor::where('is_active', false)->count(),
        ]);
    }
}
