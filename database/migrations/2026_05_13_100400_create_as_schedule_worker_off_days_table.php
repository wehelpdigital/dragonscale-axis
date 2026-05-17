<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_worker_off_days', function (Blueprint $table) {
            $table->id();
            $table->integer('workerId');
            $table->tinyInteger('dayOfWeek'); // 0=Sunday, 1=Monday, ... 6=Saturday
            $table->timestamps();

            $table->index('workerId');
            $table->unique(['workerId', 'dayOfWeek']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_worker_off_days');
    }
};
