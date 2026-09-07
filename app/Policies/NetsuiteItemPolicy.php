<?php

namespace App\Policies;

use App\Models\NetsuiteItem;
use App\Models\User;

class NetsuiteItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('netsuite-items.view');
    }

    public function view(User $user, NetsuiteItem $item): bool
    {
        return $user->can('netsuite-items.view');
    }

    public function create(User $user): bool
    {
        return $user->can('netsuite-items.manage');
    }

    public function update(User $user, NetsuiteItem $item): bool
    {
        return $user->can('netsuite-items.manage');
    }

    public function delete(User $user, NetsuiteItem $item): bool
    {
        return $user->can('netsuite-items.manage');
    }
}
