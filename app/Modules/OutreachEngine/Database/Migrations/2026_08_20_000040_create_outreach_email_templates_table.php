<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable outreach emails. Bodies are HTML and hold {business_name}-style
 * placeholders resolved by TemplateRenderService at send time.
 *
 * sendOrder lets the admin keep several variants in rotation; timesUsed is a
 * denormalised counter so the templates screen can show usage without a join
 * against the (much larger) email-log table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_email_templates')) {
            return;
        }

        Schema::create('outreach_email_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->string('name', 190);
            $table->string('subjectTemplate', 500);
            $table->longText('bodyTemplate');
            $table->boolean('isActive')->default(true);
            $table->unsignedSmallInteger('sendOrder')->default(1);
            $table->unsignedInteger('timesUsed')->default(0);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_email_templates');
    }
};
