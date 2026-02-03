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
        Schema::create('ai_rag_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->text('apiKey')->nullable();
            $table->string('indexName', 255)->nullable();
            $table->string('indexHost', 500)->nullable();
            $table->string('email', 255)->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rag_settings');
    }
};
