<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finalised lists: a hand-picked set of contacts, promoted out of a sweep.
 *
 * A batch is what the scraper found; a list is what a human decided to keep.
 * Keeping them apart matters because a sweep is a fact about the world and a
 * list is an editorial judgement - re-running the sweep must not silently
 * change a list that has already been mailed.
 *
 * Membership is its own table rather than a column on the lead, because one
 * contact can legitimately sit in several lists (a regional list and a category
 * list) and a single listId column would force a choice between them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_lists')) {
            Schema::create('outreach_lists', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('usersId')->index();
                $table->string('name', 190);
                $table->string('description', 500)->nullable();
                $table->unsignedInteger('totalMembers')->default(0);
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();
                $table->index(['usersId', 'delete_status'], 'outreach_lists_user_status_idx');
            });
        }

        if (!Schema::hasTable('outreach_list_members')) {
            Schema::create('outreach_list_members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('usersId')->index();
                $table->unsignedBigInteger('listId')->index();
                $table->unsignedBigInteger('leadId')->index();

                // Where this contact came from, kept for provenance once the
                // batch itself may have been removed from the batch screen.
                $table->char('sourceBatchId', 36)->nullable();
                $table->dateTime('addedAt')->nullable();
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                // The same contact must not land in one list twice - the screen
                // adds in bulk, so this is what actually makes that safe.
                $table->unique(['listId', 'leadId'], 'outreach_list_members_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_list_members');
        Schema::dropIfExists('outreach_lists');
    }
};
