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
        Schema::create('ai_kb_image_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->text('apiKey')->nullable()->comment('Encrypted Pinecone API key');
            $table->string('indexName', 255)->nullable()->comment('Pinecone index/assistant name');
            $table->string('indexHost', 500)->nullable()->comment('Pinecone index host URL');
            $table->string('email', 255)->nullable()->comment('Pinecone account email');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('delete_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_kb_image_settings');
    }
};
