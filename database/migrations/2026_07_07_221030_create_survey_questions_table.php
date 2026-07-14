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
    Schema::create('survey_questions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('survey_id')->constrained()->cascadeOnDelete();

        $table->text('question');
        $table->enum('type', ['text', 'multiple_choice', 'rating', 'yes_no']);
        $table->json('options')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_required')->default(true);

        $table->timestamps();

        $table->index(['survey_id', 'sort_order']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
