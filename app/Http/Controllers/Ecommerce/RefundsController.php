<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomRefundRequest;
use App\Models\EcomRefundItem;
use App\Models\EcomRefundAttachment;
use App\Models\EcomRefundAuditLog;
use App\Models\EcomOrder;
use App\Models\EcomOrderItem;
use App\Models\EcomOrderAuditLog;
use App\Models\EcomProductStore;
use App\Models\EcomProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class RefundsController extends Controller
{
    /**
     * Display the refunds page.
     */
    public function index()
    {
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();
        return view('ecommerce.refunds.index', compact('stores'));
    }

    /**
     * Get refunds data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = EcomRefundRequest::with(['order', 'items'])
            ->active()
            ->orderBy('requestedAt', 'desc');

        // Apply filters
        if ($request->filled('refundNumber')) {
            $query->where('refundNumber', 'like', '%' . $request->refundNumber . '%');
        }

        if ($request->filled('orderNumber')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('orderNumber', 'like', '%' . $request->orderNumber . '%');
            });
        }

        if ($request->filled('clientName')) {
            $query->where('clientName', 'like', '%' . $request->clientName . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('storeName')) {
            $query->where('storeName', $request->storeName);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('requestedAt', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('requestedAt', '<=', $request->dateTo);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $total = $query->count();
        $refunds = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $data = $refunds->map(function ($refund) {
            return [
                'id' => $refund->id,
                'refundNumber' => $refund->refundNumber,
                'orderNumber' => $refund->order->orderNumber ?? 'N/A',
                'orderId' => $refund->orderId,
                'clientName' => $refund->clientName,
                'clientEmail' => $refund->clientEmail,
                'clientPhone' => $refund->clientPhone,
                'storeName' => $refund->storeName ?? 'Multiple Stores',
                'status' => $refund->status,
                'statusLabel' => $refund->statusLabel,
                'statusBadgeClass' => $refund->statusBadgeClass,
                'refundType' => $refund->refundType,
                'orderSubtotal' => $refund->orderSubtotal,
                'requestedAmount' => $refund->requestedAmount,
                'approvedAmount' => $refund->approvedAmount,
                'formattedOrderSubtotal' => $refund->formattedOrderSubtotal,
                'formattedRequestedAmount' => $refund->formattedRequestedAmount,
                'formattedApprovedAmount' => $refund->formattedApprovedAmount,
                'requestReason' => $refund->requestReason,
                'adminNotes' => $refund->adminNotes,
                'rejectionReason' => $refund->rejectionReason,
                'requestedAt' => $refund->formattedRequestedAt,
                'processedAt' => $refund->formattedProcessedAt,
                'itemCount' => $refund->items->where('deleteStatus', 1)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }

    /**
     * Get order details for creating a refund request.
     */
    public function getOrderForRefund(Request $request)
    {
        $orderNumber = $request->input('orderNumber');

        $order = EcomOrder::with(['items' => function ($q) {
            $q->where('deleteStatus', 1);
        }])
            ->active()
            ->where('orderNumber', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Check if order is eligible for refund (must be complete or paid)
        if (!in_array($order->orderStatus, ['complete', 'paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be Complete or Paid to request a refund. Current status: ' . ucfirst($order->orderStatus)
            ], 400);
        }

        // Check if there's already a pending/approved refund for this order
        $existingRefund = EcomRefundRequest::active()
            ->where('orderId', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingRefund) {
            return response()->json([
                'success' => false,
                'message' => 'This order already has a pending or approved refund request (Ref: ' . $existingRefund->refundNumber . ')'
            ], 400);
        }

        // Calculate refundable amount (excludes shipping & discount - only what customer paid for products)
        $refundableAmount = $order->subtotal - $order->discountTotal;

        // Calculate discount multiplier for prorating refunds per item
        // e.g., if subtotal=150 and discount=49.50, multiplier = (150-49.50)/150 = 0.67
        $discountMultiplier = $order->subtotal > 0
            ? ($order->subtotal - $order->discountTotal) / $order->subtotal
            : 1;

        // Get previously refunded amounts for this order
        $previouslyRefunded = EcomRefundRequest::active()
            ->where('orderId', $order->id)
            ->where('status', 'processed')
            ->sum('approvedAmount');

        $remainingRefundable = $refundableAmount - $previouslyRefunded;

        // Format items for response
        $items = $order->items->map(function ($item) use ($discountMultiplier) {
            // Check how many of this item have been refunded previously
            $refundedQty = EcomRefundItem::active()
                ->where('orderItemId', $item->id)
                ->whereHas('refundRequest', function ($q) {
                    $q->where('status', 'processed');
                })
                ->sum('refundQuantity');

            $remainingQty = $item->quantity - $refundedQty;

            // Calculate effective unit price after discount proration
            $effectiveUnitPrice = round($item->unitPrice * $discountMultiplier, 2);

            return [
                'id' => $item->id,
                'productId' => $item->productId,
                'productName' => $item->productName,
                'productStore' => $item->productStore,
                'variantId' => $item->variantId,
                'variantName' => $item->variantName,
                'variantImage' => $item->variantImage,
                'unitPrice' => $item->unitPrice,
                'effectiveUnitPrice' => $effectiveUnitPrice,
                'formattedUnitPrice' => '₱' . number_format($effectiveUnitPrice, 2),
                'formattedOriginalPrice' => '₱' . number_format($item->unitPrice, 2),
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'formattedSubtotal' => '₱' . number_format($item->subtotal * $discountMultiplier, 2),
                'refundedQuantity' => $refundedQty,
                'remainingQuantity' => $remainingQty,
                'isFullyRefunded' => $remainingQty <= 0,
            ];
        });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->orderNumber,
                'orderStatus' => $order->orderStatus,
                'clientName' => $order->clientFullName,
                'clientEmail' => $order->clientEmail,
                'clientPhone' => $order->clientPhone,
                'subtotal' => $order->subtotal,
                'discountTotal' => $order->discountTotal,
                'shippingTotal' => $order->shippingTotal,
                'grandTotal' => $order->grandTotal,
                'formattedSubtotal' => '₱' . number_format($order->subtotal, 2),
                'formattedDiscountTotal' => '₱' . number_format($order->discountTotal, 2),
                'formattedShippingTotal' => '₱' . number_format($order->shippingTotal, 2),
                'formattedGrandTotal' => '₱' . number_format($order->grandTotal, 2),
                'refundableAmount' => $refundableAmount,
                'formattedRefundableAmount' => '₱' . number_format($refundableAmount, 2),
                'previouslyRefunded' => $previouslyRefunded,
                'formattedPreviouslyRefunded' => '₱' . number_format($previouslyRefunded, 2),
                'remainingRefundable' => $remainingRefundable,
                'formattedRemainingRefundable' => '₱' . number_format($remainingRefundable, 2),
                'createdAt' => $order->created_at->format('M j, Y g:i A'),
            ],
            'items' => $items,
        ]);
    }

    /**
     * Create a new refund request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orderId' => 'required|exists:ecom_orders,id',
            'requestReason' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.orderItemId' => 'required|exists:ecom_order_items,id',
            'items.*.refundQuantity' => 'required|integer|min:1',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:51200', // 50MB max per file
        ], [
            'orderId.required' => 'Order ID is required',
            'requestReason.required' => 'Reason for refund is required',
            'items.required' => 'At least one item must be selected for refund',
            'attachments.max' => 'Maximum 10 files allowed',
            'attachments.*.max' => 'Each file must not exceed 50MB',
            'attachments.*.mimes' => 'Only images (jpg, png, gif, webp) and videos (mp4, mov, avi, webm) are allowed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $order = EcomOrder::with('items')->find($request->orderId);

            // Calculate discount multiplier for prorating refunds per item
            $discountMultiplier = $order->subtotal > 0
                ? ($order->subtotal - $order->discountTotal) / $order->subtotal
                : 1;

            // Calculate total refund amount from items
            $totalRefundAmount = 0;
            $refundItemsData = [];
            $storeName = null;

            foreach ($request->items as $itemData) {
                $orderItem = EcomOrderItem::find($itemData['orderItemId']);
                if (!$orderItem || $orderItem->orderId != $order->id) {
                    throw new \Exception('Invalid order item');
                }

                $refundQty = $itemData['refundQuantity'];
                if ($refundQty > $orderItem->quantity) {
                    throw new \Exception('Refund quantity cannot exceed order quantity for ' . $orderItem->productName);
                }

                // Apply discount multiplier to get effective refund amount
                $effectiveUnitPrice = round($orderItem->unitPrice * $discountMultiplier, 2);
                $itemRefundAmount = $refundQty * $effectiveUnitPrice;
                $totalRefundAmount += $itemRefundAmount;

                // Track store name (use first item's store)
                if (!$storeName) {
                    $storeName = $orderItem->productStore;
                }

                $refundItemsData[] = [
                    'orderItemId' => $orderItem->id,
                    'productId' => $orderItem->productId,
                    'variantId' => $orderItem->variantId,
                    'productName' => $orderItem->productName,
                    'variantName' => $orderItem->variantName,
                    'productStore' => $orderItem->productStore,
                    'originalQuantity' => $orderItem->quantity,
                    'refundQuantity' => $refundQty,
                    'unitPrice' => $effectiveUnitPrice, // Use effective price after discount
                    'refundAmount' => $itemRefundAmount,
                    'deleteStatus' => 1,
                ];
            }

            // Determine refund type (excludes shipping & discount - only what customer paid for products)
            $orderSubtotal = $order->subtotal - $order->discountTotal;
            $refundType = ($totalRefundAmount >= $orderSubtotal) ? 'full' : 'partial';

            // Create refund request
            $refundRequest = EcomRefundRequest::create([
                'orderId' => $order->id,
                'storeName' => $storeName,
                'clientName' => $order->clientFullName,
                'clientEmail' => $order->clientEmail,
                'clientPhone' => $order->clientPhone,
                'refundNumber' => EcomRefundRequest::generateRefundNumber(),
                'requestReason' => $request->requestReason,
                'requestedAt' => now(),
                'status' => 'pending',
                'refundType' => $refundType,
                'orderSubtotal' => $orderSubtotal,
                'requestedAmount' => $totalRefundAmount,
                'approvedAmount' => 0,
                'deleteStatus' => 1,
            ]);

            // Create refund items
            foreach ($refundItemsData as $itemData) {
                $itemData['refundRequestId'] = $refundRequest->id;
                EcomRefundItem::create($itemData);
            }

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $uploadPath = public_path('images/refunds/' . $refundRequest->id);

                // Create directory if it doesn't exist
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }

                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $fileSize = $file->getSize();

                    // Determine file type
                    $fileType = str_starts_with($mimeType, 'video/') ? 'video' : 'image';

                    // Generate unique filename
                    $extension = $file->getClientOriginalExtension();
                    $fileName = time() . '_' . uniqid() . '.' . $extension;

                    // Move file to destination
                    $file->move($uploadPath, $fileName);

                    // Save to database
                    EcomRefundAttachment::create([
                        'refundRequestId' => $refundRequest->id,
                        'fileName' => $originalName,
                        'filePath' => 'images/refunds/' . $refundRequest->id . '/' . $fileName,
                        'fileType' => $fileType,
                        'mimeType' => $mimeType,
                        'fileSize' => $fileSize,
                        'deleteStatus' => 1,
                    ]);
                }
            }

            DB::commit();

            // Log audit trail
            $attachmentCount = $request->hasFile('attachments') ? count($request->file('attachments')) : 0;
            EcomRefundAuditLog::logCreation($refundRequest, $refundItemsData, $attachmentCount);

            Log::info('Refund request created', [
                'refund_number' => $refundRequest->refundNumber,
                'order_number' => $order->orderNumber,
                'amount' => $totalRefundAmount,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund request created successfully',
                'refund' => [
                    'id' => $refundRequest->id,
                    'refundNumber' => $refundRequest->refundNumber,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating refund request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get refund request details.
     */
    public function show($id)
    {
        $refund = EcomRefundRequest::with(['order', 'items', 'attachments', 'processedByUser'])
            ->active()
            ->find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund request not found'
            ], 404);
        }

        $items = $refund->items->where('deleteStatus', 1)->map(function ($item) {
            return [
                'id' => $item->id,
                'orderItemId' => $item->orderItemId,
                'productId' => $item->productId,
                'productName' => $item->productName,
                'variantName' => $item->variantName,
                'productStore' => $item->productStore,
                'originalQuantity' => $item->originalQuantity,
                'refundQuantity' => $item->refundQuantity,
                'unitPrice' => $item->unitPrice,
                'formattedUnitPrice' => $item->formattedUnitPrice,
                'refundAmount' => $item->refundAmount,
                'formattedRefundAmount' => $item->formattedRefundAmount,
            ];
        });

        // Get attachments
        $attachments = $refund->attachments->where('deleteStatus', 1)->map(function ($attachment) {
            return [
                'id' => $attachment->id,
                'fileName' => $attachment->fileName,
                'filePath' => $attachment->filePath,
                'url' => $attachment->url,
                'fileType' => $attachment->fileType,
                'mimeType' => $attachment->mimeType,
                'fileSize' => $attachment->fileSize,
                'formattedFileSize' => $attachment->formattedFileSize,
                'isImage' => $attachment->isImage,
                'isVideo' => $attachment->isVideo,
            ];
        });

        return response()->json([
            'success' => true,
            'refund' => [
                'id' => $refund->id,
                'refundNumber' => $refund->refundNumber,
                'orderId' => $refund->orderId,
                'orderNumber' => $refund->order->orderNumber ?? 'N/A',
                'orderGrandTotal' => $refund->order->grandTotal ?? 0,
                'formattedOrderGrandTotal' => '₱' . number_format($refund->order->grandTotal ?? 0, 2),
                'clientName' => $refund->clientName,
                'clientEmail' => $refund->clientEmail,
                'clientPhone' => $refund->clientPhone,
                'storeName' => $refund->storeName,
                'status' => $refund->status,
                'statusLabel' => $refund->statusLabel,
                'statusBadgeClass' => $refund->statusBadgeClass,
                'refundType' => $refund->refundType,
                'orderSubtotal' => $refund->orderSubtotal,
                'requestedAmount' => $refund->requestedAmount,
                'approvedAmount' => $refund->approvedAmount,
                'formattedOrderSubtotal' => $refund->formattedOrderSubtotal,
                'formattedRequestedAmount' => $refund->formattedRequestedAmount,
                'formattedApprovedAmount' => $refund->formattedApprovedAmount,
                'requestReason' => $refund->requestReason,
                'adminNotes' => $refund->adminNotes,
                'rejectionReason' => $refund->rejectionReason,
                'requestedAt' => $refund->formattedRequestedAt,
                'processedAt' => $refund->formattedProcessedAt,
                'processedBy' => $refund->processedByUser->name ?? null,
            ],
            'items' => $items,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Process a refund request (approve/reject/process).
     */
    public function process(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,process',
            'approvedAmount' => 'required_if:action,process|numeric|min:0',
            'adminNotes' => 'nullable|string|max:1000',
            'rejectionReason' => 'required_if:action,reject|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refund = EcomRefundRequest::with('order')->active()->find($id);

            if (!$refund) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund request not found'
                ], 404);
            }

            $action = $request->action;
            $oldStatus = $refund->status;

            switch ($action) {
                case 'approve':
                    if ($refund->status !== 'pending') {
                        throw new \Exception('Only pending refunds can be approved');
                    }
                    $refund->status = 'approved';
                    // Don't set approvedAmount yet - that's done during 'process'
                    if ($request->filled('adminNotes')) {
                        $refund->adminNotes = $request->adminNotes;
                    }
                    break;

                case 'reject':
                    if (!in_array($refund->status, ['pending', 'approved'])) {
                        throw new \Exception('Only pending or approved refunds can be rejected');
                    }
                    $refund->status = 'rejected';
                    $refund->rejectionReason = $request->rejectionReason;
                    $refund->adminNotes = $request->adminNotes;
                    $refund->processedBy = Auth::id();
                    $refund->processedAt = now();
                    break;

                case 'process':
                    if (!in_array($refund->status, ['pending', 'approved'])) {
                        throw new \Exception('Only pending or approved refunds can be processed');
                    }
                    $refund->status = 'processed';
                    $refund->approvedAmount = $request->approvedAmount;
                    $refund->adminNotes = $request->adminNotes;
                    $refund->processedBy = Auth::id();
                    $refund->processedAt = now();

                    // Determine if this is a full refund
                    $orderSubtotal = $refund->orderSubtotal;
                    $refund->refundType = ($request->approvedAmount >= $orderSubtotal) ? 'full' : 'partial';

                    // Update order status to refunded
                    $order = $refund->order;
                    $previousOrderStatus = $order->orderStatus;
                    $order->orderStatus = 'refunded';
                    $order->save();

                    // Log audit trail
                    EcomOrderAuditLog::logAction(
                        $order,
                        'refund_processed',
                        'orderStatus',
                        $previousOrderStatus,
                        'refunded',
                        "Order refunded via refund request {$refund->refundNumber}. Amount: ₱" . number_format($request->approvedAmount, 2)
                    );
                    break;
            }

            $refund->save();

            DB::commit();

            // Log refund audit trail based on action
            switch ($action) {
                case 'approve':
                    EcomRefundAuditLog::logApproval($refund, $request->adminNotes);
                    break;
                case 'reject':
                    EcomRefundAuditLog::logRejection($refund, $request->rejectionReason, $oldStatus);
                    break;
                case 'process':
                    EcomRefundAuditLog::logProcessing($refund, $request->approvedAmount, $request->adminNotes, $oldStatus);
                    break;
            }

            $actionLabels = [
                'approve' => 'approved',
                'reject' => 'rejected',
                'process' => 'processed',
            ];

            Log::info('Refund request ' . $actionLabels[$action], [
                'refund_number' => $refund->refundNumber,
                'action' => $action,
                'amount' => $refund->approvedAmount,
                'processed_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund request ' . $actionLabels[$action] . ' successfully',
                'refund' => [
                    'id' => $refund->id,
                    'status' => $refund->status,
                    'statusLabel' => $refund->statusLabel,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a refund request (soft delete).
     */
    public function destroy($id)
    {
        try {
            $refund = EcomRefundRequest::active()->find($id);

            if (!$refund) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund request not found'
                ], 404);
            }

            // Only allow deleting pending refunds
            if ($refund->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending refund requests can be deleted'
                ], 400);
            }

            // Log audit trail before soft delete (so we still have the data)
            EcomRefundAuditLog::logDeletion($refund);

            // Soft delete the refund and its items
            $refund->deleteStatus = 0;
            $refund->save();

            EcomRefundItem::where('refundRequestId', $id)->update(['deleteStatus' => 0]);

            Log::info('Refund request deleted', [
                'refund_number' => $refund->refundNumber,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund request deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting refund request'
            ], 500);
        }
    }

    /**
     * Get products for filtering.
     */
    public function getProducts()
    {
        $products = EcomProduct::active()
            ->select('id', 'productName', 'productStore')
            ->orderBy('productName')
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    /**
     * Get refund summary statistics.
     */
    public function getSummary(Request $request)
    {
        $query = EcomRefundRequest::active();

        // Apply date filters
        if ($request->filled('dateFrom')) {
            $query->whereDate('requestedAt', '>=', $request->dateFrom);
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('requestedAt', '<=', $request->dateTo);
        }
        if ($request->filled('storeName')) {
            $query->where('storeName', $request->storeName);
        }

        $summary = [
            'totalRequests' => (clone $query)->count(),
            'pendingRequests' => (clone $query)->where('status', 'pending')->count(),
            'approvedRequests' => (clone $query)->where('status', 'approved')->count(),
            'processedRequests' => (clone $query)->where('status', 'processed')->count(),
            'rejectedRequests' => (clone $query)->where('status', 'rejected')->count(),
            'totalRefundedAmount' => (clone $query)->where('status', 'processed')->sum('approvedAmount'),
            'totalPendingAmount' => (clone $query)->whereIn('status', ['pending', 'approved'])->sum('requestedAmount'),
        ];

        $summary['formattedTotalRefunded'] = '₱' . number_format($summary['totalRefundedAmount'], 2);
        $summary['formattedTotalPending'] = '₱' . number_format($summary['totalPendingAmount'], 2);

        return response()->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    /**
     * Get audit trail for a specific refund request.
     */
    public function getAuditTrail($id)
    {
        $refund = EcomRefundRequest::find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund request not found'
            ], 404);
        }

        $auditLogs = EcomRefundAuditLog::active()
            ->forRefund($id)
            ->orderBy('actionAt', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actionLabel' => $log->actionLabel,
                    'actionBadgeClass' => $log->actionBadgeClass,
                    'actionIcon' => $log->actionIcon,
                    'actionBy' => $log->actionByName ?? 'System',
                    'actionByEmail' => $log->actionByEmail,
                    'fieldChanged' => $log->fieldChanged,
                    'previousValue' => $log->previousValue,
                    'newValue' => $log->newValue,
                    'notes' => $log->notes,
                    'metadata' => $log->metadata,
                    'ipAddress' => $log->ipAddress,
                    'actionAt' => $log->formattedActionAt,
                    'relativeTime' => $log->relativeTime,
                ];
            });

        return response()->json([
            'success' => true,
            'refund' => [
                'id' => $refund->id,
                'refundNumber' => $refund->refundNumber,
                'orderNumber' => $refund->order->orderNumber ?? 'N/A',
                'status' => $refund->status,
                'statusLabel' => $refund->statusLabel,
            ],
            'auditLogs' => $auditLogs,
            'totalLogs' => $auditLogs->count(),
        ]);
    }

    /**
     * Get all audit logs with filters (for global audit view).
     */
    public function getAllAuditLogs(Request $request)
    {
        $query = EcomRefundAuditLog::active()
            ->orderBy('actionAt', 'desc');

        // Apply filters
        if ($request->filled('refundNumber')) {
            $query->where('refundNumber', 'like', '%' . $request->refundNumber . '%');
        }

        if ($request->filled('orderNumber')) {
            $query->where('orderNumber', 'like', '%' . $request->orderNumber . '%');
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('actionBy')) {
            $query->where('actionByName', 'like', '%' . $request->actionBy . '%');
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('actionAt', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('actionAt', '<=', $request->dateTo);
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $total = $query->count();
        $logs = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'refundRequestId' => $log->refundRequestId,
                'refundNumber' => $log->refundNumber,
                'orderNumber' => $log->orderNumber,
                'action' => $log->action,
                'actionLabel' => $log->actionLabel,
                'actionBadgeClass' => $log->actionBadgeClass,
                'actionIcon' => $log->actionIcon,
                'actionBy' => $log->actionByName ?? 'System',
                'actionByEmail' => $log->actionByEmail,
                'notes' => $log->notes,
                'ipAddress' => $log->ipAddress,
                'actionAt' => $log->formattedActionAt,
                'relativeTime' => $log->relativeTime,
            ];
        });

        // Get action types for filter dropdown
        $actionTypes = collect(EcomRefundAuditLog::$actionLabels)->map(function ($label, $action) {
            return ['value' => $action, 'label' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'actionTypes' => $actionTypes,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }
}
