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
    Schema::create('chat_rooms', function (Blueprint $table) {
        $table->id();
        $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

        $table->string('name');
        $table->text('description')->nullable();
        $table->string('avatar')->nullable();

        // Context — agnostic, expandable
        $table->string('context')->default('general'); // general, work, book_club
        $table->nullableMorphs('contextable'); // optional link to book, editorial etc

        // Settings
        $table->boolean('is_private')->default(false);
        $table->unsignedInteger('max_participants')->nullable();

        $table->timestamp('last_message_at')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index('owner_id');
        $table->index('context');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
