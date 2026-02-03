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
        Schema::create('ai_query_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('ruleName', 255)->comment('Display name of the rule');
            $table->string('ruleCategory', 100)->default('general')->comment('Category: general, formatting, data_preference, terminology, etc.');
            $table->text('ruleDescription')->nullable()->comment('Human-readable description of what this rule does');
            $table->text('rulePrompt')->comment('The actual prompt/instruction to inject into AI queries');
            $table->integer('priority')->default(0)->comment('Higher priority rules are applied first');
            $table->boolean('isEnabled')->default(true)->comment('Whether this rule is active');
            $table->boolean('isSystemRule')->default(false)->comment('System rules cannot be deleted by users');
            $table->json('appliesTo')->nullable()->comment('Optional: specific contexts where rule applies (e.g., ["crops", "agriculture"])');
            $table->timestamps();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['usersId', 'delete_status', 'isEnabled']);
            $table->index(['ruleCategory']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_query_rules');
    }
};
