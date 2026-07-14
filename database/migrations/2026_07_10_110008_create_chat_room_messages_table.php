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
    Schema::create('chat_room_messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('chat_room_id')->constrained()->cascadeOnDelete();
        $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();

        $table->text('body')->nullable();
        $table->enum('type', ['text', 'media', 'system'])->default('text');
        $table->boolean('is_deleted')->default(false);
        $table->timestamp('deleted_at')->nullable();

        $table->timestamps();

        $table->index('chat_room_id');
        $table->index('sender_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_room_messages');
    }
};
