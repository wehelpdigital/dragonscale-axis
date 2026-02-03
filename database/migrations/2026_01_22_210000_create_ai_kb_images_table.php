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
        Schema::create('ai_kb_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('fileName', 255);
            $table->string('originalName', 255);
            $table->string('filePath', 500)->nullable();
            $table->text('description'); // Required - RAG context for the image
            $table->unsignedBigInteger('fileSize')->default(0);
            $table->string('mimeType', 100)->nullable();
            $table->string('fileHash', 64)->nullable();
            $table->string('pineconeFileId', 255)->nullable();
            $table->enum('pineconeStatus', ['pending', 'processing', 'indexed', 'failed'])->default('pending');
            $table->text('errorMessage')->nullable();
            $table->timestamp('indexedAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
            $table->index('pineconeStatus');
            $table->index('fileHash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_kb_images');
    }
};
