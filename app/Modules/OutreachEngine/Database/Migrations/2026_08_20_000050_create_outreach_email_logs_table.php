<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per outbound email attempt — the audit trail AND the throttle's
 * source of truth (the daily cap counts 'sent' rows dated today, Asia/Manila).
 *
 * subjectUsed / bodyUsed store the fully rendered copy rather than a template
 * reference: the template can be edited or deleted afterwards and the inbox
 * thread must still show exactly what the recipient received.
 *
 * messageId holds the RFC 5322 Message-ID we generate at send time; the IMAP
 * reader matches an inbound In-Reply-To/References header back to it to link
 * a reply to its lead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_email_logs')) {
            return;
        }

        Schema::create('outreach_email_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->unsignedBigInteger('leadId')->index();
            $table->unsignedBigInteger('templateId')->nullable()->index();
            $table->string('messageId', 255)->nullable()->index();

            $table->string('subjectUsed', 500);
            $table->longText('bodyUsed');
            $table->enum('status', ['queued', 'sent', 'failed', 'bounced'])->default('queued');
            $table->text('smtpResponse')->nullable();
            $table->text('errorMessage')->nullable();
            $table->boolean('aiRephrased')->default(false);
            $table->dateTime('sentAt')->nullable()->index();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Covers the per-minute cron question: "how many did this user
            // actually send today?"
            $table->index(['usersId', 'status', 'sentAt'], 'outreach_logs_user_status_sent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_email_logs');
    }
};
