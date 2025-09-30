<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'role' => 'string',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(AgentThread::class, 'thread_id');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }
}
