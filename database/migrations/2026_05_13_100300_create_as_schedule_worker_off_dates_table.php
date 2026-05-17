<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_worker_off_dates', function (Blueprint $table) {
            $table->id();
            $table->integer('workerId');
            $table->date('offDate');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('workerId');
            $table->index('offDate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_worker_off_dates');
    }
};
