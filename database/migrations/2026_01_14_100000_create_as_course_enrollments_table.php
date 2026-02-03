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
        Schema::create('as_course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->integer('accessClientId');
            $table->integer('asCoursesId');
            $table->datetime('enrollmentDate');
            $table->datetime('expirationDate')->nullable();
            $table->tinyInteger('isActive')->default(1);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('accessClientId');
            $table->index('asCoursesId');
            $table->index('deleteStatus');
            $table->unique(['accessClientId', 'asCoursesId', 'deleteStatus'], 'unique_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_course_enrollments');
    }
};
