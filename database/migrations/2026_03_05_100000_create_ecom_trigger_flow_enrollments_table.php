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
        Schema::create('ecom_trigger_flow_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flowId')->index();
            $table->unsignedBigInteger('clientId')->nullable()->index();
            $table->unsignedBigInteger('orderId')->nullable()->index();
            $table->string('triggerSource', 50)->default('manual'); // manual, order, api, flow_action
            $table->json('contextData')->nullable(); // Order info, client info, etc.
            $table->enum('status', ['active', 'completed', 'cancelled', 'paused', 'failed'])->default('active');
            $table->unsignedInteger('totalTasks')->default(0);
            $table->unsignedInteger('completedTasks')->default(0);
            $table->unsignedInteger('currentTaskOrder')->default(0);
            $table->timestamp('startedAt')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->timestamp('cancelledAt')->nullable();
            $table->unsignedBigInteger('cancelledBy')->nullable();
            $table->string('cancellationReason')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->timestamps();
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');

            $table->foreign('flowId')->references('id')->on('ecom_trigger_flows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_trigger_flow_enrollments');
    }
};
