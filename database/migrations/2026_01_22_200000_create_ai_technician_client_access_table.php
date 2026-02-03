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
        Schema::create('ai_technician_client_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId')->comment('Admin user who owns this access grant');
            $table->unsignedBigInteger('accessClientId')->comment('Reference to clients_access_login.id');
            $table->dateTime('grantedAt')->comment('When access was granted');
            $table->dateTime('expirationDate')->nullable()->comment('Null = lifetime access');
            $table->boolean('isActive')->default(true)->comment('Whether access is currently active');
            $table->text('notes')->nullable()->comment('Optional notes about the client access');
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('accessClientId');
            $table->index('expirationDate');
            $table->index('isActive');
            $table->index('delete_status');

            // Unique constraint to prevent duplicate grants
            $table->unique(['usersId', 'accessClientId', 'delete_status'], 'unique_client_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_technician_client_access');
    }
};
