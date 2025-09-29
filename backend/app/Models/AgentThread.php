<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id', 
        'audience',
        'title',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'audience' => 'string'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentMessage::class, 'thread_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AgentRun::class, 'thread_id');
    }

    public function scopeForParticipant($query, User $user)
    {
        return $query->where('audience', 'participant')
                    ->whereHas('project.users', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
    }

    public function scopeForAdmin($query, User $user)
    {
        return $query->where('audience', 'admin')
                    ->whereHas('project', function ($q) use ($user) {
                        $q->where('workspace_id', $user->workspace_id);
                    });
    }
}
