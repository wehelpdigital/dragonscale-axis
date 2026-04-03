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
        Schema::table('as_chat_conversations', function (Blueprint $table) {
            $table->string('farmLocation', 255)->nullable()->after('visitorEmail');
            $table->enum('visitorType', ['farm_owner', 'farm_worker', 'other'])->nullable()->after('farmLocation');
            $table->unsignedBigInteger('leadId')->nullable()->after('visitorType');

            $table->index('visitorType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('as_chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['visitorType']);
            $table->dropColumn(['farmLocation', 'visitorType', 'leadId']);
        });
    }
};
