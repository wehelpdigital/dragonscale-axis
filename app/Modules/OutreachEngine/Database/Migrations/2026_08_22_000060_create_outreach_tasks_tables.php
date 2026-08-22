<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task Outreach: a campaign built from a finalised list, and the lead pool it works.
 *
 * The task owns its flow. flowConfig holds the per-node settings the Outreach
 * Flow screen edits - which template each branch sends, how long "no reply"
 * waits - as JSON rather than columns, because the node set is fixed but its
 * settings will keep growing and a migration per knob is not worth it.
 *
 * outreach_task_leads is the pool. A lead's state HERE is deliberately separate
 * from outreach_leads.outreachStatus: the same contact can be finished in one
 * campaign and pending in another, and a single status on the lead itself would
 * make the second campaign unable to run at all.
 *
 * nextActionAt is what keeps the cron cheap. Rather than re-deriving every
 * lead's next step on every tick, the flow stamps when a lead is next due and
 * the tick simply asks for the rows whose time has come.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_tasks')) {
            Schema::create('outreach_tasks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('usersId')->index();
                $table->unsignedBigInteger('listId')->nullable()->index();
                $table->string('name', 190);
                $table->string('description', 500)->nullable();

                $table->enum('status', ['draft', 'running', 'paused', 'complete'])->default('draft');

                // Per-node settings for the fixed flow. Null until the Outreach
                // Flow screen saves one, at which point defaults are filled in.
                $table->json('flowConfig')->nullable();

                $table->unsignedInteger('totalLeads')->default(0);
                $table->unsignedInteger('sentCount')->default(0);
                $table->unsignedInteger('openedCount')->default(0);
                $table->unsignedInteger('repliedCount')->default(0);
                $table->unsignedInteger('interestedCount')->default(0);
                $table->unsignedInteger('notInterestedCount')->default(0);
                $table->unsignedInteger('noReplyCount')->default(0);
                $table->unsignedInteger('bouncedCount')->default(0);
                $table->unsignedInteger('spamCount')->default(0);

                $table->dateTime('startedAt')->nullable();
                $table->dateTime('completedAt')->nullable();
                $table->dateTime('lastProcessedAt')->nullable();
                $table->dateTime('countsRefreshedAt')->nullable();

                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();
                $table->index(['usersId', 'status'], 'outreach_tasks_user_status_idx');
            });
        }

        if (!Schema::hasTable('outreach_task_leads')) {
            Schema::create('outreach_task_leads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('usersId')->index();
                $table->unsignedBigInteger('taskId')->index();
                $table->unsignedBigInteger('leadId')->index();

                $table->enum('state', [
                    'pending',
                    'queued',
                    'sent',
                    'replied',
                    'interested',
                    'not_interested',
                    'no_reply',
                    'bounced',
                    'spam',
                    'stopped',
                ])->default('pending');

                $table->unsignedTinyInteger('emailsSent')->default(0);
                $table->unsignedTinyInteger('followUpCount')->default(0);

                $table->dateTime('firstSentAt')->nullable();
                $table->dateTime('lastSentAt')->nullable();
                $table->dateTime('lastRepliedAt')->nullable();
                $table->dateTime('stateChangedAt')->nullable();

                // When the flow should look at this lead again. Null means it is
                // not waiting on a clock - either finished, or due immediately.
                $table->dateTime('nextActionAt')->nullable();

                $table->unsignedBigInteger('lastEmailLogId')->nullable();
                $table->text('lastNote')->nullable();

                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->unique(['taskId', 'leadId'], 'outreach_task_leads_unique');
                $table->index(['taskId', 'state'], 'outreach_task_leads_task_state_idx');
                $table->index(['usersId', 'state', 'nextActionAt'], 'outreach_task_leads_due_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_task_leads');
        Schema::dropIfExists('outreach_tasks');
    }
};
