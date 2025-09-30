<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'due_date',
        'position',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeForWorkspace(Builder $query, int $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    // Global scope for workspace filtering
    protected static function booted()
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            if (auth()->check() && auth()->user()->current_workspace_id) {
                $builder->where('workspace_id', auth()->user()->current_workspace_id);
            }
        });
    }
}
