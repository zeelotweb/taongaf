<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'synopsis',
        'genre',
        'has_text',
        'has_audio',
        'has_video',
        'has_pdf',
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
            'has_text' => 'boolean',
            'has_audio' => 'boolean',
            'has_video' => 'boolean',
            'has_pdf' => 'boolean',
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

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('sort_order');
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

    public function scopeByGenre($query, $genre)
    {
        return $query->where('genre', $genre);
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

    public function availableFormats(): array
    {
        $formats = [];
        if ($this->has_text) $formats[] = 'text';
        if ($this->has_audio) $formats[] = 'audio';
        if ($this->has_video) $formats[] = 'video';
        if ($this->has_pdf) $formats[] = 'pdf';
        return $formats;
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