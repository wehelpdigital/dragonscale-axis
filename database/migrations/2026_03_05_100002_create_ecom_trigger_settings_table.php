<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ecom_trigger_settings', function (Blueprint $table) {
            $table->id();
            $table->string('settingKey', 100)->unique();
            $table->text('settingValue')->nullable();
            $table->string('settingType', 20)->default('string'); // string, json, boolean, integer
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $cronSecret = Str::random(32);

        \DB::table('ecom_trigger_settings')->insert([
            [
                'settingKey' => 'cron_secret_key',
                'settingValue' => $cronSecret,
                'settingType' => 'string',
                'description' => 'Secret key for cron API authentication',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'settingKey' => 'cron_enabled',
                'settingValue' => '1',
                'settingType' => 'boolean',
                'description' => 'Enable or disable cron processing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'settingKey' => 'cron_batch_size',
                'settingValue' => '10',
                'settingType' => 'integer',
                'description' => 'Number of tasks to process per cron run',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'settingKey' => 'cron_last_run',
                'settingValue' => null,
                'settingType' => 'string',
                'description' => 'Last cron execution timestamp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'settingKey' => 'cron_total_runs',
                'settingValue' => '0',
                'settingType' => 'integer',
                'description' => 'Total number of cron runs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_trigger_settings');
    }
};
