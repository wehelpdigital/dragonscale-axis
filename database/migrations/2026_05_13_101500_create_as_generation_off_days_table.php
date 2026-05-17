<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_generation_off_days', function (Blueprint $table) {
            $table->id();
            $table->integer('generationId');
            $table->tinyInteger('dayOfWeek'); // 0=Sunday ... 6=Saturday
            $table->timestamps();

            $table->index('generationId');
            $table->unique(['generationId', 'dayOfWeek']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_generation_off_days');
    }
};
