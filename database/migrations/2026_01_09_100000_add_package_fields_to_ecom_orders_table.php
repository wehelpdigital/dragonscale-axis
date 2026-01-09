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
        Schema::table('ecom_orders', function (Blueprint $table) {
            // Package purchase fields
            $table->boolean('isPackage')->default(false)->after('orderNotes');
            $table->unsignedBigInteger('packageId')->nullable()->after('isPackage');
            $table->string('packageName', 255)->nullable()->after('packageId');
            $table->text('packageDescription')->nullable()->after('packageName');
            $table->decimal('packageCalculatedPrice', 15, 2)->nullable()->after('packageDescription');
            $table->decimal('packagePrice', 15, 2)->nullable()->after('packageCalculatedPrice');
            $table->decimal('packageSavings', 15, 2)->nullable()->after('packagePrice');

            // Add index for package lookups
            $table->index('isPackage');
            $table->index('packageId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropIndex(['isPackage']);
            $table->dropIndex(['packageId']);

            $table->dropColumn([
                'isPackage',
                'packageId',
                'packageName',
                'packageDescription',
                'packageCalculatedPrice',
                'packagePrice',
                'packageSavings',
            ]);
        });
    }
};
