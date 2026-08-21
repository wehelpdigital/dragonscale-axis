<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin configuration for the Lead Finder (OutreachEngine) module.
 *
 * One active row per usersId — every credential the module needs lives here
 * rather than in .env so each admin runs their own Places quota, their own
 * mailbox and their own send limits without a deploy.
 *
 * The four *ApiKey / *Password columns are TEXT because they hold
 * Crypt::encryptString() ciphertext (base64 JSON envelope), which is far
 * longer than the plaintext key it wraps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_settings')) {
            return;
        }

        Schema::create('outreach_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();

            // --- Google APIs (Places for discovery, Custom Search for enrichment)
            $table->text('googlePlacesApiKey')->nullable();   // encrypted
            $table->text('googleSearchApiKey')->nullable();   // encrypted
            $table->string('googleSearchEngineId', 255)->nullable();

            // --- LLM (email extraction fallback + rephrasing)
            $table->enum('llmProvider', ['claude', 'openai', 'gemini'])->default('gemini');
            $table->text('llmApiKey')->nullable();            // encrypted
            $table->string('llmModel', 120)->nullable();

            // --- Outbound mail
            $table->string('smtpHost', 255)->nullable();
            $table->unsignedSmallInteger('smtpPort')->nullable()->default(587);
            $table->string('smtpUsername', 255)->nullable();
            $table->text('smtpPassword')->nullable();         // encrypted
            $table->enum('smtpEncryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('smtpFromName', 255)->nullable();
            $table->string('smtpFromEmail', 255)->nullable();

            // --- Inbound mail (raw-socket IMAP client; ext-imap is not installed)
            $table->string('imapHost', 255)->nullable();
            $table->unsignedSmallInteger('imapPort')->nullable()->default(993);
            $table->string('imapUsername', 255)->nullable();
            $table->text('imapPassword')->nullable();         // encrypted
            $table->enum('imapEncryption', ['ssl', 'tls', 'none'])->default('ssl');
            $table->string('imapFolder', 120)->default('INBOX');

            // --- Sending limits & business hours (all evaluated in Asia/Manila)
            $table->unsignedSmallInteger('dailySendCap')->default(30);   // clamped 1..50 in code
            $table->time('sendWindowStart')->default('08:30:00');
            $table->time('sendWindowEnd')->default('17:00:00');
            $table->string('sendDays', 40)->default('1,2,3,4,5');        // ISO days, 1 = Monday
            $table->unsignedSmallInteger('minDelayMinutes')->default(3);
            $table->unsignedSmallInteger('maxDelayMinutes')->default(17);

            // --- Grid search geometry
            $table->decimal('defaultGridRadiusKm', 6, 2)->default(5.00);
            $table->decimal('minGridRadiusKm', 6, 2)->default(0.50);
            $table->unsignedTinyInteger('maxSubdivisionDepth')->default(4);

            // --- Domain warm-up: ramps the real cap up day by day from a cold start
            $table->boolean('warmupEnabled')->default(true);
            $table->unsignedSmallInteger('warmupStartCap')->default(5);
            $table->unsignedSmallInteger('warmupIncrementPerDay')->default(2);
            $table->date('warmupStartedOn')->nullable();

            $table->boolean('aiRephraseEnabled')->default(true);
            // Master kill switch — OFF until the admin has tested SMTP/IMAP.
            $table->boolean('outreachEnabled')->default(false);

            // --- Last connectivity test result, surfaced on the settings screen
            $table->dateTime('lastTestedAt')->nullable();
            $table->enum('lastTestStatus', ['pending', 'success', 'failed'])->default('pending');
            $table->text('lastTestError')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // delete_status is part of the key so a soft-deleted row never
            // blocks the user from creating a fresh settings row.
            $table->unique(['usersId', 'delete_status'], 'outreach_settings_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_settings');
    }
};
