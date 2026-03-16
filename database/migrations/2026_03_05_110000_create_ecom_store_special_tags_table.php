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
        Schema::create('ecom_store_special_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('storeId');
            $table->string('tagName', 255);
            $table->string('tagValue', 255);
            $table->text('tagDescription')->nullable();
            $table->boolean('isActive')->default(true);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('storeId');
            $table->index('tagValue');
            $table->index(['storeId', 'deleteStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_store_special_tags');
    }
};
