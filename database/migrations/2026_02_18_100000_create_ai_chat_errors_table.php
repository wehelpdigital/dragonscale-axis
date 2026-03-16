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
        Schema::create('ai_chat_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->unsignedBigInteger('sessionId')->nullable();
            $table->dateTime('errorDate');
            $table->longText('chatThread');
            $table->longText('flowLogs')->nullable();
            $table->text('errorDescription')->nullable();
            $table->enum('status', ['pending', 'fixed'])->default('pending');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('sessionId');
            $table->index('status');
            $table->index('delete_status');
            $table->index('errorDate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_errors');
    }
};
