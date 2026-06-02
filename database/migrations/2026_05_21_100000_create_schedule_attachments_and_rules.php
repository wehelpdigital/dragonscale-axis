<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            // User-facing original filename (preserved for display + the
            // "download" link). The actual file on disk uses a UUID-based
            // name so two uploads of the same filename don't collide.
            $table->string('filename');
            // Path under the `public` disk (storage/app/public/...). We
            // store the relative path so Storage::url() / Storage::disk()
            // can resolve it without knowing the absolute fs location.
            $table->string('storagePath');
            $table->string('mimeType', 100)->nullable();
            $table->unsignedBigInteger('fileSize')->default(0);
            $table->text('description')->nullable();
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
        });

        Schema::create('as_schedule_critical_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            // Plain text — rich-text would be overkill for one-liner
            // reminders ("Always confirm tank-mix the day before",
            // "No spraying after 10am", etc.). Use line-breaks if needed.
            $table->text('ruleText');
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('croppingScheduleId');
            $table->index('deleteStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_attachments');
        Schema::dropIfExists('as_schedule_critical_rules');
    }
};
