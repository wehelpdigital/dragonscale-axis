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
     * Changes restrictions from product-level to variant-level selection.
     * - Adds variantId column
     * - Migrates existing productId restrictions to include all variants of that product
     */
    public function up(): void
    {
        // Step 1: Add variantId column
        Schema::table('ecom_products_shipping_restrictions', function (Blueprint $table) {
            $table->integer('variantId')->nullable()->after('productId');
            $table->index('variantId');
        });

        // Step 2: Migrate existing product restrictions to variant restrictions
        // For each product restriction, create variant restrictions for all active variants
        $productRestrictions = DB::table('ecom_products_shipping_restrictions')
            ->whereNotNull('productId')
            ->where('deleteStatus', 1)
            ->get();

        foreach ($productRestrictions as $restriction) {
            // Get all active variants for this product
            $variants = DB::table('ecom_products_variants')
                ->where('ecomProductsId', $restriction->productId)
                ->where('deleteStatus', 1)
                ->get();

            if ($variants->count() > 0) {
                // Update the first variant in the original restriction
                $firstVariant = $variants->first();
                DB::table('ecom_products_shipping_restrictions')
                    ->where('id', $restriction->id)
                    ->update(['variantId' => $firstVariant->id]);

                // Create new restrictions for remaining variants
                foreach ($variants->skip(1) as $variant) {
                    DB::table('ecom_products_shipping_restrictions')->insert([
                        'shippingId' => $restriction->shippingId,
                        'storeId' => null,
                        'productId' => $restriction->productId,
                        'variantId' => $variant->id,
                        'deleteStatus' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove duplicate restrictions created for variants (keep only one per product)
        $restrictions = DB::table('ecom_products_shipping_restrictions')
            ->whereNotNull('productId')
            ->whereNotNull('variantId')
            ->where('deleteStatus', 1)
            ->get()
            ->groupBy('productId');

        foreach ($restrictions as $productId => $group) {
            // Keep the first one, delete the rest
            $idsToDelete = $group->skip(1)->pluck('id');
            if ($idsToDelete->count() > 0) {
                DB::table('ecom_products_shipping_restrictions')
                    ->whereIn('id', $idsToDelete)
                    ->delete();
            }
        }

        // Drop the variantId column
        Schema::table('ecom_products_shipping_restrictions', function (Blueprint $table) {
            $table->dropIndex(['variantId']);
            $table->dropColumn('variantId');
        });
    }
};
