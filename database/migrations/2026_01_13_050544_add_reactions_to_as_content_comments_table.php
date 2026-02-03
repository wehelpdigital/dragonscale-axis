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
        Schema::table('as_content_comments', function (Blueprint $table) {
            $table->unsignedInteger('likesCount')->default(0)->after('isPinned');
            $table->unsignedInteger('heartsCount')->default(0)->after('likesCount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('as_content_comments', function (Blueprint $table) {
            $table->dropColumn(['likesCount', 'heartsCount']);
        });
    }
};
