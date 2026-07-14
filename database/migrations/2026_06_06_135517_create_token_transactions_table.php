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
Schema::create('token_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
    
    // Transaction type
    $table->enum('type', [
        'purchase',      // user bought tokens with real money
        'spend',         // user spent tokens on content
        'earn',          // publisher earned tokens from content
        'refund',        // tokens refunded
        'payout',        // earnings converted to real money
        'bonus'          // free tokens granted by admin
    ]);
    
    // Amount
    $table->unsignedBigInteger('amount');
    $table->enum('direction', ['credit', 'debit']);
    
    // Balance snapshot
    $table->unsignedBigInteger('balance_before');
    $table->unsignedBigInteger('balance_after');
    
    // Reference
    $table->nullableMorphs('transactionable');
    $table->string('description')->nullable();
    
    // Stripe reference for real money transactions
    $table->string('stripe_payment_intent_id')->nullable();
    
    $table->timestamps();
    
    $table->index('user_id');
    $table->index('wallet_id');
    $table->index('type');
    $table->index('direction');
    $table->index('stripe_payment_intent_id');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};
