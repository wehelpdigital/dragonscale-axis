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
        Schema::create('as_course_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asCoursesId');
            $table->unsignedBigInteger('enrollmentId');
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->text('reviewTitle')->nullable();
            $table->text('reviewText');
            $table->boolean('isApproved')->default(true);
            $table->boolean('isFeatured')->default(false);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('asCoursesId');
            $table->index('enrollmentId');
            $table->index('rating');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_course_reviews');
    }
};
