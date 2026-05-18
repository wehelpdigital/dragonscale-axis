<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_date_notes', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            // Versioned alongside activities — each version maintains its own
            // notes so forking "Budget Cut" doesn't share the Original's
            // commentary. The clone routine in ActivityVersionController
            // duplicates these rows when a fork is created.
            $table->integer('versionId');
            $table->date('noteDate');
            $table->text('noteContent');
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('versionId');
            $table->index('deleteStatus');
            // No DB-level unique constraint on (versionId, noteDate) because
            // soft-deleted rows would still occupy the slot. App-level upsert
            // logic in the controller ensures at most one active row per
            // (version, date) by updating the existing row when present.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_date_notes');
    }
};
