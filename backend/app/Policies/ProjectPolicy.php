<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // System admins can do everything
        if ($user->hasRole('system-admin')) {
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
        // Admin can view projects, or any user with project memberships
        return $user->hasRole('system-admin') || $user->hasAnyRole(['team', 'consultant', 'client']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Admin can view projects in their workspace, or user must be project member
        if ($user->hasRole('system-admin')) {
            return $user->workspace_id === $project->workspace_id;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin can create projects
        return $user->hasRole('system-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Admin can update projects in their workspace
        if ($user->hasRole('system-admin')) {
            return $user->workspace_id === $project->workspace_id;
        }

        // Team members can update projects they're assigned to
        $membership = $project->members()->where('user_id', $user->id)->first();
        return $membership && in_array($membership->role, ['team', 'consultant']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // Only admin can delete projects in their workspace
        return $user->hasRole('system-admin') && $user->workspace_id === $project->workspace_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        // Only admin can restore projects in their workspace
        return $user->hasRole('system-admin') && $user->workspace_id === $project->workspace_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        // Only admin can force delete projects in their workspace
        return $user->hasRole('system-admin') && $user->workspace_id === $project->workspace_id;
    }
}
