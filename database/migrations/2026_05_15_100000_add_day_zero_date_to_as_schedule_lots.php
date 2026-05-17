<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->date('dayZeroDate')->nullable()->after('lotSizeUnit');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->dropColumn('dayZeroDate');
        });
    }
};
