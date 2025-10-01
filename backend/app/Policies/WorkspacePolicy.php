<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
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
        // Only admin role can view workspaces list
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        // Admin can view their workspace, or if they have admin role
        return $user->hasRole('admin') && $user->workspace_id === $workspace->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin role can create workspaces
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        // Admin can update their workspace
        return $user->hasRole('admin') && $user->workspace_id === $workspace->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        // Admin can delete workspace only if it has no projects
        return $user->hasRole('admin') &&
               $user->workspace_id === $workspace->id &&
               $workspace->projects()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        // Admin can restore workspace
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        // Admin can force delete workspace only if it has no projects
        return $user->hasRole('admin') &&
               $user->workspace_id === $workspace->id &&
               $workspace->projects()->count() === 0;
    }
}
