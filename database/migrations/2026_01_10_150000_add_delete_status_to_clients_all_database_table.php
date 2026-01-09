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
        Schema::table('clients_all_database', function (Blueprint $table) {
            $table->integer('deleteStatus')->default(1)->after('clientEmailAddress');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients_all_database', function (Blueprint $table) {
            $table->dropIndex(['deleteStatus']);
            $table->dropColumn('deleteStatus');
        });
    }
};
