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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic — attaches to editorial, chapter, book or message
            $table->nullableMorphs('mediable');

            // Storage
            $table->enum('disk', ['public', 'r2'])->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name');

            // File meta
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->enum('type', ['video', 'audio', 'pdf', 'image']);

            // For video/audio — Mux integration
            $table->string('mux_asset_id')->nullable();
            $table->string('mux_playback_id')->nullable();
            $table->string('duration')->nullable();

            // Thumbnails
            $table->string('thumbnail_url')->nullable();

            // Content
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Ordering & processing
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_failed')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index('mime_type');
            $table->index('type');
            $table->index('mux_asset_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};







