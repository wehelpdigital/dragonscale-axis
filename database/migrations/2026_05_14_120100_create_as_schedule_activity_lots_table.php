<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_activity_lots', function (Blueprint $table) {
            $table->id();
            $table->integer('activityId');
            $table->integer('lotId');
            $table->timestamps();

            $table->index('activityId');
            $table->index('lotId');
            $table->unique(['activityId', 'lotId'], 'unq_activity_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_activity_lots');
    }
};
