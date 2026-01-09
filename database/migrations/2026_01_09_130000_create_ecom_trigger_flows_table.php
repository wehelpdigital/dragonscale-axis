<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table stores trigger flow automations.
     * Each flow starts with a trigger tag and can have multiple connected nodes.
     *
     * Flow Node Types:
     * - trigger_tag: Starting point, linked to ecom_trigger_tags
     * - delay: Wait for X days or minutes before continuing
     * - schedule: Execute at specific date/time
     * - email: Send email with merge tags
     * - y_flow: Split flow into two branches
     * - course_access: Grant access to course via axis_tags
     *
     * Merge Tags Available for Email:
     * {{client_name}}, {{client_email}}, {{product_name}}, {{product_price}},
     * {{discount_name}}, {{user_login}}, {{user_password}}, {{purchase_date}},
     * {{order_number}}
     */
    public function up(): void
    {
        Schema::create('ecom_trigger_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('flowName', 255);
            $table->text('flowDescription')->nullable();
            $table->unsignedBigInteger('triggerTagId')->nullable(); // Starting trigger tag
            $table->json('flowData')->nullable(); // Stores nodes, connections, positions
            $table->boolean('isActive')->default(true);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('triggerTagId');
            $table->index('isActive');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_trigger_flows');
    }
};
