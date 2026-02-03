<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration restructures the Ani-Senso content hierarchy:
     * - Courses → Chapters → Topics → Contents
     *
     * Topics now only have: title, description, cover photo
     * Contents have: title, body (WYSIWYG), youtube, photos, takeaways, downloadables
     */
    public function up(): void
    {
        // Add topicCoverPhoto to topics table if it doesn't exist
        if (!Schema::hasColumn('as_courses_topics', 'topicCoverPhoto')) {
            Schema::table('as_courses_topics', function (Blueprint $table) {
                $table->string('topicCoverPhoto')->nullable()->after('topicDescription');
            });
        }

        // Create as_topic_contents table if it doesn't exist
        if (!Schema::hasTable('as_topic_contents')) {
            Schema::create('as_topic_contents', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('topicId')->index();
                $table->string('contentTitle');
                $table->longText('contentBody')->nullable();
                $table->string('youtubeUrl')->nullable();
                $table->json('contentPhotos')->nullable(); // Array of photo URLs
                $table->text('takeaways')->nullable(); // For popup takeaways
                $table->integer('contentOrder')->default(0)->index();
                $table->boolean('deleteStatus')->default(true); // true = active, false = deleted
                $table->timestamps();

                $table->index(['topicId', 'deleteStatus']);
            });
        }

        // Create as_content_resources table for downloadables if it doesn't exist
        if (!Schema::hasTable('as_content_resources')) {
            Schema::create('as_content_resources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contentId')->index();
                $table->string('fileName');
                $table->string('fileUrl');
                $table->integer('resourceOrder')->default(0)->index();
                $table->boolean('deleteStatus')->default(true); // true = active, false = deleted
                $table->timestamps();

                $table->index(['contentId', 'deleteStatus']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_content_resources');
        Schema::dropIfExists('as_topic_contents');

        if (Schema::hasColumn('as_courses_topics', 'topicCoverPhoto')) {
            Schema::table('as_courses_topics', function (Blueprint $table) {
                $table->dropColumn('topicCoverPhoto');
            });
        }
    }
};
