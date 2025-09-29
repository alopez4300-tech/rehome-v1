<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'permissions',
        'invited_at',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Role checks based on 3-surface architecture
    public function isTeam(): bool
    {
        return $this->role === 'team';
    }

    public function isConsultant(): bool
    {
        return $this->role === 'consultant';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['team', 'consultant']);
    }

    public function canViewAssets(): bool
    {
        return true; // All project members can view assets
    }

    public function canDownloadAssets(): bool
    {
        return in_array($this->role, ['team', 'consultant', 'client']);
    }
}
