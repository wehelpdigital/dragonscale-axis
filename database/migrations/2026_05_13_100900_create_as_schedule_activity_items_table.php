<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_activity_items', function (Blueprint $table) {
            $table->id();
            $table->integer('activityId');
            $table->enum('itemType', ['material', 'service']);
            $table->integer('materialId')->nullable();
            $table->integer('serviceId')->nullable();
            $table->decimal('quantity', 14, 4)->default(1);
            $table->string('notes')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('activityId');
            $table->index(['itemType', 'materialId']);
            $table->index(['itemType', 'serviceId']);
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_activity_items');
    }
};
