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
    Schema::create('chat_room_members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('chat_room_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->enum('role', ['owner', 'admin', 'member'])->default('member');
        $table->boolean('is_muted')->default(false);
        $table->timestamp('last_read_at')->nullable();
        $table->timestamp('joined_at')->nullable();

        $table->timestamps();

        $table->unique(['chat_room_id', 'user_id']);
        $table->index('user_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_room_members');
    }
};
