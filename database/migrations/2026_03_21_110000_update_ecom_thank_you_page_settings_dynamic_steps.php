<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecom_thank_you_page_settings', function (Blueprint $table) {
            // Add text below subheading
            $table->string('subHeadingText')->nullable()->after('subHeading');

            // Add JSON column for dynamic steps
            $table->json('whatsNextSteps')->nullable()->after('whatsNextTitle');
        });

        // Migrate existing step data to JSON format
        $settings = DB::table('ecom_thank_you_page_settings')->get();
        foreach ($settings as $setting) {
            $steps = [];
            if (!empty($setting->step1Text)) {
                $steps[] = ['text' => $setting->step1Text];
            }
            if (!empty($setting->step2Text)) {
                $steps[] = ['text' => $setting->step2Text];
            }
            if (!empty($setting->step3Text)) {
                $steps[] = ['text' => $setting->step3Text];
            }

            if (!empty($steps)) {
                DB::table('ecom_thank_you_page_settings')
                    ->where('id', $setting->id)
                    ->update(['whatsNextSteps' => json_encode($steps)]);
            }
        }

        // Drop old step columns
        Schema::table('ecom_thank_you_page_settings', function (Blueprint $table) {
            $table->dropColumn(['step1Text', 'step2Text', 'step3Text']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_thank_you_page_settings', function (Blueprint $table) {
            // Re-add old columns
            $table->string('step1Text')->default('I-ve-verify namin ang payment mo <strong>within 24 hours</strong>.')->after('whatsNextTitle');
            $table->string('step2Text')->default('Makakatanggap ka ng <strong>email confirmation</strong> with login details.')->after('step1Text');
            $table->string('step3Text')->default('Simulan mo na ang <strong>pag-aaral</strong> at magsimulang kumita!')->after('step2Text');
        });

        // Migrate JSON data back to columns
        $settings = DB::table('ecom_thank_you_page_settings')->get();
        foreach ($settings as $setting) {
            if (!empty($setting->whatsNextSteps)) {
                $steps = json_decode($setting->whatsNextSteps, true);
                $update = [];
                if (isset($steps[0]['text'])) $update['step1Text'] = $steps[0]['text'];
                if (isset($steps[1]['text'])) $update['step2Text'] = $steps[1]['text'];
                if (isset($steps[2]['text'])) $update['step3Text'] = $steps[2]['text'];

                if (!empty($update)) {
                    DB::table('ecom_thank_you_page_settings')
                        ->where('id', $setting->id)
                        ->update($update);
                }
            }
        }

        Schema::table('ecom_thank_you_page_settings', function (Blueprint $table) {
            $table->dropColumn(['subHeadingText', 'whatsNextSteps']);
        });
    }
};
