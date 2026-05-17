<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_generation_groupings', function (Blueprint $table) {
            $table->id();
            $table->integer('generationId');
            $table->string('groupName');
            $table->integer('staggerDays')->default(0);
            $table->integer('groupOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('generationId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_generation_groupings');
    }
};
