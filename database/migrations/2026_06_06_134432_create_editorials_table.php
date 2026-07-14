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
Schema::create('editorials', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    
    // Core content
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('body')->nullable();
    
    // Media type
    $table->enum('primary_format', ['text', 'video', 'audio', 'pdf']);
    
    // Cover
    $table->string('cover_image')->nullable();
    
    // Publishing
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
    $table->timestamp('published_at')->nullable();
    
    // Access
    $table->enum('visibility', ['free', 'tokens'])->default('free');
    $table->unsignedInteger('token_price')->nullable();
    
    // Stats
    $table->unsignedBigInteger('views_count')->default(0);
    $table->unsignedBigInteger('reads_count')->default(0);
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('user_id');
    $table->index('slug');
    $table->index('status');
    $table->index('primary_format');
    $table->index('visibility');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorials');
    }
};
