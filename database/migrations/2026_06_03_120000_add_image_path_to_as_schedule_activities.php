<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            // Optional single reference image per activity. Path is relative
            // to the `public` disk (e.g. "schedule-activities/3/uuid.jpg")
            // so Storage::url() / asset('storage/...') resolves it.
            $table->string('imagePath', 500)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropColumn('imagePath');
        });
    }
};
