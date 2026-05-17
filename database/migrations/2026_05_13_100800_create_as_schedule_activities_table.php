<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_activities', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('activityTitle');
            $table->enum('activityType', ['DAP', 'DAS', 'DAT']);
            $table->integer('targetDay'); // can be negative
            $table->integer('windowBeforeDays')->default(0);
            $table->integer('windowAfterDays')->default(0);
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->text('description')->nullable();
            $table->integer('workersRequired')->default(0);
            $table->enum('timeRequired', ['half', 'whole'])->default('half');
            $table->integer('lotsPerDay')->default(1);
            $table->integer('sequenceOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index(['activityType', 'targetDay']);
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_activities');
    }
};
