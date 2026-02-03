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
        Schema::create('ai_currency_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->decimal('usdToPhpRate', 10, 4)->default(56.0000)->comment('USD to PHP exchange rate');
            $table->timestamp('lastRateUpdate')->nullable()->comment('Last time rate was fetched from API');
            $table->boolean('autoUpdate')->default(true)->comment('Auto-update rate from API');
            $table->string('apiSource', 100)->default('exchangerate-api')->comment('API source for rate');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_currency_settings');
    }
};
