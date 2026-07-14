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
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    
    // Core content
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('synopsis')->nullable();
    
    // Classification
    $table->enum('genre', [
        'fiction',
        'non_fiction', 
        'biography',
        'self_help',
        'poetry',
        'essay_collection',
        'other'
    ])->default('other');
    
    // Available formats
    $table->boolean('has_text')->default(false);
    $table->boolean('has_audio')->default(false);
    $table->boolean('has_video')->default(false);
    $table->boolean('has_pdf')->default(false);
    
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
    $table->index('genre');
    $table->index('visibility');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
