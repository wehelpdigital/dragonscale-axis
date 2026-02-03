<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * External Products for AI Technician Knowledge Base
     * Stores agricultural product information (pesticides, fertilizers, etc.)
     * with AI-analyzed details for accurate recommendations.
     */
    public function up(): void
    {
        Schema::create('ai_external_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');

            // Basic Product Information
            $table->string('productName');
            $table->string('brandName')->nullable();
            $table->string('manufacturer')->nullable();

            // Product Type Classification
            $table->enum('productType', [
                'pesticide',
                'insecticide',
                'fungicide',
                'herbicide',
                'bactericide',
                'nematicide',
                'molluscicide',
                'rodenticide',
                'fertilizer_granular',
                'fertilizer_foliar',
                'fertilizer_liquid',
                'fertilizer_organic',
                'plant_growth_regulator',
                'soil_conditioner',
                'seed_treatment',
                'adjuvant',
                'other'
            ])->default('pesticide');

            // User Input
            $table->text('manualText')->nullable()->comment('User-provided product description');

            // Image & OCR
            $table->string('imagePath')->nullable()->comment('Path to uploaded product image');
            $table->string('imageUrl')->nullable()->comment('Public URL for RAG reference');
            $table->text('ocrText')->nullable()->comment('Text extracted from image via AI Vision');

            // AI-Generated Analysis (stored as JSON)
            $table->json('aiAnalysis')->nullable()->comment('Comprehensive AI analysis of the product');
            /*
             * aiAnalysis JSON structure:
             * {
             *   "summary": "Brief product description",
             *   "purpose": "What this product is used for",
             *   "activeIngredients": [
             *     {"name": "Chlorpyrifos", "concentration": "500g/L", "purpose": "Contact insecticide"}
             *   ],
             *   "targetPests": ["Stem borer", "Army worm", "Aphids"],
             *   "targetDiseases": ["Rice blast", "Bacterial leaf blight"],
             *   "targetCrops": ["Rice", "Corn", "Vegetables"],
             *   "applicationMethod": "Foliar spray",
             *   "dosage": "20-30ml per 16L water",
             *   "applicationTiming": "Apply at early infestation",
             *   "safetyPrecautions": ["Wear protective gear", "Avoid contact with skin"],
             *   "preHarvestInterval": "14 days",
             *   "reEntryInterval": "24 hours",
             *   "compatibility": ["Can mix with foliar fertilizers"],
             *   "incompatibility": ["Do not mix with alkaline products"],
             *   "storageInstructions": "Store in cool, dry place",
             *   "searchTags": ["insecticide", "stem borer", "corn pest", "rice pest"],
             *   "relatedProducts": ["Alternative products for same purpose"],
             *   "localAvailability": "Available in agricultural supply stores",
             *   "priceRange": "PHP 200-300 per liter",
             *   "registrationNumber": "FPA Reg. No. XXXX"
             * }
             */

            // Combined RAG Content (what gets uploaded to Pinecone)
            $table->longText('ragContent')->nullable()->comment('Combined content for RAG indexing');

            // RAG/Pinecone Status
            $table->string('pineconeFileId')->nullable()->comment('Pinecone file ID after upload');
            $table->enum('ragStatus', [
                'pending',
                'processing',
                'analyzing',
                'uploading',
                'indexed',
                'failed'
            ])->default('pending');
            $table->text('ragError')->nullable()->comment('Error message if RAG upload failed');

            // Metadata
            $table->boolean('isVerified')->default(false)->comment('Admin verified accuracy');
            $table->boolean('isActive')->default(true);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');

            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('productType');
            $table->index('ragStatus');
            $table->index('delete_status');
            $table->index(['usersId', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_external_products');
    }
};
