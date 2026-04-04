<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_testimonials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('name', 150);
            $table->string('location', 255)->nullable();
            $table->string('role', 100)->nullable();
            $table->text('testimonial');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('image', 500)->nullable();
            $table->boolean('isActive')->default(true);
            $table->integer('testimonialOrder')->default(0);
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('isActive');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_testimonials');
    }
};
