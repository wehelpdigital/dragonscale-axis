<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_lots', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('lotName');
            $table->decimal('lotSize', 12, 4)->default(0);
            $table->string('lotSizeUnit', 50)->default('hectare');
            $table->text('notes')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_lots');
    }
};
