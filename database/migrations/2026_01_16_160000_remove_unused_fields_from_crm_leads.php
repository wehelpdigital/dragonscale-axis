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
        Schema::table('crm_leads', function (Blueprint $table) {
            // Remove Interest & Requirements fields
            $table->dropColumn('interestCategory');
            $table->dropColumn('interestDetails');
            $table->dropColumn('budgetRange');
            $table->dropColumn('timeline');

            // Remove Follow-up fields
            $table->dropColumn('nextFollowUpDate');
            $table->dropColumn('nextFollowUpTime');
            $table->dropColumn('followUpNotes');

            // Remove Deal Value fields
            $table->dropColumn('estimatedValue');
            $table->dropColumn('actualValue');

            // Remove Tags
            $table->dropColumn('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            // Restore Interest & Requirements fields
            $table->string('interestCategory', 100)->nullable();
            $table->text('interestDetails')->nullable();
            $table->string('budgetRange', 100)->nullable();
            $table->enum('timeline', ['immediate', 'short_term', 'medium_term', 'long_term', 'undecided'])->nullable();

            // Restore Follow-up fields
            $table->date('nextFollowUpDate')->nullable();
            $table->time('nextFollowUpTime')->nullable();
            $table->text('followUpNotes')->nullable();

            // Restore Deal Value fields
            $table->decimal('estimatedValue', 15, 2)->nullable();
            $table->decimal('actualValue', 15, 2)->nullable();

            // Restore Tags
            $table->text('tags')->nullable();
        });
    }
};
