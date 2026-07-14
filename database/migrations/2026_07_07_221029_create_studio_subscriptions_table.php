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
    Schema::create('studio_subscriptions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Stripe
        $table->string('stripe_subscription_id')->nullable();
        $table->string('stripe_price_id')->nullable();

        // Plan
        $table->enum('plan', ['basic', 'pro'])->default('basic');
        $table->decimal('price_usd', 8, 2);

        // Status
        $table->enum('status', ['active', 'cancelled', 'past_due', 'trialing'])->default('active');
        $table->timestamp('trial_ends_at')->nullable();
        $table->timestamp('current_period_ends_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();

        $table->timestamps();

        $table->index('user_id');
        $table->index('status');
        $table->index('stripe_subscription_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studio_subscriptions');
    }
};
