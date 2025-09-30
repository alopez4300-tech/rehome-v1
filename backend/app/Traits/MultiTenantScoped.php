<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Multi-tenant query scoping trait that only applies when multi-tenancy is enabled
 */
trait MultiTenantScoped
{
    /**
     * Boot the multi-tenant scoped trait for a model.
     */
    protected static function bootMultiTenantScoped(): void
    {
        if (!feature('multi_tenant')) {
            return;
        }

        static::addGlobalScope('workspace', function (Builder $builder) {
            $workspaceId = self::getCurrentWorkspaceId();
            
            if ($workspaceId !== null) {
                $builder->where('workspace_id', $workspaceId);
            }
        });
    }

    /**
     * Get the current workspace ID from context
     */
    protected static function getCurrentWorkspaceId(): ?int
    {
        // Check if we're in a Filament context
        if (class_exists(\Filament\Facades\Filament::class)) {
            $tenant = \Filament\Facades\Filament::getTenant();
            if ($tenant && method_exists($tenant, 'getKey')) {
                return $tenant->getKey();
            }
        }

        // Check session for workspace context
        if (Session::has('current_workspace_id')) {
            return Session::get('current_workspace_id');
        }

        // Check authenticated user's default workspace
        if (Auth::check() && Auth::user()->default_workspace_id) {
            return Auth::user()->default_workspace_id;
        }

        return null;
    }

    /**
     * Scope query to a specific workspace (bypasses global scope)
     */
    public function scopeInWorkspace(Builder $query, $workspaceId): Builder
    {
        if (!feature('multi_tenant')) {
            return $query;
        }

        return $query->withoutGlobalScope('workspace')->where('workspace_id', $workspaceId);
    }

    /**
     * Scope query to exclude workspace filtering (admin access)
     */
    public function scopeAllWorkspaces(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }

    /**
     * Get all records across workspaces (requires system admin)
     */
    public static function allWorkspaces()
    {
        if (!Auth::user()?->isSystemAdmin()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('System admin access required');
        }

        return static::withoutGlobalScope('workspace');
    }
}