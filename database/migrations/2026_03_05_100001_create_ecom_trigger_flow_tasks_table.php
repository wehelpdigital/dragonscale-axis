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
        Schema::create('ecom_trigger_flow_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollmentId')->index();
            $table->unsignedBigInteger('flowId')->index();
            $table->string('nodeId', 100)->index(); // Node ID from flowData
            $table->string('nodeType', 50); // email, delay, if_else, course_access, etc.
            $table->string('nodeLabel', 255)->nullable(); // Human readable label
            $table->json('nodeData')->nullable(); // Node configuration
            $table->unsignedInteger('taskOrder')->default(0); // Order in flow (1, 2, 3...)
            $table->string('parentNodeId', 100)->nullable(); // For branching (if_else, y_flow)
            $table->string('branchType', 20)->nullable(); // yes, no, path_a, path_b
            $table->enum('status', [
                'pending',      // Waiting for previous task
                'scheduled',    // Has scheduled time, waiting
                'ready',        // Ready to execute (no delay, previous done)
                'running',      // Currently being processed
                'completed',    // Done successfully
                'failed',       // Error occurred
                'cancelled',    // Manually cancelled
                'skipped'       // Skipped due to condition
            ])->default('pending');
            $table->timestamp('scheduledAt')->nullable()->index(); // When to execute
            $table->timestamp('startedAt')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->json('resultData')->nullable(); // Result of execution
            $table->text('errorMessage')->nullable();
            $table->unsignedTinyInteger('retryCount')->default(0);
            $table->unsignedTinyInteger('maxRetries')->default(3);
            $table->timestamp('lastRetryAt')->nullable();
            $table->timestamps();
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');

            $table->foreign('enrollmentId')->references('id')->on('ecom_trigger_flow_enrollments')->onDelete('cascade');
            $table->foreign('flowId')->references('id')->on('ecom_trigger_flows')->onDelete('cascade');

            // Index for cron queries
            $table->index(['status', 'scheduledAt']);
            $table->index(['enrollmentId', 'taskOrder']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_trigger_flow_tasks');
    }
};
