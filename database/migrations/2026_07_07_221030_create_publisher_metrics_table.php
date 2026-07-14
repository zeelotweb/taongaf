<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('publisher_metrics', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Content
        $table->unsignedInteger('content_count')->default(0);
        $table->unsignedBigInteger('total_views')->default(0);
        $table->unsignedBigInteger('total_reads')->default(0);

        // Engagement
        $table->unsignedBigInteger('total_reactions')->default(0);
        $table->unsignedBigInteger('total_comments')->default(0);
        $table->unsignedBigInteger('total_bookmarks')->default(0);
        $table->decimal('engagement_rate', 8, 4)->default(0);

        // Community
        $table->unsignedInteger('follower_count')->default(0);
        $table->unsignedInteger('subscriber_count')->default(0);
        $table->decimal('retention_rate', 5, 2)->default(0);

        // Earnings
        $table->unsignedBigInteger('total_token_earnings')->default(0);
        $table->unsignedBigInteger('monthly_token_earnings')->default(0);

        // Pricing
        $table->unsignedInteger('suggested_community_price')->default(0);
        $table->unsignedInteger('price_cap')->default(10);
        $table->string('suggested_studio_plan')->default('basic');

        // Calculated at
        $table->timestamp('calculated_at')->nullable();

        $table->timestamps();

        $table->unique('user_id');
        $table->index('price_cap');
        $table->index('engagement_rate');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publisher_metrics');
    }
};
