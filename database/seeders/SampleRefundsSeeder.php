<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EcomRefundRequest;
use App\Models\EcomRefundItem;
use App\Models\EcomOrder;
use App\Models\EcomOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SampleRefundsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing sample data (disable foreign key checks)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        EcomRefundItem::truncate();
        EcomRefundRequest::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Sample refund 1: Pending refund request (partial - one item)
        $order1 = EcomOrder::find(4);
        if ($order1) {
            $refund1 = EcomRefundRequest::create([
                'orderId' => 4,
                'storeName' => 'Ani-Senso',
                'clientName' => $order1->clientFullName ?: 'Alex Jonez',
                'clientEmail' => $order1->clientEmail,
                'clientPhone' => $order1->clientPhone,
                'refundNumber' => 'REF-20260114-A001',
                'requestReason' => 'Customer received wrong course access. They ordered the Advanced course but were given Basic course access instead.',
                'requestedAt' => Carbon::now()->subDays(2),
                'status' => 'pending',
                'refundType' => 'partial',
                'orderSubtotal' => 850.00,
                'requestedAmount' => 500.00,
                'approvedAmount' => 0,
                'deleteStatus' => 1,
            ]);

            // Add refund item
            $orderItem1 = EcomOrderItem::where('orderId', 4)->where('productName', 'like', '%Max Yield%')->first();
            if ($orderItem1) {
                EcomRefundItem::create([
                    'refundRequestId' => $refund1->id,
                    'orderItemId' => $orderItem1->id,
                    'productId' => $orderItem1->productId,
                    'variantId' => $orderItem1->variantId,
                    'productName' => $orderItem1->productName,
                    'variantName' => $orderItem1->variantName,
                    'productStore' => $orderItem1->productStore,
                    'originalQuantity' => $orderItem1->quantity,
                    'refundQuantity' => 1,
                    'unitPrice' => $orderItem1->unitPrice,
                    'refundAmount' => 500.00,
                    'deleteStatus' => 1,
                ]);
            }
        }

        // Sample refund 2: Approved refund (waiting to be processed)
        $order2 = EcomOrder::find(9);
        if ($order2) {
            $refund2 = EcomRefundRequest::create([
                'orderId' => 9,
                'storeName' => 'Ani-Senso',
                'clientName' => $order2->clientFullName ?: 'Alex Jonez',
                'clientEmail' => $order2->clientEmail,
                'clientPhone' => $order2->clientPhone,
                'refundNumber' => 'REF-20260113-B002',
                'requestReason' => 'Product did not meet expectations. Customer was unable to access the course materials properly.',
                'requestedAt' => Carbon::now()->subDays(3),
                'status' => 'approved',
                'refundType' => 'full',
                'orderSubtotal' => 500.00,
                'requestedAmount' => 500.00,
                'approvedAmount' => 450.00, // Partial approval
                'adminNotes' => 'Approved partial refund after customer used the course for 3 days. Deducted 10% usage fee.',
                'deleteStatus' => 1,
            ]);

            $orderItem2 = EcomOrderItem::where('orderId', 9)->first();
            if ($orderItem2) {
                EcomRefundItem::create([
                    'refundRequestId' => $refund2->id,
                    'orderItemId' => $orderItem2->id,
                    'productId' => $orderItem2->productId,
                    'variantId' => $orderItem2->variantId,
                    'productName' => $orderItem2->productName,
                    'variantName' => $orderItem2->variantName,
                    'productStore' => $orderItem2->productStore,
                    'originalQuantity' => $orderItem2->quantity,
                    'refundQuantity' => 1,
                    'unitPrice' => $orderItem2->unitPrice,
                    'refundAmount' => 500.00,
                    'deleteStatus' => 1,
                ]);
            }
        }

        // Sample refund 3: Processed refund (completed)
        $order3 = EcomOrder::find(10);
        if ($order3) {
            $refund3 = EcomRefundRequest::create([
                'orderId' => 10,
                'storeName' => 'Ani-Senso',
                'clientName' => $order3->clientFullName ?: 'Alex Jonez',
                'clientEmail' => $order3->clientEmail,
                'clientPhone' => $order3->clientPhone,
                'refundNumber' => 'REF-20260110-C003',
                'requestReason' => 'Customer accidentally purchased duplicate subscription. Already has active subscription from previous order.',
                'requestedAt' => Carbon::now()->subDays(5),
                'status' => 'processed',
                'refundType' => 'full',
                'orderSubtotal' => 500.00,
                'requestedAmount' => 500.00,
                'approvedAmount' => 500.00,
                'processedBy' => 1,
                'processedAt' => Carbon::now()->subDays(4),
                'adminNotes' => 'Full refund approved - duplicate purchase verified.',
                'deleteStatus' => 1,
            ]);

            // Update order status to refunded
            $order3->orderStatus = 'refunded';
            $order3->save();

            $orderItem3 = EcomOrderItem::where('orderId', 10)->first();
            if ($orderItem3) {
                EcomRefundItem::create([
                    'refundRequestId' => $refund3->id,
                    'orderItemId' => $orderItem3->id,
                    'productId' => $orderItem3->productId,
                    'variantId' => $orderItem3->variantId,
                    'productName' => $orderItem3->productName,
                    'variantName' => $orderItem3->variantName,
                    'productStore' => $orderItem3->productStore,
                    'originalQuantity' => $orderItem3->quantity,
                    'refundQuantity' => 1,
                    'unitPrice' => $orderItem3->unitPrice,
                    'refundAmount' => 500.00,
                    'deleteStatus' => 1,
                ]);
            }
        }

        // Sample refund 4: Rejected refund
        $order4 = EcomOrder::find(15);
        if ($order4) {
            $refund4 = EcomRefundRequest::create([
                'orderId' => 15,
                'storeName' => 'Alcala Mushroom Farm',
                'clientName' => $order4->clientFullName ?: 'Alex Jonez',
                'clientEmail' => $order4->clientEmail,
                'clientPhone' => $order4->clientPhone,
                'refundNumber' => 'REF-20260112-D004',
                'requestReason' => 'Customer claims mushrooms were not fresh upon delivery.',
                'requestedAt' => Carbon::now()->subDays(4),
                'status' => 'rejected',
                'refundType' => 'full',
                'orderSubtotal' => 350.00,
                'requestedAmount' => 350.00,
                'approvedAmount' => 0,
                'processedBy' => 1,
                'processedAt' => Carbon::now()->subDays(3),
                'rejectionReason' => 'Delivery confirmation shows package was received in good condition. Customer opened complaint 3 days after delivery which exceeds our 24-hour freshness guarantee claim window.',
                'adminNotes' => 'Photos from delivery driver show intact packaging. No evidence of spoilage at time of delivery.',
                'deleteStatus' => 1,
            ]);

            $orderItem4 = EcomOrderItem::where('orderId', 15)->first();
            if ($orderItem4) {
                EcomRefundItem::create([
                    'refundRequestId' => $refund4->id,
                    'orderItemId' => $orderItem4->id,
                    'productId' => $orderItem4->productId,
                    'variantId' => $orderItem4->variantId,
                    'productName' => $orderItem4->productName,
                    'variantName' => $orderItem4->variantName,
                    'productStore' => $orderItem4->productStore,
                    'originalQuantity' => $orderItem4->quantity,
                    'refundQuantity' => 1,
                    'unitPrice' => $orderItem4->unitPrice,
                    'refundAmount' => 350.00,
                    'deleteStatus' => 1,
                ]);
            }
        }

        // Sample refund 5: Another pending refund (different store)
        $order5 = EcomOrder::find(16);
        if ($order5) {
            $refund5 = EcomRefundRequest::create([
                'orderId' => 16,
                'storeName' => 'Ani-Senso',
                'clientName' => $order5->clientFullName ?: 'Alex Jonez',
                'clientEmail' => $order5->clientEmail,
                'clientPhone' => $order5->clientPhone,
                'refundNumber' => 'REF-20260114-E005',
                'requestReason' => 'Calculator tool is not compatible with customer\'s device. Requesting refund to purchase different product.',
                'requestedAt' => Carbon::now()->subHours(6),
                'status' => 'pending',
                'refundType' => 'full',
                'orderSubtotal' => 150.00,
                'requestedAmount' => 150.00,
                'approvedAmount' => 0,
                'deleteStatus' => 1,
            ]);

            $orderItem5 = EcomOrderItem::where('orderId', 16)->first();
            if ($orderItem5) {
                EcomRefundItem::create([
                    'refundRequestId' => $refund5->id,
                    'orderItemId' => $orderItem5->id,
                    'productId' => $orderItem5->productId,
                    'variantId' => $orderItem5->variantId,
                    'productName' => $orderItem5->productName,
                    'variantName' => $orderItem5->variantName,
                    'productStore' => $orderItem5->productStore,
                    'originalQuantity' => $orderItem5->quantity,
                    'refundQuantity' => 1,
                    'unitPrice' => $orderItem5->unitPrice,
                    'refundAmount' => 150.00,
                    'deleteStatus' => 1,
                ]);
            }
        }

        $this->command->info('Sample refund requests created successfully!');
        $this->command->info('- 2 Pending refunds');
        $this->command->info('- 1 Approved refund');
        $this->command->info('- 1 Processed refund');
        $this->command->info('- 1 Rejected refund');
    }
}
