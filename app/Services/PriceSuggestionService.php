<?php

namespace App\Services;

use App\Models\PublisherMetrics;
use App\Models\User;

class PriceSuggestionService
{
    /**
     * Calculate price cap based on publisher metrics
     */
    public function calculatePriceCap(PublisherMetrics $metrics): int
    {
        $base = 10; // minimum cap for all publishers

        // Content bonus — more content = higher cap
        $contentBonus = min($metrics->content_count * 2, 50);

        // Engagement bonus
        $engagementBonus = min((int) ($metrics->engagement_rate * 100), 100);

        // Follower bonus
        $followerBonus = min((int) ($metrics->follower_count * 0.5), 200);

        // Retention bonus — loyal subscribers = higher cap
        $retentionBonus = min((int) ($metrics->retention_rate * 2), 100);

        $cap = $base + $contentBonus + $engagementBonus + $followerBonus + $retentionBonus;

        // Hard cap at 500 tokens/month
        return min($cap, 500);
    }

    /**
     * Suggest community subscription price
     */
    public function suggestCommunityPrice(PublisherMetrics $metrics): int
    {
        // Find similar publishers
        $similar = PublisherMetrics::where('id', '!=', $metrics->id)
            ->whereBetween('follower_count', [
                max(0, $metrics->follower_count - 100),
                $metrics->follower_count + 100,
            ])
            ->whereBetween('engagement_rate', [
                max(0, $metrics->engagement_rate - 0.1),
                $metrics->engagement_rate + 0.1,
            ])
            ->get();

        if ($similar->isEmpty()) {
            // No similar publishers — suggest based on cap
            return (int) max(5, $metrics->price_cap * 0.2);
        }

        // Get median price of similar publishers
        $prices = $similar
            ->filter(fn($m) => $m->suggested_community_price > 0)
            ->pluck('suggested_community_price')
            ->sort()
            ->values();

        if ($prices->isEmpty()) {
            return (int) max(5, $metrics->price_cap * 0.2);
        }

        $median = $prices->count() % 2 === 0
            ? ($prices[$prices->count() / 2 - 1] + $prices[$prices->count() / 2]) / 2
            : $prices[(int) ($prices->count() / 2)];

        // New publishers — suggest slightly below median for competitive edge
        if ($metrics->follower_count < 50) {
            return (int) max(5, $median * 0.85);
        }

        // Established publishers — suggest at or slightly above median
        if ($metrics->follower_count > 500) {
            return (int) min($metrics->price_cap, $median * 1.1);
        }

        return (int) $median;
    }

    /**
     * Suggest studio plan based on metrics
     */
    public function suggestStudioPlan(PublisherMetrics $metrics): string
    {
        // Pro if high engagement and content volume
        if (
            $metrics->content_count >= 10 &&
            $metrics->engagement_rate >= 0.05 &&
            $metrics->follower_count >= 100
        ) {
            return 'pro';
        }

        return 'basic';
    }

    /**
     * Get full suggestion package for a publisher
     */
    public function getSuggestions(User $publisher): array
    {
        $metrics = $publisher->publisherMetrics
            ?? new PublisherMetrics(['user_id' => $publisher->id]);

        $priceCap        = $this->calculatePriceCap($metrics);
        $communityPrice  = $this->suggestCommunityPrice($metrics);
        $studioPlan      = $this->suggestStudioPlan($metrics);

        // Breakdown — transparent reasoning
        $breakdown = [
            'price_cap' => [
                'value'   => $priceCap,
                'factors' => [
                    'content'    => min($metrics->content_count * 2, 50),
                    'engagement' => min((int) ($metrics->engagement_rate * 100), 100),
                    'followers'  => min((int) ($metrics->follower_count * 0.5), 200),
                    'retention'  => min((int) ($metrics->retention_rate * 2), 100),
                ],
            ],
            'community_price' => [
                'value'  => $communityPrice,
                'reason' => $metrics->follower_count < 50
                    ? 'Competitive entry price to grow your audience'
                    : ($metrics->follower_count > 500
                        ? 'Premium pricing reflecting your established audience'
                        : 'Based on similar publishers in your category'),
            ],
            'studio_plan' => [
                'value'  => $studioPlan,
                'reason' => $studioPlan === 'pro'
                    ? 'Your content volume and engagement suggest Pro features'
                    : 'Basic plan suits your current publishing stage',
            ],
        ];

        return $breakdown;
    }
}