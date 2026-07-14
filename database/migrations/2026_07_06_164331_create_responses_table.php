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
    Schema::create('responses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
        $table->text('body');
        $table->boolean('is_flagged')->default(false);
        $table->timestamp('flagged_at')->nullable();
        $table->softDeletes();
        $table->timestamps();

        $table->index('comment_id');
        $table->index('user_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};
