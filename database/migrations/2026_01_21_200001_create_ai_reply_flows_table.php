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
        Schema::create('ai_reply_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('flowName', 255);
            $table->text('flowDescription')->nullable();

            // Flow data stores the entire flow structure as JSON
            // Contains nodes and connections
            $table->json('flowData')->nullable();

            // Priority determines which flow is checked first
            $table->integer('priority')->default(0);

            // Status
            $table->boolean('isActive')->default(true);
            $table->boolean('isDefault')->default(false); // Default flow for unmatched queries

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('isActive');
            $table->index('isDefault');
            $table->index('priority');
            $table->index('delete_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_reply_flows');
    }
};
