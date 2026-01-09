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
        Schema::create('ecom_affiliate_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliateId');
            $table->string('documentName', 255);
            $table->string('documentType', 100)->nullable(); // e.g., 'ID', 'Contract', 'Certificate'
            $table->string('documentPath', 500);
            $table->text('documentNotes')->nullable();
            $table->integer('deleteStatus')->default(1); // 1=active, 0=deleted
            $table->timestamps();

            // Indexes
            $table->index('affiliateId');
            $table->index('deleteStatus');

            // Foreign key to affiliates table
            $table->foreign('affiliateId')
                ->references('id')
                ->on('ecom_affiliates')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_affiliate_documents');
    }
};
