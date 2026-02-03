<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('as_topic_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollmentId');
            $table->unsignedBigInteger('topicId');
            $table->datetime('completedAt');
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index(['enrollmentId', 'topicId']);
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_topic_progress');
    }
};
