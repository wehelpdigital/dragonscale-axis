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
        Schema::table('recom_crop_breeds', function (Blueprint $table) {
            $table->string('imagePath')->nullable()->after('relatedInformation');
            $table->string('brochurePath')->nullable()->after('imagePath');
            $table->json('additionalDocuments')->nullable()->after('brochurePath');
            $table->string('sourceUrl')->nullable()->after('additionalDocuments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recom_crop_breeds', function (Blueprint $table) {
            $table->dropColumn(['imagePath', 'brochurePath', 'additionalDocuments', 'sourceUrl']);
        });
    }
};
