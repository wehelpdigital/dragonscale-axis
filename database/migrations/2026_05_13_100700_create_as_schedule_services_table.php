<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_services', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('serviceName');
            $table->text('description')->nullable();
            $table->decimal('serviceCost', 14, 2)->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_services');
    }
};
