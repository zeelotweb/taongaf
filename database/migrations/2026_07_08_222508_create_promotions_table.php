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
    Schema::create('promotions', function (Blueprint $table) {
        $table->id();

        // Who is promoting
        $table->foreignId('hustler_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Whose profile/community
        $table->foreignId('profile_owner_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // What is being promoted — agnostic
        $table->morphs('promotable');

        // Service type — from config service_types
        $table->string('service_type')->default('promotion');

        // Flat fee paid to promote
        $table->unsignedInteger('tokens_paid')->default(0);

        // Status
        $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'completed'])
            ->default('pending');

        // Scheduling
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('ends_at')->nullable();

        // Split tracking
        $table->unsignedInteger('hustler_earned')->default(0);
        $table->unsignedInteger('profile_owner_earned')->default(0);
        $table->unsignedInteger('platform_earned')->default(0);

        $table->timestamps();
        $table->softDeletes();

        $table->index('hustler_id');
        $table->index('profile_owner_id');
        $table->index('status');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
