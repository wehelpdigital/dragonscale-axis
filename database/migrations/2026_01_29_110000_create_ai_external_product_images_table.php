<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * External Product Images - supports multiple images per product
     * Each image can be analyzed independently with OCR
     */
    public function up(): void
    {
        Schema::create('ai_external_product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('productId')->comment('Foreign key to ai_external_products');
            $table->unsignedBigInteger('usersId');

            // Image Information
            $table->string('imagePath')->comment('Path to stored image');
            $table->string('imageUrl')->nullable()->comment('Public URL for RAG reference');
            $table->string('originalName')->comment('Original filename');
            $table->unsignedBigInteger('fileSize')->default(0);
            $table->string('mimeType')->nullable();

            // OCR / AI Analysis
            $table->text('ocrText')->nullable()->comment('Text extracted from image via AI Vision');
            $table->json('aiAnalysis')->nullable()->comment('AI analysis specific to this image');
            /*
             * aiAnalysis JSON structure (per image):
             * {
             *   "imageType": "front_label|back_label|ingredients|instructions|warnings|other",
             *   "extractedText": "raw OCR text",
             *   "summary": "what this image shows",
             *   "relevantInfo": ["key point 1", "key point 2"],
             * }
             */

            // Processing Status
            $table->enum('status', ['pending', 'processing', 'analyzed', 'failed'])->default('pending');
            $table->text('errorMessage')->nullable();

            // Metadata
            $table->integer('sortOrder')->default(0)->comment('Display order');
            $table->boolean('isPrimary')->default(false)->comment('Primary/featured image');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');

            $table->timestamps();

            // Indexes
            $table->index('productId');
            $table->index('usersId');
            $table->index('status');
            $table->index('delete_status');
            $table->index(['productId', 'delete_status']);
        });

        // Update ai_external_products to remove single image fields (we'll keep them for backward compatibility but deprecate)
        // Actually, let's keep imagePath for the primary/thumbnail image reference
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_external_product_images');
    }
};
