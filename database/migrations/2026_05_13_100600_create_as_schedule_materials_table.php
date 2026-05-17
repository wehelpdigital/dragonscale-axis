<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_materials', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('materialName');
            $table->text('description')->nullable();
            $table->string('materialType', 50); // granular, foliar, pesticide, fertilizer, seed, other
            $table->string('unitOfMeasure', 30); // kg, g, ml, l, bottle, sachet, piece
            $table->decimal('priceAmount', 14, 2)->default(0);
            $table->decimal('priceQuantity', 14, 4)->default(1); // e.g. price for 50 (kg)
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('materialType');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_materials');
    }
};
