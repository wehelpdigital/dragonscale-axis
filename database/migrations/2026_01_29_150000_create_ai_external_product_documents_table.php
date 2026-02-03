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
        Schema::create('ai_external_product_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('productId');
            $table->unsignedBigInteger('usersId');
            $table->string('documentPath');
            $table->string('documentUrl')->nullable();
            $table->string('originalName');
            $table->unsignedBigInteger('fileSize')->default(0);
            $table->string('mimeType')->nullable();
            $table->string('fileExtension', 20)->nullable();
            $table->longText('extractedText')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'processing', 'extracted', 'failed'])->default('pending');
            $table->text('errorMessage')->nullable();
            $table->integer('sortOrder')->default(0);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('productId')->references('id')->on('ai_external_products')->onDelete('cascade');
            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['productId', 'delete_status']);
            $table->index(['usersId', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_external_product_documents');
    }
};
