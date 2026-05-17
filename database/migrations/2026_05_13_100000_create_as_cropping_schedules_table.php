<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_cropping_schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('usersId');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'setup', 'generated', 'completed', 'archived'])->default('draft');
            $table->tinyInteger('isActive')->default(1);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('usersId');
            $table->index('status');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_cropping_schedules');
    }
};
