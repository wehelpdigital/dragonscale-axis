<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The module's mailbox mirror. Despite the name it stores BOTH sides of a
 * conversation — `direction` = 'outbound' rows are the admin's quick replies —
 * so the inbox screen can render a real thread from one table.
 *
 * (usersId, messageUid) is unique because the IMAP poller re-reads with
 * BODY.PEEK[] (messages stay unread server-side, this table owns read state),
 * so every poll sees the same UIDs again and must not duplicate them. UIDs are
 * only unique within a mailbox's uidValidity generation, which is kept
 * alongside so a mailbox re-index can be detected.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_inbound_messages')) {
            return;
        }

        Schema::create('outreach_inbound_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->unsignedBigInteger('leadId')->nullable()->index();

            $table->string('uidValidity', 60)->nullable();
            $table->string('messageUid', 120)->nullable();     // IMAP UID
            $table->string('messageId', 255)->nullable()->index();
            $table->string('inReplyTo', 255)->nullable()->index();

            $table->string('senderEmail', 255)->index();
            $table->string('senderName', 255)->nullable();
            $table->string('subject', 500)->nullable();
            $table->longText('bodyText')->nullable();
            $table->longText('bodyHtml')->nullable();

            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->boolean('isBounce')->default(false);
            $table->dateTime('readAt')->nullable();
            $table->boolean('isReplied')->default(false);
            $table->dateTime('receivedAt')->nullable()->index();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique(['usersId', 'messageUid'], 'outreach_inbound_user_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_inbound_messages');
    }
};
