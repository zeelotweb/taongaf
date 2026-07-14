<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublisherMetrics extends Model
{
    protected $fillable = [
        'user_id',
        'content_count',
        'total_views',
        'total_reads',
        'total_reactions',
        'total_comments',
        'total_bookmarks',
        'engagement_rate',
        'follower_count',
        'subscriber_count',
        'retention_rate',
        'total_token_earnings',
        'monthly_token_earnings',
        'suggested_community_price',
        'price_cap',
        'suggested_studio_plan',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'engagement_rate' => 'decimal:4',
            'retention_rate'  => 'decimal:2',
            'calculated_at'   => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}