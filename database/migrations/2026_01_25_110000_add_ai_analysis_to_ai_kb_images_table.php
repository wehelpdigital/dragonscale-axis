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
        Schema::table('ai_kb_images', function (Blueprint $table) {
            $table->text('aiAnalysis')->nullable()->after('description')->comment('AI-generated image analysis');
            $table->string('aiProvider', 50)->nullable()->after('aiAnalysis')->comment('AI provider used for analysis');
            $table->string('aiModel', 100)->nullable()->after('aiProvider')->comment('AI model used for analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_kb_images', function (Blueprint $table) {
            $table->dropColumn(['aiAnalysis', 'aiProvider', 'aiModel']);
        });
    }
};
