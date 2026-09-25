<?php

namespace App\Livewire\Roles;

use App\Models\User;
use App\Support\AccessPages;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Matriks hak akses: halaman mana yang boleh dibuka / dikelola tiap role.
 *
 * Role admin sengaja dikunci penuh — kalau admin bisa mencabut akses ke
 * halaman ini dari dirinya sendiri, tidak ada lagi yang bisa memulihkannya.
 */
#[Layout('layouts.app', ['title' => 'Hak Akses Role', 'subtitle' => 'Atur halaman yang boleh dibuka dan dikelola oleh tiap role.'])]
class Index extends Component
{
    /**
     * role => kunci permission => aktif. Kunci memakai '__' pengganti '.',
     * karena titik adalah pemisah path pada wire:model.
     *
     * @var array<string, array<string, bool>>
     */
    public array $matrix = [];

    public static function key(string $permission): string
    {
        return str_replace('.', '__', $permission);
    }

    public function mount(): void
    {
        Gate::authorize('users.manage');
        $this->loadMatrix();
    }

    private function editableRoles()
    {
        return Role::where('guard_name', 'web')
            ->where('name', '!=', User::ROLE_ADMIN)
            ->orderBy('name')
            ->get();
    }

    private function loadMatrix(): void
    {
        $this->matrix = [];

        foreach ($this->editableRoles() as $role) {
            $owned = $role->permissions->pluck('name')->all();
            foreach (AccessPages::permissions() as $perm) {
                $this->matrix[$role->name][self::key($perm)] = in_array($perm, $owned, true);
            }
        }
    }

    /** Mencentang "Kelola" otomatis mencentang "Lihat"; mencabut "Lihat" mencabut "Kelola". */
    public function updatedMatrix($value, string $key): void
    {
        [$roleName, $permKey] = explode('.', $key, 2);

        foreach (AccessPages::all() as $page) {
            if ($value && $page['manage'] && $permKey === self::key($page['manage'])) {
                $this->matrix[$roleName][self::key($page['view'])] = true;
            }
            if (! $value && $page['manage'] && $permKey === self::key($page['view'])) {
                $this->matrix[$roleName][self::key($page['manage'])] = false;
            }
        }
    }

    public function save(): void
    {
        Gate::authorize('users.manage');

        $known = AccessPages::permissions();
        foreach ($known as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        foreach ($this->editableRoles() as $role) {
            $row = $this->matrix[$role->name] ?? [];
            $granted = array_values(array_filter($known, fn ($perm) => ! empty($row[self::key($perm)])));

            // Permission di luar daftar halaman (bila ada) tidak ikut dicabut.
            $other = $role->permissions->pluck('name')->diff($known)->all();

            $role->syncPermissions(array_merge($granted, $other));
        }

        $this->loadMatrix();
        session()->flash('status', 'Hak akses role tersimpan. Perubahan berlaku saat user membuka halaman berikutnya.');
    }

    public function render()
    {
        return view('livewire.roles.index', [
            'pages' => AccessPages::all(),
            'roles' => $this->editableRoles(),
        ]);
    }
}
