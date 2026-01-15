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
        Schema::create('ecom_refund_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refundRequestId')->nullable();
            $table->unsignedBigInteger('orderId')->nullable();
            $table->string('refundNumber', 50)->nullable(); // Store for historical reference
            $table->string('orderNumber', 50)->nullable(); // Store for historical reference

            // Action details
            $table->string('action', 50); // created, approved, rejected, processed, deleted, status_changed, notes_updated, etc.
            $table->string('actionLabel', 100)->nullable(); // Human-readable action label

            // Who performed the action
            $table->unsignedBigInteger('actionBy')->nullable(); // User ID
            $table->string('actionByName', 100)->nullable(); // Store name for historical purposes
            $table->string('actionByEmail', 150)->nullable(); // Store email for historical purposes

            // Change tracking
            $table->string('fieldChanged', 100)->nullable(); // Which field was changed
            $table->text('previousValue')->nullable(); // Previous value (can be JSON for complex data)
            $table->text('newValue')->nullable(); // New value (can be JSON for complex data)

            // Additional context
            $table->text('notes')->nullable(); // Additional notes/description
            $table->json('metadata')->nullable(); // Additional metadata (items, amounts, etc.)

            // Request info for security tracking
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();

            // Timestamps
            $table->timestamp('actionAt')->useCurrent();
            $table->timestamps();

            // Soft delete
            $table->tinyInteger('deleteStatus')->default(1); // 1=active, 0=deleted

            // Indexes
            $table->index('refundRequestId');
            $table->index('orderId');
            $table->index('action');
            $table->index('actionBy');
            $table->index('actionAt');
            $table->index(['refundRequestId', 'deleteStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_refund_audit_logs');
    }
};
