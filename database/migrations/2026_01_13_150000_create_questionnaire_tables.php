<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates tables for the questionnaire module:
     * - as_questionnaires: Main questionnaire container (placed between chapters)
     * - as_questionnaire_questions: Individual questions within a questionnaire
     * - as_questionnaire_answers: Answer choices for each question
     */
    public function up(): void
    {
        // Main questionnaires table
        if (!Schema::hasTable('as_questionnaires')) {
            Schema::create('as_questionnaires', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('asCoursesId');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->integer('itemOrder')->default(1);
                $table->boolean('deleteStatus')->default(true);
                $table->timestamps();

                $table->index('asCoursesId');
                $table->index(['asCoursesId', 'deleteStatus', 'itemOrder']);
            });
        }

        // Questions table
        if (!Schema::hasTable('as_questionnaire_questions')) {
            Schema::create('as_questionnaire_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('questionnaireId');
                $table->string('questionTitle', 255);
                $table->text('questionText');
                $table->string('questionPhoto', 500)->nullable();
                $table->string('questionVideo', 500)->nullable(); // YouTube URL
                $table->enum('questionType', ['single', 'multiple'])->default('single');
                // single = radio buttons (one correct answer)
                // multiple = checkboxes (multiple correct answers)
                $table->integer('questionOrder')->default(1);
                $table->boolean('deleteStatus')->default(true);
                $table->timestamps();

                $table->foreign('questionnaireId')
                      ->references('id')
                      ->on('as_questionnaires')
                      ->onDelete('cascade');

                $table->index(['questionnaireId', 'deleteStatus', 'questionOrder'], 'as_quest_questions_idx');
            });
        }

        // Answers table
        if (!Schema::hasTable('as_questionnaire_answers')) {
            Schema::create('as_questionnaire_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('questionId');
                $table->text('answerText');
                $table->boolean('isCorrect')->default(false);
                $table->integer('answerOrder')->default(1);
                $table->boolean('deleteStatus')->default(true);
                $table->timestamps();

                $table->foreign('questionId')
                      ->references('id')
                      ->on('as_questionnaire_questions')
                      ->onDelete('cascade');

                $table->index(['questionId', 'deleteStatus', 'answerOrder'], 'as_quest_answers_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_questionnaire_answers');
        Schema::dropIfExists('as_questionnaire_questions');
        Schema::dropIfExists('as_questionnaires');
    }
};
