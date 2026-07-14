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
Schema::create('purchases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    
    // What was purchased — polymorphic
    // can be editorial, book or chapter
    $table->morphs('purchasable');
    
    // Token cost at time of purchase
    $table->unsignedInteger('tokens_spent');
    
    // Publisher who earned
    $table->foreignId('publisher_id')
        ->constrained('users')
        ->cascadeOnDelete();
    
    // Publisher earnings from this purchase
    $table->unsignedInteger('publisher_earned');
    
    // Platform cut
    $table->unsignedInteger('platform_cut')->default(0);
    
    // Access control
    $table->timestamp('access_expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    
    $table->timestamps();
    
    $table->unique(['user_id', 'purchasable_type', 'purchasable_id']);
    $table->index('user_id');
    $table->index('publisher_id');
    $table->index('is_active');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
