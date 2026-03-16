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
        Schema::create('recom_api_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->enum('provider', ['claude', 'openai', 'gemini'])->default('claude');
            $table->text('apiKey')->nullable();
            $table->string('organizationId', 255)->nullable();
            $table->string('defaultModel', 100)->nullable();
            $table->integer('maxTokens')->default(4096);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('requestsPerMinute')->default(60);
            $table->integer('tokensPerMinute')->default(100000);
            $table->boolean('isActive')->default(true);
            $table->boolean('isDefault')->default(false);
            $table->timestamp('lastTestedAt')->nullable();
            $table->enum('lastTestStatus', ['success', 'failed'])->nullable();
            $table->text('lastTestError')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
            $table->index(['usersId', 'provider', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recom_api_settings');
    }
};
