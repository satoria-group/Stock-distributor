<?php

namespace App\Policies;

use App\Models\DistributorItem;
use App\Models\User;

class DistributorItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('distributor-items.view');
    }

    public function view(User $user, DistributorItem $item): bool
    {
        return $user->can('distributor-items.view');
    }

    public function create(User $user): bool
    {
        return $user->can('distributor-items.manage');
    }

    public function update(User $user, DistributorItem $item): bool
    {
        return $user->can('distributor-items.manage');
    }

    public function delete(User $user, DistributorItem $item): bool
    {
        return $user->can('distributor-items.manage');
    }
}
