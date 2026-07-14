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
    Schema::create('message_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Who can message this user
        $table->enum('who_can_message', [
            'anyone',
            'community_members',
            'selected_users',
            'staff_only',
        ])->default('anyone');

        // Manually selected users (JSON array of user IDs)
        $table->json('allowed_user_ids')->default('[]');

        // Blocked users (JSON array of user IDs)
        $table->json('blocked_user_ids')->default('[]');

        $table->timestamps();

        $table->unique('user_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_settings');
    }
};
