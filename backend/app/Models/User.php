<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'workspace_id',
        'role',
        'is_active',
        'last_active_at',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    /**
     * Get the workspace that the user belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get all projects the user is assigned to.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot([
                'role',
                'can_manage_tasks',
                'can_manage_files',
                'can_manage_users',
                'can_view_budget',
                'hourly_rate',
                'joined_at',
                'left_at',
            ])
            ->withTimestamps();
    }

    /**
     * Get projects where user is an owner.
     */
    public function ownedProjects(): BelongsToMany
    {
        return $this->projects()->wherePivot('role', 'owner');
    }

    /**
     * Get projects where user is a manager.
     */
    public function managedProjects(): BelongsToMany
    {
        return $this->projects()->wherePivot('role', 'manager');
    }

    /**
     * Check if user is workspace admin.
     */
    public function isWorkspaceAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can access project.
     */
    public function canAccessProject(Project $project): bool
    {
        if ($this->isWorkspaceAdmin() && $this->workspace_id === $project->workspace_id) {
            return true;
        }

        return $this->projects()->where('project_id', $project->id)->exists();
    }

    /**
     * Update last active timestamp.
     */
    public function updateLastActive(): void
    {
        $this->update(['last_Active_at' => now()]);
    }

    /**
     * Get all agent threads created by this user.
     */
    public function agentThreads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgentThread::class);
    }

    /**
     * Get participant agent threads (only for projects this user is assigned to).
     */
    public function participantAgentThreads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgentThread::class)
                    ->where('audience', 'participant')
                    ->whereHas('project.users', function ($query) {
                        $query->where('users.id', $this->id);
                    });
    }

    /**
     * Get admin agent threads (workspace-wide scope).
     */
    public function adminAgentThreads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgentThread::class)
                    ->where('audience', 'admin')
                    ->whereHas('project', function ($query) {
                        $query->where('workspace_id', $this->workspace_id);
                    });
    }
}
