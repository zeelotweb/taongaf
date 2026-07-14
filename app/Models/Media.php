<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mediable_type',
        'mediable_id',
        'disk',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'type',
        'mux_asset_id',
        'mux_playback_id',
        'duration',
        'thumbnail_url',
        'title',
        'description',
        'sort_order',
        'is_processed',
        'is_failed',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
            'is_processed' => 'boolean',
            'is_failed' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mediable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helpers
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    public function isPdf(): bool
    {
        return $this->type === 'pdf';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function hasMux(): bool
    {
        return !is_null($this->mux_asset_id);
    }

    public function streamUrl(): ?string
    {
        if (!$this->mux_playback_id) return null;
        return "https://stream.mux.com/{$this->mux_playback_id}.m3u8";
    }

    public function formattedSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
}