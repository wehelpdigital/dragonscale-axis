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
        Schema::table('ai_website_pages', function (Blueprint $table) {
            $table->string('pineconeFileId', 255)->nullable()->after('scrapeError')->comment('Pinecone file ID for RAG');
            $table->enum('pineconeStatus', ['pending', 'processing', 'indexed', 'failed'])->nullable()->after('pineconeFileId')->comment('Pinecone indexing status');
            $table->text('pineconeError')->nullable()->after('pineconeStatus')->comment('Pinecone error message if failed');
            $table->timestamp('pineconeIndexedAt')->nullable()->after('pineconeError')->comment('When page was indexed in Pinecone');

            $table->index('pineconeFileId');
            $table->index('pineconeStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_website_pages', function (Blueprint $table) {
            $table->dropIndex(['pineconeFileId']);
            $table->dropIndex(['pineconeStatus']);
            $table->dropColumn(['pineconeFileId', 'pineconeStatus', 'pineconeError', 'pineconeIndexedAt']);
        });
    }
};
