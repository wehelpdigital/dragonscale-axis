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
        Schema::table('ai_rag_files', function (Blueprint $table) {
            $table->string('pineconeFileId', 100)->nullable()->after('filePath');
            $table->index('pineconeFileId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_rag_files', function (Blueprint $table) {
            $table->dropIndex(['pineconeFileId']);
            $table->dropColumn('pineconeFileId');
        });
    }
};
