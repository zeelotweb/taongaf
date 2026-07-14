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
    Schema::create('referral_commissions', function (Blueprint $table) {
        $table->id();

        // The sale that triggered the commission
        $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();

        // Who drove the sale
        $table->foreignId('hustler_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Whose profile it came from
        $table->foreignId('profile_owner_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // The publisher whose content was sold
        $table->foreignId('publisher_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Split amounts
        $table->unsignedInteger('sale_amount')->default(0);
        $table->unsignedInteger('publisher_earned')->default(0);
        $table->unsignedInteger('hustler_earned')->default(0);
        $table->unsignedInteger('profile_owner_earned')->default(0);
        $table->unsignedInteger('platform_earned')->default(0);

        // Was this publisher hustling their own work
        $table->boolean('is_self_hustle')->default(false);

        // Referral source
        $table->enum('source', ['link', 'session'])->default('session');

        // Has this been included in a withdrawal
        $table->boolean('is_withdrawn')->default(false);
        $table->timestamp('withdrawn_at')->nullable();

        // Over-commission audit
        $table->boolean('over_commission_detected')->default(false);
        $table->unsignedInteger('over_commission_amount')->default(0);
        $table->unsignedInteger('credit_issued')->default(0);

        $table->timestamps();

        $table->index('hustler_id');
        $table->index('profile_owner_id');
        $table->index('publisher_id');
        $table->index('purchase_id');
        $table->index('is_withdrawn');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
