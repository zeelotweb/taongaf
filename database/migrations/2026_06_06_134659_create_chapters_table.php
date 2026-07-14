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
Schema::create('chapters', function (Blueprint $table) {
    $table->id();
    $table->foreignId('book_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    
    // Core content
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->string('cover_image')->nullable();
    $table->longText('body')->nullable();
    
    // Media type for this chapter
    $table->enum('primary_format', ['text', 'video', 'audio', 'pdf']);
    
    // Ordering
    $table->unsignedInteger('sort_order')->default(0);
    
    // Publishing
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
    $table->timestamp('published_at')->nullable();
    
    // Access — chapter can override book price
    $table->boolean('is_free_preview')->default(false);
    $table->enum('visibility', ['free', 'tokens'])->default('free');
    $table->unsignedInteger('token_price')->nullable();
    
    // Stats
    $table->unsignedBigInteger('views_count')->default(0);
    $table->unsignedBigInteger('reads_count')->default(0);
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['book_id', 'sort_order']);
    $table->index('user_id');
    $table->index('status');
    $table->index('visibility');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
