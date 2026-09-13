<?php

namespace App\Policies;

use App\Models\StockEntry;
use App\Models\User;

class StockEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.view');
    }

    public function view(User $user, StockEntry $entry): bool
    {
        return $user->can('stock.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.upload');
    }

    public function update(User $user, StockEntry $entry): bool
    {
        return $user->can('stock.upload');
    }

    public function delete(User $user, ?StockEntry $entry = null): bool
    {
        return $user->can('stock.upload');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('stock.upload');
    }
}
