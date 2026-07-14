<?php

namespace App\Jobs;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\CommunityMember;
use App\Models\PublisherMetrics;
use App\Models\Reaction;
use App\Models\User;
use App\Services\PriceSuggestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePublisherMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $userId) {}

    public function handle(): void
    {
        $user    = User::findOrFail($this->userId);
        $service = new PriceSuggestionService();

        // Gather all content
        $editorialIds = $user->editorials()->published()->pluck('id');
        $bookIds      = $user->books()->published()->pluck('id');
        $chapterIds   = \App\Models\Chapter::whereIn('book_id', $bookIds)
            ->published()->pluck('id');

        $contentCount = $editorialIds->count()
            + $bookIds->count()
            + $chapterIds->count();

        // Views and reads
        $totalViews = $user->editorials()->sum('views_count')
            + $user->books()->sum('views_count');

        $totalReads = $user->editorials()->sum('reads_count')
            + $user->books()->sum('reads_count');

        // Reactions
        $totalReactions = Reaction::where(function ($q) use ($editorialIds, $chapterIds) {
            $q->whereIn('reactable_id', $editorialIds)
              ->where('reactable_type', 'App\Models\Editorial');
        })->orWhere(function ($q) use ($chapterIds) {
            $q->whereIn('reactable_id', $chapterIds)
              ->where('reactable_type', 'App\Models\Chapter');
        })->count();

        // Comments
        $totalComments = Comment::where(function ($q) use ($editorialIds) {
            $q->whereIn('commentable_id', $editorialIds)
              ->where('commentable_type', 'App\Models\Editorial');
        })->orWhere(function ($q) use ($chapterIds) {
            $q->whereIn('commentable_id', $chapterIds)
              ->where('commentable_type', 'App\Models\Chapter');
        })->count();

        // Bookmarks
        $totalBookmarks = Bookmark::where(function ($q) use ($editorialIds) {
            $q->whereIn('bookmarkable_id', $editorialIds)
              ->where('bookmarkable_type', 'App\Models\Editorial');
        })->orWhere(function ($q) use ($bookIds) {
            $q->whereIn('bookmarkable_id', $bookIds)
              ->where('bookmarkable_type', 'App\Models\Book');
        })->count();

        // Engagement rate
        $totalEngagements = $totalReactions + $totalComments + $totalBookmarks;
        $engagementRate   = $totalViews > 0
            ? round($totalEngagements / $totalViews, 4)
            : 0;

        // Community
        $followerCount   = CommunityMember::where('publisher_id', $user->id)
            ->where('status', 'active')->count();

        $subscriberCount = CommunityMember::where('publisher_id', $user->id)
            ->where('type', 'subscribed')
            ->where('status', 'active')->count();

        // Retention rate
        $lastMonthSubscribers = CommunityMember::where('publisher_id', $user->id)
            ->where('type', 'subscribed')
            ->where('subscribed_at', '<=', now()->subMonth())
            ->count();

        $retentionRate = $lastMonthSubscribers > 0
            ? round(($subscriberCount / $lastMonthSubscribers) * 100, 2)
            : 0;

        // Earnings
        $totalEarnings   = $user->wallet?->total_earned ?? 0;
        $monthlyEarnings = \App\Models\TokenTransaction::where('user_id', $user->id)
            ->where('type', 'earn')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        // Update or create metrics
        $metrics = PublisherMetrics::updateOrCreate(
            ['user_id' => $user->id],
            [
                'content_count'          => $contentCount,
                'total_views'            => $totalViews,
                'total_reads'            => $totalReads,
                'total_reactions'        => $totalReactions,
                'total_comments'         => $totalComments,
                'total_bookmarks'        => $totalBookmarks,
                'engagement_rate'        => $engagementRate,
                'follower_count'         => $followerCount,
                'subscriber_count'       => $subscriberCount,
                'retention_rate'         => $retentionRate,
                'total_token_earnings'   => $totalEarnings,
                'monthly_token_earnings' => $monthlyEarnings,
                'calculated_at'          => now(),
            ]
        );

        // Get price suggestions
        $suggestions = $service->getSuggestions($user);

        // Update pricing suggestions
        $metrics->update([
            'price_cap'                => $suggestions['price_cap']['value'],
            'suggested_community_price' => $suggestions['community_price']['value'],
            'suggested_studio_plan'    => $suggestions['studio_plan']['value'],
        ]);
    }
}