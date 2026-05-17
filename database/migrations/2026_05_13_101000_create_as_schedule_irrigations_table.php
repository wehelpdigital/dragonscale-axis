<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_irrigations', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('irrigationTitle');
            $table->text('description')->nullable();
            $table->enum('dayType', ['DAP', 'DAS', 'DAT'])->default('DAS');
            $table->integer('startDay');
            $table->integer('endDay');
            $table->integer('assignedWorkerId')->nullable();
            $table->enum('timeRequired', ['half', 'whole'])->default('half');
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('assignedWorkerId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_irrigations');
    }
};
