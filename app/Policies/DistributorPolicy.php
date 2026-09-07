<?php

namespace App\Policies;

use App\Models\Distributor;
use App\Models\User;

class DistributorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('distributors.view');
    }

    public function view(User $user, Distributor $distributor): bool
    {
        return $user->can('distributors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('distributors.manage');
    }

    public function update(User $user, Distributor $distributor): bool
    {
        return $user->can('distributors.manage');
    }

    public function delete(User $user, Distributor $distributor): bool
    {
        return $user->can('distributors.manage');
    }
}
