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
        Schema::create('crm_lead_store_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leadId');
            $table->unsignedBigInteger('storeId');
            $table->timestamps();

            // Indexes
            $table->index('leadId');
            $table->index('storeId');
            $table->unique(['leadId', 'storeId']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_lead_store_targets');
    }
};
