<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_progress_markers', function (Blueprint $table) {
            // Bookmark / progress markers the user drops into the activities
            // timeline to remember "where I left off yesterday". Each marker
            // is positioned AFTER its markerDate's activities (i.e., the line
            // sits between markerDate's day-group and the next-later day).
            // Optional noteContent lets the user attach a free-form reminder.
            $table->id();
            $table->integer('croppingScheduleId');
            $table->integer('versionId')->nullable(); // version-scoped, mirrors date notes
            $table->date('markerDate'); // sorts the marker into the timeline at this date
            $table->text('noteContent')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index(['croppingScheduleId', 'versionId']);
            $table->index('markerDate');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_progress_markers');
    }
};
