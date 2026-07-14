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
    Schema::create('referral_sessions', function (Blueprint $table) {
        $table->id();

        // Visitor — nullable if guest
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

        // Profile they visited
        $table->foreignId('profile_owner_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Hustler who owns the referral link (if link based)
        $table->foreignId('hustler_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // Tracking
        $table->string('session_token')->unique();
        $table->enum('source', ['link', 'visit'])->default('visit');
        $table->timestamp('expires_at');
        $table->boolean('converted')->default(false);
        $table->timestamp('converted_at')->nullable();

        $table->timestamps();

        $table->index('session_token');
        $table->index('user_id');
        $table->index('profile_owner_id');
        $table->index('expires_at');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_sessions');
    }
};
