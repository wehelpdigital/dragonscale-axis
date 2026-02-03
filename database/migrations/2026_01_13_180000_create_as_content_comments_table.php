<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the comments table for Ani-Senso course content.
     * Supports nested/threaded comments, emoji, and GIF content.
     */
    public function up(): void
    {
        if (!Schema::hasTable('as_content_comments')) {
            Schema::create('as_content_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('asCoursesId'); // Which course
                $table->unsignedBigInteger('contentId')->nullable(); // Which content (null = general course comment)
                $table->unsignedBigInteger('parentCommentId')->nullable(); // For nested comments

                // Author information
                $table->enum('authorType', ['admin', 'student', 'guest'])->default('guest');
                $table->unsignedInteger('authorId')->nullable(); // User/client ID if logged in
                $table->string('authorName', 100);
                $table->string('authorEmail', 255)->nullable();
                $table->string('authorAvatar', 500)->nullable(); // Avatar URL

                // Comment content
                $table->text('commentText'); // Supports emoji codes and GIF URLs

                // Status flags
                $table->boolean('isAnswered')->default(false); // Has admin replied?
                $table->boolean('isApproved')->default(true); // For moderation
                $table->boolean('isPinned')->default(false); // Pin important comments
                $table->boolean('deleteStatus')->default(true); // true = active

                $table->timestamps();

                // Indexes
                $table->index('asCoursesId');
                $table->index('contentId');
                $table->index('parentCommentId');
                $table->index(['asCoursesId', 'isAnswered', 'deleteStatus'], 'as_comments_unanswered_idx');
                $table->index(['contentId', 'deleteStatus', 'created_at'], 'as_comments_content_idx');

                // Foreign key for self-referencing (nested comments)
                $table->foreign('parentCommentId')
                      ->references('id')
                      ->on('as_content_comments')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_content_comments');
    }
};
