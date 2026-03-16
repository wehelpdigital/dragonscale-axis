<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes API settings global instead of per-user.
     */
    public function up(): void
    {
        // Drop foreign key and make usersId nullable for global settings
        Schema::table('recom_api_settings', function (Blueprint $table) {
            $table->dropForeign(['usersId']);
            $table->unsignedBigInteger('usersId')->nullable()->change();
        });

        // Set existing records to NULL (global)
        DB::table('recom_api_settings')->update(['usersId' => null]);

        // Remove duplicate providers, keeping the most recent one
        $providers = ['claude', 'openai', 'gemini'];
        foreach ($providers as $provider) {
            $records = DB::table('recom_api_settings')
                ->where('provider', $provider)
                ->where('delete_status', 'active')
                ->orderBy('updated_at', 'desc')
                ->get();

            if ($records->count() > 1) {
                // Keep the first (most recent), delete the rest
                $keepId = $records->first()->id;
                DB::table('recom_api_settings')
                    ->where('provider', $provider)
                    ->where('delete_status', 'active')
                    ->where('id', '!=', $keepId)
                    ->update(['delete_status' => 'deleted']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recom_api_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('usersId')->nullable(false)->change();
            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
