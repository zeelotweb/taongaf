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
    Schema::create('community_members', function (Blueprint $table) {
        $table->id();

        // Publisher whose community
        $table->foreignId('publisher_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Member
        $table->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        // Access type
        $table->enum('type', ['open', 'approved', 'subscribed'])->default('open');

        // Subscription details if subscribed
        $table->unsignedInteger('token_price')->nullable();
        $table->timestamp('subscribed_at')->nullable();
        $table->timestamp('subscription_ends_at')->nullable();
        $table->boolean('auto_renew')->default(true);

        // Status
        $table->enum('status', ['pending', 'active', 'blocked', 'expired'])->default('pending');
        $table->timestamp('blocked_at')->nullable();
        $table->string('block_reason')->nullable();

        $table->timestamps();

        $table->unique(['publisher_id', 'user_id']);
        $table->index('publisher_id');
        $table->index('user_id');
        $table->index('status');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_members');
    }
};
