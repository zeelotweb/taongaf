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
Schema::create('token_packages', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->unsignedInteger('tokens');
        $table->decimal('price_usd', 8, 2);
        $table->string('stripe_price_id')->nullable();
        $table->text('description')->nullable();
        $table->boolean('is_popular')->default(false);
        $table->boolean('is_active')->default(true);
        $table->boolean('is_custom')->default(false);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_packages');
    }
};
