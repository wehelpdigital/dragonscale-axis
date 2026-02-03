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
        Schema::create('as_review_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reviewId');
            $table->unsignedBigInteger('userId')->nullable(); // Admin user who replied
            $table->string('userName')->nullable();
            $table->text('replyText'); // Supports emoji and GIF URLs
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('reviewId');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_review_replies');
    }
};
