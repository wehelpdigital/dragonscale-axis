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
        Schema::create('as_course_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('asCoursesId');
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('userName', 255);
            $table->string('actionType', 100);
            $table->string('entityType', 50);
            $table->integer('entityId')->nullable();
            $table->string('entityName', 255)->nullable();
            $table->string('fieldChanged', 100)->nullable();
            $table->text('previousValue')->nullable();
            $table->text('newValue')->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('asCoursesId');
            $table->index('userId');
            $table->index('actionType');
            $table->index('entityType');
            $table->index('created_at');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_course_audit_logs');
    }
};
