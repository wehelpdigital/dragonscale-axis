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
        Schema::table('ai_websites', function (Blueprint $table) {
            $table->timestamp('lastRagSyncAt')->nullable()->after('lastScrapedAt')->comment('Last time pages were synced to Pinecone RAG');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_websites', function (Blueprint $table) {
            $table->dropColumn('lastRagSyncAt');
        });
    }
};
