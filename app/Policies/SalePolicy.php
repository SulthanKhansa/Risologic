<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine if the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Staff and Admin can view sales
        return $user->hasRole(['staff', 'admin']);
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, Sale $sale): bool
    {
        // Staff and Admin can view
        return $user->hasRole(['staff', 'admin']);
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Staff and Admin can create
        return $user->hasRole(['staff', 'admin']);
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, Sale $sale): bool
    {
        // Only Admin can update sales (Staff cannot)
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, Sale $sale): bool
    {
        // Only Admin can delete sales (Staff cannot)
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can restore the model.
     */
    public function restore(User $user, Sale $sale): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can permanently delete the model.
     */
    public function forceDelete(User $user, Sale $sale): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can replicate the model.
     */
    public function replicate(User $user, Sale $sale): bool
    {
        return $user->hasRole('admin');
    }
}
