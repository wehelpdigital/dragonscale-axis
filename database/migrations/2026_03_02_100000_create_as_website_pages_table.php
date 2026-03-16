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
        Schema::create('as_website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('pageName', 100);
            $table->string('pageSlug', 100)->unique();
            $table->string('pageIcon', 50)->nullable();
            $table->longText('pageContent')->nullable();
            $table->string('metaTitle', 255)->nullable();
            $table->text('metaDescription')->nullable();
            $table->string('metaKeywords', 500)->nullable();
            $table->enum('pageStatus', ['draft', 'published'])->default('draft');
            $table->integer('pageOrder')->default(0);
            $table->boolean('isSystemPage')->default(false);
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('pageSlug');
            $table->index('pageStatus');
            $table->index('deleteStatus');
        });

        // Insert default Homepage
        DB::table('as_website_pages')->insert([
            'pageName' => 'Homepage',
            'pageSlug' => 'home',
            'pageIcon' => 'bx-home',
            'pageContent' => '<h1>Welcome to Ani-Senso</h1><p>Edit this page to customize your homepage content.</p>',
            'metaTitle' => 'Ani-Senso - Home',
            'metaDescription' => 'Welcome to Ani-Senso Academy',
            'pageStatus' => 'draft',
            'pageOrder' => 1,
            'isSystemPage' => true,
            'deleteStatus' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_website_pages');
    }
};
