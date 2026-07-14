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
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
        $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();

        // Content
        $table->text('body')->nullable();
        $table->enum('type', ['text', 'media', 'forwarded', 'system'])->default('text');

        // Forwarded message reference
        $table->foreignId('forwarded_from_id')->nullable()->constrained('messages')->nullOnDelete();

        // Status
        $table->boolean('is_deleted')->default(false);
        $table->timestamp('deleted_at')->nullable();

        $table->timestamps();

        $table->index('conversation_id');
        $table->index('sender_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
