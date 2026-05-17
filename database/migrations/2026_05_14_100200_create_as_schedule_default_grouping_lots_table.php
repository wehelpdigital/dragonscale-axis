<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_default_grouping_lots', function (Blueprint $table) {
            $table->id();
            $table->integer('defaultGroupingId');
            $table->integer('lotId');
            $table->timestamps();

            $table->index('defaultGroupingId');
            $table->index('lotId');
            $table->unique(['defaultGroupingId', 'lotId'], 'unq_default_grouping_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_default_grouping_lots');
    }
};
