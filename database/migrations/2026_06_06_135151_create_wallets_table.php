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
Schema::create('wallets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    
    // Balances
    $table->unsignedBigInteger('token_balance')->default(0);
    $table->unsignedBigInteger('earnings_balance')->default(0);
    $table->unsignedBigInteger('total_earned')->default(0);
    $table->unsignedBigInteger('total_spent')->default(0);
    
    // Stripe for payouts
    $table->string('stripe_account_id')->nullable();
    $table->boolean('payouts_enabled')->default(false);
    $table->timestamp('last_payout_at')->nullable();
    
    $table->timestamps();
    
    $table->unique('user_id');
    $table->index('stripe_account_id');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
