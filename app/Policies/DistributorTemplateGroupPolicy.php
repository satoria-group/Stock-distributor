<?php

namespace App\Policies;

use App\Models\DistributorTemplateGroup;
use App\Models\User;

class DistributorTemplateGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('template-groups.view');
    }

    public function view(User $user, DistributorTemplateGroup $group): bool
    {
        return $user->can('template-groups.view');
    }

    public function create(User $user): bool
    {
        return $user->can('template-groups.manage');
    }

    public function update(User $user, DistributorTemplateGroup $group): bool
    {
        return $user->can('template-groups.manage');
    }

    public function delete(User $user, DistributorTemplateGroup $group): bool
    {
        return $user->can('template-groups.manage');
    }
}
