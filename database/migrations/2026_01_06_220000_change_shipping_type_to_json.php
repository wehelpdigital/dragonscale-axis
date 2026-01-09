<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes shippingType from ENUM (single value) to JSON (multiple values).
     * This allows shipping methods to support multiple shipping types (e.g., Regular + COD).
     */
    public function up(): void
    {
        // Step 1: Get all existing shipping data
        $existingShipping = DB::table('ecom_products_shipping')
            ->select('id', 'shippingType')
            ->get();

        // Step 2: Add a new temporary column for JSON data
        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->json('shippingTypeNew')->nullable()->after('shippingType');
        });

        // Step 3: Convert existing single values to JSON arrays
        foreach ($existingShipping as $shipping) {
            $currentType = $shipping->shippingType;
            // Convert single value to array
            $jsonType = json_encode([$currentType ?: 'Regular']);

            DB::table('ecom_products_shipping')
                ->where('id', $shipping->id)
                ->update(['shippingTypeNew' => $jsonType]);
        }

        // Step 4: Drop the old column and rename the new one
        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->dropColumn('shippingType');
        });

        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->renameColumn('shippingTypeNew', 'shippingType');
        });

        // Step 5: Set default for shippingType column
        DB::statement("ALTER TABLE ecom_products_shipping MODIFY shippingType JSON NOT NULL DEFAULT (JSON_ARRAY('Regular'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Get all existing shipping data
        $existingShipping = DB::table('ecom_products_shipping')
            ->select('id', 'shippingType')
            ->get();

        // Step 2: Add a new temporary column for ENUM data
        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->enum('shippingTypeOld', ['Regular', 'Cash on Delivery', 'Cash on Pickup'])
                ->default('Regular')
                ->nullable()
                ->after('shippingType');
        });

        // Step 3: Convert JSON arrays back to single values (take first value)
        foreach ($existingShipping as $shipping) {
            $types = json_decode($shipping->shippingType, true);
            $singleType = is_array($types) && count($types) > 0 ? $types[0] : 'Regular';

            DB::table('ecom_products_shipping')
                ->where('id', $shipping->id)
                ->update(['shippingTypeOld' => $singleType]);
        }

        // Step 4: Drop the JSON column and rename the new one
        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->dropColumn('shippingType');
        });

        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->renameColumn('shippingTypeOld', 'shippingType');
        });
    }
};
