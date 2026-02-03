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
        Schema::create('ai_rag_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('fileName', 255);
            $table->string('originalName', 255);
            $table->unsignedBigInteger('fileSize')->default(0);
            $table->string('fileType', 100)->nullable();
            $table->string('filePath', 500)->nullable();
            $table->enum('status', ['pending', 'processing', 'indexed', 'failed'])->default('pending');
            $table->string('pineconeNamespace', 255)->nullable();
            $table->unsignedInteger('vectorCount')->default(0);
            $table->unsignedInteger('chunkCount')->default(0);
            $table->text('errorMessage')->nullable();
            $table->timestamp('indexedAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rag_files');
    }
};
