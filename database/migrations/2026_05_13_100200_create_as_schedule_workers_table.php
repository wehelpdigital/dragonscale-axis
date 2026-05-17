<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_workers', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('workerName');
            $table->decimal('costPerHalfDay', 12, 2)->default(0);
            $table->integer('priority')->default(1);
            $table->text('notes')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('priority');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_workers');
    }
};
