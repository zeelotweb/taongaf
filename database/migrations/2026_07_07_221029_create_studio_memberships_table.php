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
    Schema::create('studio_memberships', function (Blueprint $table) {
        $table->id();

        // Publisher who owns the studio
        $table->foreignId('publisher_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Staff member
        $table->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        // Roles — stored as JSON array
        // ['content_manager', 'reaction_moderator', 'community_moderator', 'content_analyst']
        $table->json('roles')->default('[]');

        // Status
        $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
        $table->string('invite_token')->nullable()->unique();
        $table->timestamp('invited_at')->nullable();
        $table->timestamp('joined_at')->nullable();

        $table->timestamps();

        $table->unique(['publisher_id', 'user_id']);
        $table->index('publisher_id');
        $table->index('user_id');
        $table->index('invite_token');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studio_memberships');
    }
};
