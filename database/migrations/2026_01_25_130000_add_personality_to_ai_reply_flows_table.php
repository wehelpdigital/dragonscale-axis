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
        Schema::table('ai_reply_flows', function (Blueprint $table) {
            $table->text('personality')->nullable()->after('flowData')->comment('AI personality description');
            $table->json('sampleConversations')->nullable()->after('personality')->comment('Sample conversations for AI training');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_reply_flows', function (Blueprint $table) {
            $table->dropColumn(['personality', 'sampleConversations']);
        });
    }
};
