<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_generation_grouping_lots', function (Blueprint $table) {
            $table->id();
            $table->integer('groupingId');
            $table->integer('lotId');
            $table->timestamps();

            $table->index('groupingId');
            $table->index('lotId');
            $table->unique(['groupingId', 'lotId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_generation_grouping_lots');
    }
};
