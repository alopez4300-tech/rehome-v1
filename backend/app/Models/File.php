<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'workspace_id',
        'project_id',
        'name',
        'original_name',
        'path',
        'category',
        'mime_type',
        'size',
        'uploaded_by',
        'ocr_path',
        'thumbnail_path',
        'embedding_path',
        'has_ocr',
        'has_thumbnail',
        'has_embedding',
        'metadata',
    ];

    protected $casts = [
        'has_ocr' => 'boolean',
        'has_thumbnail' => 'boolean',
        'has_embedding' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (File $file) {
            $file->uuid = Str::uuid();
        });

        // Global workspace scope
        static::addGlobalScope('workspace', function (Builder $builder) {
            if (auth()->check() && auth()->user()->current_workspace_id) {
                $builder->where('workspace_id', auth()->user()->current_workspace_id);
            }
        });
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeWithOcr(Builder $query): Builder
    {
        return $query->where('has_ocr', true);
    }

    public function scopeWithThumbnails(Builder $query): Builder
    {
        return $query->where('has_thumbnail', true);
    }

    // Helper methods
    public function getDownloadUrlAttribute(): string
    {
        return route('api.files.download', $this->uuid);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->has_thumbnail ? route('api.files.thumbnail', $this->uuid) : null;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ]);
    }
}
