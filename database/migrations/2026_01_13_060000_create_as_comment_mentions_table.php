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
        Schema::create('as_comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commentId')->comment('The comment where the mention was made');
            $table->unsignedBigInteger('asCoursesId')->comment('Course ID for context');
            $table->string('mentionType')->default('reply')->comment('Type: reply, tag');

            // Who was mentioned
            $table->unsignedBigInteger('mentionedUserId')->nullable()->comment('User ID if registered user');
            $table->string('mentionedAuthorName')->nullable()->comment('Author name from comment');
            $table->string('mentionedAuthorEmail')->nullable()->comment('Author email for notification');

            // Who made the mention
            $table->unsignedBigInteger('mentionerUserId')->nullable()->comment('User ID who made the mention');
            $table->string('mentionerAuthorName')->nullable()->comment('Name of who made the mention');
            $table->string('mentionerType')->default('admin')->comment('admin or student');

            // Notification status
            $table->boolean('isRead')->default(false)->comment('Has the mentioned user read this');
            $table->boolean('isNotified')->default(false)->comment('Has notification been sent');
            $table->timestamp('notifiedAt')->nullable()->comment('When notification was sent');
            $table->timestamp('readAt')->nullable()->comment('When user read the notification');

            // Context
            $table->text('commentPreview')->nullable()->comment('Preview of the comment text');
            $table->string('contextType')->nullable()->comment('content or questionnaire');
            $table->unsignedBigInteger('contextId')->nullable()->comment('Content or questionnaire ID');

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('commentId');
            $table->index('asCoursesId');
            $table->index('mentionedUserId');
            $table->index('mentionedAuthorEmail');
            $table->index(['isRead', 'isNotified']);
            $table->index('delete_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_comment_mentions');
    }
};
