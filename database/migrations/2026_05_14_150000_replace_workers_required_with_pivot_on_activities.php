<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_activity_workers', function (Blueprint $table) {
            $table->id();
            $table->integer('activityId');
            $table->integer('workerId');
            $table->timestamps();

            $table->index('activityId');
            $table->index('workerId');
            $table->unique(['activityId', 'workerId'], 'unq_activity_worker');
        });

        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_activities', 'workersRequired')) {
                $table->dropColumn('workersRequired');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->integer('workersRequired')->default(0)->after('description');
        });
        Schema::dropIfExists('as_schedule_activity_workers');
    }
};
