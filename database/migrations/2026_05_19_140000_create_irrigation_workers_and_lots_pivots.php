<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_irrigation_workers', function (Blueprint $table) {
            $table->id();
            $table->integer('irrigationId');
            $table->integer('workerId');
            $table->timestamps();

            $table->index('irrigationId');
            $table->index('workerId');
            $table->unique(['irrigationId', 'workerId'], 'unq_irrigation_worker');
        });

        Schema::create('as_schedule_irrigation_lots', function (Blueprint $table) {
            $table->id();
            $table->integer('irrigationId');
            $table->integer('lotId');
            $table->timestamps();

            $table->index('irrigationId');
            $table->index('lotId');
            $table->unique(['irrigationId', 'lotId'], 'unq_irrigation_lot');
        });

        // Backfill: every existing irrigation row with a non-null
        // assignedWorkerId becomes a single-worker pivot row so the new
        // many-to-many relation matches the old single-worker assignment.
        // The legacy column stays in place for safety but is no longer the
        // source of truth; reads now go through workers() belongsToMany.
        DB::statement("
            INSERT INTO as_schedule_irrigation_workers (irrigationId, workerId, created_at, updated_at)
            SELECT id, assignedWorkerId, NOW(), NOW()
            FROM as_schedule_irrigations
            WHERE assignedWorkerId IS NOT NULL AND deleteStatus = 1
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_irrigation_workers');
        Schema::dropIfExists('as_schedule_irrigation_lots');
    }
};
