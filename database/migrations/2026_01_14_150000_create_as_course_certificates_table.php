<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Certificate Templates Table
     * ---------------------------
     * Stores certificate design templates for each course.
     *
     * The templateData column stores JSON with all design elements:
     * - Text boxes (with font, size, color, position)
     * - Images (uploaded images with position, size)
     * - Icons (from icon library)
     * - Placeholders (dynamic fields like {{student_name}}, {{course_name}}, etc.)
     * - Shapes (lines, rectangles, etc.)
     *
     * Fabric.js canvas state is serialized to JSON for storage.
     */
    public function up(): void
    {
        Schema::create('as_course_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asCoursesId')->unique(); // One certificate per course
            $table->string('certificateName')->default('Certificate of Completion');

            // Paper settings (Letter = 8.5x11 inches)
            $table->enum('paperSize', ['letter', 'a4'])->default('letter');
            $table->enum('orientation', ['landscape', 'portrait'])->default('landscape');

            // Design data - stores Fabric.js canvas JSON
            $table->longText('templateData')->nullable();

            // Background settings
            $table->string('backgroundColor', 20)->default('#ffffff');
            $table->string('backgroundImage')->nullable(); // Path to uploaded background

            // Status
            $table->boolean('isActive')->default(false); // Enable/disable certificate generation
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('asCoursesId');
            $table->index('deleteStatus');
        });

        // Table for storing uploaded certificate assets (images, logos)
        Schema::create('as_certificate_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asCoursesId')->nullable(); // Null = global asset
            $table->string('assetName');
            $table->string('assetPath');
            $table->string('assetType', 50); // image, icon, signature
            $table->integer('fileSize')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index('asCoursesId');
            $table->index('assetType');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_certificate_assets');
        Schema::dropIfExists('as_course_certificates');
    }
};
