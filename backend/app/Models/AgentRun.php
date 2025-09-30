<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'status',
        'provider',
        'model',
        'tokens_in',
        'tokens_out',
        'cost_cents',
        'context_used',
        'started_at',
        'finished_at',
        'error',
    ];

    protected $casts = [
        'context_used' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'cost_cents' => 'integer',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(AgentThread::class, 'thread_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function getTotalCostAttribute(): float
    {
        return ($this->cost_cents ?? 0) / 100;
    }

    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
            'error' => $error,
        ]);
    }
}
