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
        Schema::create('recom_crop_breeds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('name', 255);
            $table->enum('cropType', ['corn', 'rice']);
            $table->enum('breedType', ['hybrid', 'inbred', 'opv'])->nullable(); // OPV = Open Pollinated Variety
            $table->enum('cornType', ['yellow', 'white', 'special'])->nullable(); // For corn only
            $table->string('manufacturer', 255)->nullable();
            $table->string('potentialYield', 255)->nullable(); // e.g., "8-10 tons/ha"
            $table->string('maturityDays', 100)->nullable(); // e.g., "110-115 days"
            $table->text('geneProtection')->nullable(); // JSON array of resistances
            $table->text('characteristics')->nullable(); // Additional characteristics
            $table->text('relatedInformation')->nullable(); // All other related info
            $table->boolean('isActive')->default(true);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['cropType', 'breedType', 'delete_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recom_crop_breeds');
    }
};
