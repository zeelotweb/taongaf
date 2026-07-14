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
    Schema::create('message_attachments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('message_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // File details
        $table->string('disk')->default('public');
        $table->string('path');
        $table->string('filename');
        $table->string('original_name');
        $table->string('mime_type');
        $table->unsignedBigInteger('size');
        $table->enum('type', ['image', 'video', 'audio', 'pdf', 'file']);
        $table->string('thumbnail_url')->nullable();

        $table->timestamps();

        $table->index('message_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
