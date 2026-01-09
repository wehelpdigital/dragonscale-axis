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
        Schema::create('ecom_store_smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('storeId');
            $table->string('smtpHost', 255)->nullable();
            $table->integer('smtpPort')->default(587);
            $table->string('smtpUsername', 255)->nullable();
            $table->text('smtpPassword')->nullable(); // Will be encrypted
            $table->enum('smtpEncryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('smtpFromEmail', 255)->nullable();
            $table->string('smtpFromName', 255)->nullable();
            $table->boolean('isActive')->default(0);
            $table->boolean('isVerified')->default(0);
            $table->timestamp('lastTestedAt')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            // Note: Foreign key constraint removed due to column type mismatch with legacy table
            // Application-level referential integrity is enforced through the model relationship
            $table->index('storeId');
            $table->unique(['storeId', 'deleteStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_store_smtp_settings');
    }
};
