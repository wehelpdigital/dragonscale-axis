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
        Schema::create('ecom_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('reportName', 255);
            $table->string('reportType', 50); // overview, by_store, by_product, trend, discount, commission
            $table->date('dateFrom')->nullable();
            $table->date('dateTo')->nullable();
            $table->json('filters')->nullable(); // Store IDs, Product IDs, etc.
            $table->json('reportData')->nullable(); // Cached report results
            $table->string('groupBy', 50)->nullable(); // daily, weekly, monthly
            $table->text('notes')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('usersId');
            $table->index('reportType');
            $table->index('deleteStatus');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_sales_reports');
    }
};
