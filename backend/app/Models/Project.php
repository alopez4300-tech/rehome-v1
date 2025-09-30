<?php

namespace App\Models;

use App\Traits\MultiTenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, MultiTenantScoped;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'status',
        'priority',
        'budget',
        'start_date',
        'end_date',
        'deadline',
        'metadata',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deadline' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the workspace that owns the project.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get all users assigned to this project.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
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
     * Get project owners.
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'owner');
    }

    /**
     * Get project managers.
     */
    public function managers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'manager');
    }

    /**
     * Get project members.
     */
    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'member');
    }

    /**
     * Get project consultants.
     */
    public function consultants(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'consultant');
    }

    /**
     * Get project clients.
     */
    public function clients(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'client');
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'completed';
    }

    /**
     * Get project progress percentage.
     */
    public function getProgressPercentage(): int
    {
        // This would be calculated based on tasks completion
        // For now, return a simple calculation based on dates
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        $total = $this->start_date->diffInDays($this->end_date);
        $elapsed = $this->start_date->diffInDays(now());

        if ($elapsed <= 0) {
            return 0;
        }

        if ($elapsed >= $total) {
            return 100;
        }

        return (int) (($elapsed / $total) * 100);
    }

    /**
     * Get all agent threads for this project.
     */
    public function agentThreads(): HasMany
    {
        return $this->hasMany(AgentThread::class);
    }

    /**
     * Get participant agent threads (scoped to assigned project members).
     */
    public function participantThreads(): HasMany
    {
        return $this->hasMany(AgentThread::class)->where('audience', 'participant');
    }

    /**
     * Get admin agent threads (workspace-wide scope).
     */
    public function adminThreads(): HasMany
    {
        return $this->hasMany(AgentThread::class)->where('audience', 'admin');
    }
}
