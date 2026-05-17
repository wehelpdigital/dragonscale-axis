<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_generations', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->integer('generationNumber')->default(1);
            $table->date('seasonStartDate');
            $table->datetime('generatedAt')->nullable();
            $table->integer('generatedBy')->nullable();
            $table->tinyInteger('isCurrent')->default(1);
            $table->text('notes')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('isCurrent');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_generations');
    }
};
