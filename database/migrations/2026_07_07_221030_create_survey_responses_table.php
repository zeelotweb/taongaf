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
    Schema::create('survey_responses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
        $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->text('answer');

        $table->timestamps();

        $table->unique(['survey_question_id', 'user_id']);
        $table->index('survey_id');
        $table->index('user_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
