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
        Schema::create('ecom_trigger_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('triggerTagName', 255);
            $table->text('triggerTagDescription')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('triggerTagName');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_trigger_tags');
    }
};
