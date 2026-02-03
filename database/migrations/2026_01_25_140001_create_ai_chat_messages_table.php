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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sessionId');
            $table->enum('role', ['user', 'assistant', 'system', 'thinking'])->default('user');
            $table->longText('content');
            $table->json('images')->nullable()->comment('Array of image paths');
            $table->json('metadata')->nullable()->comment('Additional info: tokens, model, node outputs, etc.');
            $table->decimal('processingTime', 10, 3)->nullable()->comment('Processing time in seconds');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['sessionId', 'delete_status']);
            $table->index('role');
            $table->foreign('sessionId')->references('id')->on('ai_chat_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
