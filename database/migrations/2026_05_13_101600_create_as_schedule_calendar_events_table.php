<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->integer('generationId');
            $table->integer('croppingScheduleId');
            $table->integer('groupingId')->nullable();
            $table->integer('lotId')->nullable();
            $table->enum('eventType', ['activity', 'irrigation']);
            $table->integer('activityId')->nullable();
            $table->integer('irrigationId')->nullable();
            $table->string('eventTitle');
            $table->date('scheduledDate');
            $table->date('originalDate')->nullable(); // before shifting due to constraints
            $table->enum('timeOfDay', ['half', 'whole'])->default('half');
            $table->integer('targetDayValue')->nullable(); // DAP/DAS/DAT value
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['pending', 'completed', 'skipped'])->default('pending');
            $table->datetime('completedAt')->nullable();
            $table->integer('completedBy')->nullable();
            $table->decimal('extraCost', 14, 2)->default(0);
            $table->text('extraCostDescription')->nullable();
            $table->text('remarks')->nullable();
            $table->json('assignedWorkerIds')->nullable(); // array of worker ids
            $table->integer('sequenceOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('generationId');
            $table->index('croppingScheduleId');
            $table->index('scheduledDate');
            $table->index('status');
            $table->index(['eventType', 'activityId']);
            $table->index(['eventType', 'irrigationId']);
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_calendar_events');
    }
};
