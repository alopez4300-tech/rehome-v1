<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Admins can do everything
        if ($user->hasRole('admin')) {
            return true;
        }

        // Continue to specific policy methods
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only admin can view users list
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admin can view users in their workspace, or user can view themselves
        if ($user->hasRole('admin')) {
            return $user->workspace_id === $model->workspace_id;
        }

        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin can create users
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admin can update users in their workspace, or user can update themselves
        if ($user->hasRole('admin')) {
            return $user->workspace_id === $model->workspace_id;
        }

        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Admin can delete users in their workspace, but not themselves
        return $user->hasRole('admin') &&
               $user->workspace_id === $model->workspace_id &&
               $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Only admin can restore users in their workspace
        return $user->hasRole('admin') && $user->workspace_id === $model->workspace_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Admin can force delete users in their workspace, but not themselves
        return $user->hasRole('admin') &&
               $user->workspace_id === $model->workspace_id &&
               $user->id !== $model->id;
    }
}
