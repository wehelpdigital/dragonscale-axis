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
        // Main forms table
        Schema::create('crm_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('formName');
            $table->string('formSlug')->unique();
            $table->text('formDescription')->nullable();
            $table->enum('formStatus', ['draft', 'active', 'inactive'])->default('draft');
            $table->json('formSettings')->nullable(); // redirect URL, success message, styling, etc.
            $table->json('formElements')->nullable(); // form fields/elements structure
            $table->unsignedInteger('submitCount')->default(0);
            $table->unsignedInteger('viewCount')->default(0);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status']);
            $table->index('formSlug');
            $table->index('formStatus');
        });

        // Form submissions table
        Schema::create('crm_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formId');
            $table->json('submissionData'); // all submitted field values
            $table->string('submitterIp', 45)->nullable();
            $table->text('submitterUserAgent')->nullable();
            $table->string('submitterEmail')->nullable(); // extracted for quick reference
            $table->string('submitterName')->nullable(); // extracted for quick reference
            $table->enum('submissionStatus', ['new', 'read', 'processed', 'archived'])->default('new');
            $table->timestamp('processedAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('formId')->references('id')->on('crm_forms')->onDelete('cascade');
            $table->index(['formId', 'delete_status']);
            $table->index('submissionStatus');
            $table->index('submitterEmail');
        });

        // Form triggers/automations table
        Schema::create('crm_form_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formId');
            $table->string('triggerName');
            $table->text('triggerDescription')->nullable();
            $table->enum('triggerEvent', ['on_submit', 'on_status_change'])->default('on_submit');
            $table->enum('triggerStatus', ['active', 'inactive'])->default('active');
            $table->json('triggerFlow')->nullable(); // automation flow steps
            $table->unsignedInteger('executionCount')->default(0);
            $table->timestamp('lastExecutedAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('formId')->references('id')->on('crm_forms')->onDelete('cascade');
            $table->index(['formId', 'delete_status']);
            $table->index('triggerStatus');
        });

        // Trigger execution logs
        Schema::create('crm_form_trigger_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('triggerId');
            $table->unsignedBigInteger('submissionId');
            $table->enum('executionStatus', ['success', 'failed', 'partial'])->default('success');
            $table->json('executionDetails')->nullable(); // step-by-step results
            $table->text('errorMessage')->nullable();
            $table->timestamps();

            $table->foreign('triggerId')->references('id')->on('crm_form_triggers')->onDelete('cascade');
            $table->foreign('submissionId')->references('id')->on('crm_form_submissions')->onDelete('cascade');
            $table->index(['triggerId', 'executionStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_form_trigger_logs');
        Schema::dropIfExists('crm_form_triggers');
        Schema::dropIfExists('crm_form_submissions');
        Schema::dropIfExists('crm_forms');
    }
};
