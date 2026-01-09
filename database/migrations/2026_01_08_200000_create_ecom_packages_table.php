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
        Schema::create('ecom_packages', function (Blueprint $table) {
            $table->id();
            $table->string('packageName', 255);
            $table->text('packageDescription')->nullable();
            $table->decimal('calculatedPrice', 15, 2)->default(0); // Sum of all items
            $table->decimal('packagePrice', 15, 2)->default(0); // User-set price (can be different for discounts)
            $table->enum('packageStatus', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('usersId')->nullable(); // Created by user
            $table->tinyInteger('deleteStatus')->default(1); // 1 = active, 0 = deleted
            $table->timestamps();

            // Indexes
            $table->index('packageName');
            $table->index('packageStatus');
            $table->index('usersId');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_packages');
    }
};
