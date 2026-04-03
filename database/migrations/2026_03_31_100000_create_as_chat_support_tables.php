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
        Schema::create('as_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('visitorName', 100)->nullable();
            $table->string('visitorEmail', 150)->nullable();
            $table->string('sessionId', 100)->unique();
            $table->unsignedBigInteger('assignedTo')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('sessionId');
            $table->index('status');
            $table->index('deleteStatus');
            $table->index('assignedTo');
        });

        Schema::create('as_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversationId');
            $table->enum('senderType', ['visitor', 'admin']);
            $table->unsignedBigInteger('senderId')->nullable();
            $table->text('message');
            $table->boolean('isRead')->default(false);
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('conversationId')->references('id')->on('as_chat_conversations')->onDelete('cascade');
            $table->index('conversationId');
            $table->index('senderType');
            $table->index('isRead');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_chat_messages');
        Schema::dropIfExists('as_chat_conversations');
    }
};
