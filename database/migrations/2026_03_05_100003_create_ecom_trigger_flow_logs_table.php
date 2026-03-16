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
        Schema::create('ecom_trigger_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollmentId')->nullable()->index();
            $table->unsignedBigInteger('taskId')->nullable()->index();
            $table->unsignedBigInteger('flowId')->nullable()->index();
            $table->string('action', 50); // task_started, task_completed, task_failed, enrollment_created, etc.
            $table->string('nodeType', 50)->nullable();
            $table->string('nodeLabel', 255)->nullable();
            $table->json('logData')->nullable(); // Additional log data
            $table->text('message')->nullable();
            $table->enum('logLevel', ['info', 'warning', 'error', 'debug'])->default('info');
            $table->string('ipAddress', 45)->nullable();
            $table->string('userAgent')->nullable();
            $table->unsignedBigInteger('executedBy')->nullable(); // User ID if manual
            $table->string('executionSource', 20)->default('cron'); // cron, manual, api
            $table->decimal('executionTime', 10, 4)->nullable(); // Seconds
            $table->timestamps();

            // Index for querying logs
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_trigger_flow_logs');
    }
};
