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
        Schema::create('ai_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('filterName', 255);
            $table->text('filterDescription')->nullable();

            // Trigger configuration - when should this filter activate
            $table->enum('triggerType', ['keywords', 'topic', 'question_type', 'always'])->default('keywords');
            $table->text('triggerValue')->nullable(); // comma-separated keywords or topic description

            // Action configuration - what should the AI do
            $table->enum('actionType', [
                'custom_response',    // Respond with specific text
                'add_instruction',    // Add instruction to AI context
                'include_info',       // Always include certain info
                'exclude_topic',      // Prevent discussing certain topics
                'redirect'            // Redirect to different response
            ])->default('add_instruction');
            $table->text('actionValue'); // The instruction or response text

            // Priority and status
            $table->integer('priority')->default(0); // Higher = processed first
            $table->boolean('isActive')->default(true);

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('triggerType');
            $table->index('isActive');
            $table->index('delete_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_filters');
    }
};
