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
        Schema::table('ai_rag_files', function (Blueprint $table) {
            $table->string('fileHash', 64)->nullable()->after('fileType');
            $table->index('fileHash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_rag_files', function (Blueprint $table) {
            $table->dropIndex(['fileHash']);
            $table->dropColumn('fileHash');
        });
    }
};
