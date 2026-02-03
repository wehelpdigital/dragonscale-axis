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
        Schema::create('ai_image_search_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('provider')->default('unsplash'); // unsplash, pexels, google
            $table->text('apiKey')->nullable();
            $table->string('googleCseId')->nullable(); // Google Custom Search Engine ID
            $table->boolean('isEnabled')->default(true);
            $table->integer('maxImagesPerRequest')->default(3);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_image_search_settings');
    }
};
