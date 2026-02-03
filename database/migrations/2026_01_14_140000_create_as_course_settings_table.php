<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Course Settings Table
     * ---------------------
     * Stores per-course configuration settings.
     *
     * Course Flow Settings:
     * - contentAccessMode: Controls how students can navigate through course content
     *   - 'open': All topics and chapters are accessible from the start
     *   - 'sequential': Topics unlock only after the previous topic is marked complete
     *
     * - quizBlocksNextChapter: Controls whether quizzes gate chapter progression
     *   - true: Student must pass the chapter quiz before accessing the next chapter
     *   - false: Students can proceed to next chapter regardless of quiz results
     */
    public function up(): void
    {
        Schema::create('as_course_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asCoursesId')->unique(); // One settings row per course

            // Course Flow Settings
            $table->enum('contentAccessMode', ['open', 'sequential'])->default('open');
            $table->boolean('quizBlocksNextChapter')->default(false);

            // Future settings can be added here as new columns
            // Examples:
            // - certificateEnabled (boolean)
            // - minimumPassScore (integer)
            // - allowRetakes (boolean)
            // - maxRetakeAttempts (integer)

            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('asCoursesId');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_course_settings');
    }
};
