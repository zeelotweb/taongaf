<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'book_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'primary_format',
        'sort_order',
        'status',
        'published_at',
        'is_free_preview',
        'visibility',
        'token_price',
        'views_count',
        'reads_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_free_preview' => 'boolean',
            'token_price' => 'integer',
            'views_count' => 'integer',
            'reads_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

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

    public function scopeFreePreview($query)
    {
        return $query->where('is_free_preview', true);
    }

    // Helpers
    public function isFree(): bool
    {
        return $this->visibility === 'free' || $this->is_free_preview;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($this->isFree()) return true;
        if ($user->isAdmin()) return true;
        if ($user->id === $this->user_id) return true;
        return $user->hasPurchased($this) || $user->hasPurchased($this->book);
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