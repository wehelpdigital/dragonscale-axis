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
        Schema::create('as_blogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');

            // Blog content
            $table->string('blogTitle');
            $table->string('blogSlug')->unique();
            $table->string('blogCategory')->default('News');
            $table->string('blogCategoryColor')->default('brand-green');
            $table->string('blogFeaturedImage')->nullable();
            $table->text('blogExcerpt');
            $table->longText('blogContent');

            // SEO fields
            $table->string('metaTitle')->nullable();
            $table->text('metaDescription')->nullable();
            $table->string('metaKeywords')->nullable();

            // Publishing
            $table->enum('blogStatus', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('publishedAt')->nullable();
            $table->boolean('isFeatured')->default(false);
            $table->integer('blogOrder')->default(0);

            // Stats
            $table->integer('viewCount')->default(0);

            // Author info
            $table->string('authorName')->nullable();
            $table->string('authorImage')->nullable();

            // Soft delete
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');

            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['blogStatus', 'deleteStatus']);
            $table->index('publishedAt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_blogs');
    }
};
