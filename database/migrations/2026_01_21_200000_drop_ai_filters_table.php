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
        Schema::dropIfExists('ai_filters');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('ai_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('filterName', 255);
            $table->text('filterDescription')->nullable();
            $table->enum('triggerType', ['keywords', 'topic', 'question_type', 'always'])->default('keywords');
            $table->text('triggerValue')->nullable();
            $table->enum('actionType', [
                'custom_response',
                'add_instruction',
                'include_info',
                'exclude_topic',
                'redirect'
            ])->default('add_instruction');
            $table->text('actionValue');
            $table->integer('priority')->default(0);
            $table->boolean('isActive')->default(true);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('triggerType');
            $table->index('isActive');
            $table->index('delete_status');
        });
    }
};
