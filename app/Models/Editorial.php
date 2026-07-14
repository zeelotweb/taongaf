<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Editorial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'primary_format',
        'cover_image',
        'status',
        'published_at',
        'visibility',
        'token_price',
        'views_count',
        'reads_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'token_price' => 'integer',
            'views_count' => 'integer',
            'reads_count' => 'integer',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFree($query)
    {
        return $query->where('visibility', 'free');
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('primary_format', $format);
    }

    // Helpers
    public function isFree(): bool
    {
        return $this->visibility === 'free';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function reactions()
{
    return $this->morphMany(Reaction::class, 'reactable');
}

public function comments()
{
    return $this->morphMany(Comment::class, 'commentable')->latest();
}

public function bookmarks()
{
    return $this->morphMany(Bookmark::class, 'bookmarkable');
}
}