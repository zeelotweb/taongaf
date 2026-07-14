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
    Schema::create('profile_commerce_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Commerce enabled
        $table->boolean('is_enabled')->default(false);

        // Which service types are allowed — agnostic array
        $table->json('allowed_services')->default('[]');

        // Rates
        $table->unsignedInteger('flat_promotion_price')->default(50);

        // Unlock status
        $table->boolean('is_unlocked')->default(false);
        $table->timestamp('unlocked_at')->nullable();

        // Publisher opt in/out of being hustled
        $table->boolean('allow_hustlers')->default(true);

        $table->timestamps();

        $table->unique('user_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_commerce_settings');
    }
};
