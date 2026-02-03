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
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('sessionName', 255)->nullable();
            $table->unsignedBigInteger('replyFlowId')->nullable();
            $table->timestamp('lastMessageAt')->nullable();
            $table->integer('messageCount')->default(0);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['usersId', 'delete_status']);
            $table->index('lastMessageAt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
