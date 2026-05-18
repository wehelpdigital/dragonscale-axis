<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_activity_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->string('versionName', 120);
            $table->text('description')->nullable();
            $table->integer('parentVersionId')->nullable();
            $table->tinyInteger('isOriginal')->default(0);
            $table->tinyInteger('isActive')->default(0);
            $table->integer('versionOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
            $table->index(['croppingScheduleId', 'isActive']);
        });

        // Backfill: every schedule that already has activities gets an
        // "Original" version, and every existing activity is pointed at it.
        // The Original is also flagged isActive so the UI has a default tab
        // selected immediately after the migration runs.
        $scheduleIds = DB::table('as_schedule_activities')
            ->where('deleteStatus', 1)
            ->select('croppingScheduleId')
            ->distinct()
            ->pluck('croppingScheduleId');

        foreach ($scheduleIds as $scheduleId) {
            DB::table('as_schedule_activity_versions')->insert([
                'croppingScheduleId' => $scheduleId,
                'versionName'        => 'Original',
                'description'        => 'Auto-created baseline version. Holds the activities that existed before versioning was introduced.',
                'parentVersionId'    => null,
                'isOriginal'         => 1,
                'isActive'           => 1,
                'versionOrder'       => 0,
                'deleteStatus'       => 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_activity_versions');
    }
};
