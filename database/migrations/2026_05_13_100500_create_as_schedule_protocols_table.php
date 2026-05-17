<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_protocols', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->enum('protocolType', ['text', 'file', 'both'])->default('text');
            $table->longText('protocolContent')->nullable();
            $table->string('protocolFile')->nullable();
            $table->string('protocolFileOriginalName')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_protocols');
    }
};
