<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Manajemen User', 'subtitle' => 'Kelola akun & role Admin / Sales / Logistik.'])]
class Index extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = User::ROLE_SALES;

    public function mount(): void
    {
        Gate::authorize('users.manage');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->getRoleNames()->first() ?? User::ROLE_SALES;
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('users.manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$this->editingId],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'in:'.implode(',', [User::ROLE_ADMIN, User::ROLE_SALES, User::ROLE_LOGISTIK])],
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                ...($data['password'] ? ['password' => bcrypt($data['password'])] : []),
            ]);
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'email_verified_at' => now(),
            ]);
        }

        $user->syncRoles([$data['role']]);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('status', 'Data user tersimpan.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('users.manage');

        if ($id === auth()->id()) {
            session()->flash('status', 'Tidak bisa menghapus akun Anda sendiri.');

            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('status', 'User dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = User::ROLE_SALES;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(15),
        ]);
    }
}
