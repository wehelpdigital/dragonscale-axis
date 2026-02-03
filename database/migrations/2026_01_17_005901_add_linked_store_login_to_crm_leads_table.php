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
        Schema::table('crm_leads', function (Blueprint $table) {
            // Add linked store login ID (nullable - can be linked to a confirmed store login)
            $table->unsignedBigInteger('linkedStoreLoginId')->nullable()->after('convertedToClientId');
            $table->timestamp('linkedStoreLoginAt')->nullable()->after('linkedStoreLoginId');

            // Add linked client confirmation timestamp (when client was confirmed, not just converted)
            $table->timestamp('linkedClientAt')->nullable()->after('conversionDate');

            // Indexes
            $table->index('linkedStoreLoginId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex(['linkedStoreLoginId']);
            $table->dropColumn(['linkedStoreLoginId', 'linkedStoreLoginAt', 'linkedClientAt']);
        });
    }
};
