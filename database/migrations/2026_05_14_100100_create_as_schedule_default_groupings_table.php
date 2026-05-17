<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_default_groupings', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('groupName');
            $table->integer('staggerDays')->default(0);
            $table->integer('groupOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_default_groupings');
    }
};
