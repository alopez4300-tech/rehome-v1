<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasRoleHelpers
{
    /**
     * Check if user has the system-admin role (global access)
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system-admin');
    }

    /**
     * Get all workspace memberships for this user
     */
    public function workspaceMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // Ensure model + table name are correct
        return $this->hasMany(\App\Models\WorkspaceMember::class, 'user_id', 'id');
    }

    /**
     * Check if user is admin/owner of a specific workspace (or current workspace)
     */
    public function isWorkspaceAdmin(?int $workspaceId = null): bool
    {
        // canonical: current_workspace_id
        $wid = $workspaceId ?? $this->getAttribute('current_workspace_id');

        if (empty($wid)) {
            return false;
        }

        return \App\Models\WorkspaceMember::query()
            ->where('workspace_id', $wid)
            ->where('user_id', $this->getKey())
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Get user's role in a specific workspace
     */
    public function getWorkspaceRole(?int $workspaceId = null): ?string
    {
        $wid = $workspaceId ?? $this->current_workspace_id;
        if (!$wid) {
            return null;
        }

        $membership = $this->workspaceMemberships()
            ->where('workspace_id', $wid)
            ->first();

        return $membership?->role;
    }

    /**
     * Check if user has any workspace admin privileges
     */
    public function hasWorkspaceAdminAccess(): bool
    {
        return $this->workspaceMemberships()
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Debug method to test workspace admin functionality
     */
    public function debugWorkspaceAdmin(): array
    {
        $wid = $this->getAttribute('current_workspace_id');

        return [
            'user_id' => $this->getKey(),
            'current_workspace_id' => $wid,
            'direct_query' => \App\Models\WorkspaceMember::query()
                ->where('workspace_id', $wid)
                ->where('user_id', $this->getKey())
                ->whereIn('role', ['owner', 'admin'])
                ->exists(),
            'method_result' => $this->isWorkspaceAdmin(),
        ];
    }

    /**
     * Test method with different name to avoid conflicts
     */
    public function testWorkspaceAdminAccess(?int $workspaceId = null): bool
    {
        $wid = $workspaceId ?? $this->getAttribute('current_workspace_id');

        if (empty($wid)) {
            return false;
        }

        return \App\Models\WorkspaceMember::query()
            ->where('workspace_id', $wid)
            ->where('user_id', $this->getKey())
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }
}
