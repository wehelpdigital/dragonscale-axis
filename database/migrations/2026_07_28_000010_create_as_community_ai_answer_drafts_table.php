<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Draft AI answers for community group posts (questions). The AniSenso admin
 * generates answers in one click, reviews/edits them here, then posts each
 * (or all) as a community reply authored by the AI Technician persona.
 *
 * A group post is considered "AI-answered" once it has a draft with
 * status = 'posted' (or an existing reply by the AI persona user).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_ai_answer_drafts')) {
            return;
        }

        Schema::create('as_community_ai_answer_drafts', function (Blueprint $table) {
            $table->id();
            $table->integer('postId');            // as_community_group_posts.id — the question
            $table->integer('groupId')->nullable();
            $table->string('questionTitle', 255)->nullable();
            $table->text('questionBody')->nullable();      // snapshot for the review UI
            $table->longText('answerBody')->nullable();     // AI answer, admin-editable
            $table->string('status', 20)->default('pending'); // pending | posted | dismissed
            $table->string('model', 100)->nullable();
            $table->integer('generatedByUserId')->nullable(); // admin who ran the generate
            $table->integer('postedReplyId')->nullable();     // as_community_group_replies.id once posted
            $table->timestamp('postedAt')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('postId');
            $table->index('status');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_ai_answer_drafts');
    }
};
