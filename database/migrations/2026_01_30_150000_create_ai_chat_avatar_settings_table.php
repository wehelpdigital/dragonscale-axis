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
        Schema::create('ai_chat_avatar_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('avatarPath', 500)->nullable()->comment('Path to uploaded avatar image');
            $table->string('avatarFilename', 255)->nullable()->comment('Original filename');
            $table->string('displayName', 100)->default('AI Technician')->comment('Display name for the AI');
            $table->boolean('useCustomAvatar')->default(false)->comment('Whether to use custom avatar or default');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['usersId', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_avatar_settings');
    }
};
