<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reoon Email Verifier credentials and the rule for what counts as sendable.
 *
 * The key is TEXT rather than varchar because it is stored through
 * Crypt::encryptString like every other secret in this table, and ciphertext is
 * several times the length of the key it hides.
 *
 * verifierMode maps to Reoon's own two modes: 'quick' is syntax, domain and MX
 * only, 'power' additionally opens an SMTP conversation with the mail server.
 * Power is slower and consumes more credit but is the only one that can tell a
 * live mailbox from a well-formed guess, which is the entire point of running
 * verification before a cold send.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        Schema::table('outreach_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('outreach_settings', 'reoonApiKey')) {
                $table->text('reoonApiKey')->nullable()->after('googleSearchEngineId');
            }

            if (!Schema::hasColumn('outreach_settings', 'verifierMode')) {
                $table->enum('verifierMode', ['quick', 'power'])->default('power')->after('reoonApiKey');
            }

            if (!Schema::hasColumn('outreach_settings', 'verificationEnabled')) {
                $table->boolean('verificationEnabled')->default(true)->after('verifierMode');
            }

            if (!Schema::hasColumn('outreach_settings', 'requireVerifiedEmail')) {
                // When true the send queue will only touch a lead whose address
                // the verifier confirmed. Default true: the whole reason to buy
                // verification is to not send to addresses that bounce.
                $table->boolean('requireVerifiedEmail')->default(true)->after('verificationEnabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        Schema::table('outreach_settings', function (Blueprint $table) {
            $table->dropColumn(['reoonApiKey', 'verifierMode', 'verificationEnabled', 'requireVerifiedEmail']);
        });
    }
};
