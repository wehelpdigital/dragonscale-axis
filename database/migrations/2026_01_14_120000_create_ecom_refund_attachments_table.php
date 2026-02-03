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
        Schema::create('ecom_refund_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refundRequestId');
            $table->string('fileName'); // Original file name
            $table->string('filePath'); // Stored file path
            $table->enum('fileType', ['image', 'video'])->default('image');
            $table->string('mimeType')->nullable();
            $table->unsignedBigInteger('fileSize')->default(0); // Size in bytes
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->foreign('refundRequestId')
                ->references('id')
                ->on('ecom_refund_requests')
                ->onDelete('cascade');

            $table->index(['refundRequestId', 'deleteStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_refund_attachments');
    }
};
