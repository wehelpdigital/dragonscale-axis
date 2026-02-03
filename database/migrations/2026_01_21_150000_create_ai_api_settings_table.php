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
        Schema::create('ai_api_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');

            // Provider identification
            $table->enum('provider', ['claude', 'openai', 'gemini'])->default('claude');

            // API credentials (encrypted)
            $table->text('apiKey')->nullable();
            $table->string('organizationId', 255)->nullable(); // For OpenAI org ID

            // Model configuration
            $table->string('defaultModel', 100)->nullable();
            $table->integer('maxTokens')->default(4096);
            $table->decimal('temperature', 3, 2)->default(0.7);

            // Rate limiting
            $table->integer('requestsPerMinute')->nullable();
            $table->integer('tokensPerMinute')->nullable();

            // Status
            $table->boolean('isActive')->default(false);
            $table->boolean('isDefault')->default(false); // Which provider is the default
            $table->timestamp('lastTestedAt')->nullable();
            $table->enum('lastTestStatus', ['pending', 'success', 'failed'])->default('pending');
            $table->text('lastTestError')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique(['usersId', 'provider', 'delete_status'], 'unique_user_provider');
            $table->index('usersId');
            $table->index('provider');
            $table->index('isActive');
            $table->index('isDefault');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_api_settings');
    }
};
