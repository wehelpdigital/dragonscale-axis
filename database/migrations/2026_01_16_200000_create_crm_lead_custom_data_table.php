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
        Schema::create('crm_lead_custom_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leadId');
            $table->string('fieldName', 255);
            $table->text('fieldValue')->nullable();
            $table->unsignedBigInteger('usersId');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('leadId');
            $table->index('usersId');
            $table->index(['leadId', 'fieldName']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_lead_custom_data');
    }
};
