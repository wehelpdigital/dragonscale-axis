<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomOrderItem;
use App\Models\EcomOrderDiscount;
use App\Models\EcomOrderAffiliateCommission;
use App\Models\EcomOrderAuditLog;
use App\Models\User;
use App\Models\EcomProductStore;
use App\Models\EcomOrderPayment;
use App\Models\EcomStoreInvoiceSetting;
use App\Services\TriggerFlowProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class OrdersController extends Controller
{
    /**
     * Display the orders page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.orders.index');
    }

    /**
     * Get orders data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $query = EcomOrder::with('user')
            ->active()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('orderNumber')) {
            $query->where('orderNumber', 'like', '%' . $request->orderNumber . '%');
        }

        if ($request->filled('customerName')) {
            $search = $request->customerName;
            $query->where(function($q) use ($search) {
                $q->where('clientFirstName', 'like', '%' . $search . '%')
                  ->orWhere('clientMiddleName', 'like', '%' . $search . '%')
                  ->orWhere('clientLastName', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('orderStatus')) {
            $query->where('orderStatus', $request->orderStatus);
        }

        if ($request->filled('shippingStatus')) {
            $query->where('shippingStatus', $request->shippingStatus);
        }

        if ($request->filled('paymentStatus')) {
            $query->where('paymentVerificationStatus', $request->paymentStatus);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('orderStatus', function ($order) {
                $status = $order->orderStatus ?? 'pending';
                $badgeClass = match($status) {
                    'paid' => 'bg-info',
                    'complete' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    'refunded' => 'bg-secondary',
                    default => 'bg-warning',
                };
                $textClass = ($status === 'pending') ? ' text-dark' : '';
                return '<span class="badge ' . $badgeClass . $textClass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('orderStatusRaw', function ($order) {
                return $order->orderStatus ?? 'pending';
            })
            ->addColumn('shippingStatusBadge', function ($order) {
                $status = $order->shippingStatus ?? 'pending';
                $badgeClass = match($status) {
                    'shipped' => 'bg-success',
                    'not_applicable' => 'bg-secondary',
                    default => 'bg-warning',
                };
                $textClass = ($status === 'pending') ? ' text-dark' : '';
                $displayText = match($status) {
                    'not_applicable' => 'Not Applicable',
                    default => ucfirst($status),
                };
                return '<span class="badge ' . $badgeClass . $textClass . '">' . $displayText . '</span>';
            })
            ->addColumn('shippingStatusRaw', function ($order) {
                return $order->shippingStatus ?? 'pending';
            })
            ->addColumn('paymentStatusBadge', function ($order) {
                $status = $order->paymentVerificationStatus ?? 'not_required';
                $badgeClass = match($status) {
                    'verified' => 'bg-success',
                    'rejected' => 'bg-danger',
                    'pending' => 'bg-warning',
                    default => 'bg-secondary',
                };
                $textClass = ($status === 'pending') ? ' text-dark' : '';
                $displayText = match($status) {
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                    'pending' => 'Pending',
                    default => 'N/A',
                };
                return '<span class="badge ' . $badgeClass . $textClass . '">' . $displayText . '</span>';
            })
            ->addColumn('paymentStatusRaw', function ($order) {
                return $order->paymentVerificationStatus ?? 'not_required';
            })
            ->addColumn('pendingPaymentsCount', function ($order) {
                return EcomOrderPayment::active()
                    ->where('orderId', $order->id)
                    ->where('paymentStatus', 'pending')
                    ->count();
            })
            ->addColumn('customerFullName', function ($order) {
                $parts = array_filter([
                    $order->clientFirstName,
                    $order->clientMiddleName,
                    $order->clientLastName
                ]);
                return !empty($parts) ? implode(' ', $parts) : 'N/A';
            })
            ->addColumn('formatted_subtotal', function ($order) {
                return '₱' . number_format($order->subtotal ?? 0, 2);
            })
            ->addColumn('formatted_discount', function ($order) {
                $discount = $order->discountTotal ?? 0;
                if ($discount > 0) {
                    return '<span class="text-danger">-₱' . number_format($discount, 2) . '</span>';
                }
                return '₱0.00';
            })
            ->addColumn('formatted_shipping', function ($order) {
                return '₱' . number_format($order->shippingTotal ?? 0, 2);
            })
            ->addColumn('formatted_grand_total', function ($order) {
                return '<strong>₱' . number_format($order->grandTotal ?? 0, 2) . '</strong>';
            })
            ->addColumn('handledBy', function ($order) {
                if ($order->user) {
                    return $order->user->name ?? $order->user->email ?? 'Admin';
                }
                return 'System';
            })
            ->addColumn('formatted_date', function ($order) {
                return $order->created_at ? $order->created_at->format('F j, Y g:ia') : '';
            })
            ->addColumn('action', function ($order) {
                return ''; // Actions are rendered client-side
            })
            ->rawColumns(['orderStatus', 'shippingStatusBadge', 'paymentStatusBadge', 'formatted_discount', 'formatted_grand_total', 'action'])
            ->make(true);
    }

    /**
     * Get order details for view modal.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderDetails($id)
    {
        try {
            $order = EcomOrder::with(['user', 'items', 'discounts', 'affiliateCommissions', 'verifiedByUser'])
                ->where('id', $id)
                ->active()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Format the order data
            $orderData = $order->toArray();
            $orderData['created_at'] = $order->created_at ? $order->created_at->format('M j, Y g:i A') : '';

            // Add payment verification formatted data
            $orderData['paymentMethodLabel'] = $order->paymentMethodLabel;
            $orderData['paymentVerificationStatusLabel'] = $order->paymentVerificationStatusLabel;
            $orderData['paymentVerifiedAtFormatted'] = $order->paymentVerifiedAt ? $order->paymentVerifiedAt->format('M j, Y g:i A') : null;
            $orderData['verifiedByUserName'] = $order->verifiedByUser ? $order->verifiedByUser->name : null;
            $orderData['requiresPaymentVerification'] = $order->requiresPaymentVerification();
            $orderData['isPaymentVerified'] = $order->isPaymentVerified();

            return response()->json([
                'success' => true,
                'order' => $orderData
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order details'
            ], 500);
        }
    }

    /**
     * Update order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $order = EcomOrder::where('id', $id)
                ->active()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validStatuses = ['pending', 'paid', 'complete', 'cancelled', 'refunded'];
            $finalStatuses = ['complete', 'cancelled', 'refunded'];
            $newStatus = $request->input('status');
            $oldStatus = $order->orderStatus;

            if (!in_array($newStatus, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
            }

            // Check if order is already in a final status - prevent most changes
            // Exception: "complete" orders can be changed to "refunded"
            if (in_array($oldStatus, $finalStatuses)) {
                // Allow complete -> refunded
                if ($oldStatus === 'complete' && $newStatus === 'refunded') {
                    // Allowed - continue processing
                } else {
                    $statusLabel = ucfirst($oldStatus);
                    $allowedChange = $oldStatus === 'complete' ? " (can only be changed to 'Refunded')" : "";
                    return response()->json([
                        'success' => false,
                        'message' => "This order is already marked as '{$statusLabel}' and cannot be changed{$allowedChange}.",
                        'isFinal' => true,
                        'allowRefund' => $oldStatus === 'complete'
                    ], 400);
                }
            }

            // Check if changing to a final status - require confirmation token
            if (in_array($newStatus, $finalStatuses)) {
                $confirmationToken = $request->input('confirmationToken');
                if (!$confirmationToken || $confirmationToken !== 'CONFIRM') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Confirmation required for final status change',
                        'requiresConfirmation' => true
                    ], 400);
                }
            }

            $order->orderStatus = $newStatus;
            $order->save();

            // Log audit trail
            EcomOrderAuditLog::logAction(
                $order,
                'status_change',
                'orderStatus',
                $oldStatus,
                $newStatus,
                "Order status changed from '{$oldStatus}' to '{$newStatus}'"
            );

            Log::info('Order status updated', [
                'order_id' => $id,
                'order_number' => $order->orderNumber,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => Auth::id()
            ]);

            // Trigger flows for order status change
            try {
                // Get storeId from order items if available
                $storeId = null;
                $firstItem = $order->items()->first();
                if ($firstItem && $firstItem->productStore) {
                    $store = EcomProductStore::where('storeName', $firstItem->productStore)
                        ->where('deleteStatus', 1)
                        ->first();
                    if ($store) {
                        $storeId = $store->id;
                    }
                }

                $processor = new TriggerFlowProcessorService();
                $enrollments = $processor->triggerFlowsForEvent('order_status_changed', [
                    'clientId' => $order->clientId,
                    'orderId' => $order->id,
                    'storeId' => $storeId,
                    'oldStatus' => $oldStatus,
                    'newStatus' => $newStatus,
                ], Auth::id());

                if (count($enrollments) > 0) {
                    Log::info('Trigger flows enrolled for order status change', [
                        'order_id' => $order->id,
                        'enrollments' => count($enrollments),
                    ]);
                }
            } catch (\Exception $e) {
                // Don't fail the order update if trigger fails
                Log::error('Failed to trigger flows for order status change: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => [
                    'id' => $order->id,
                    'orderNumber' => $order->orderNumber,
                    'orderStatus' => $order->orderStatus
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status'
            ], 500);
        }
    }

    /**
     * Update shipping status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateShipping(Request $request, $id)
    {
        try {
            $order = EcomOrder::where('id', $id)
                ->active()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validStatuses = ['pending', 'shipped'];
            $finalOrderStatuses = ['complete', 'cancelled', 'refunded'];
            $shippingStatus = $request->input('shippingStatus');

            if (!in_array($shippingStatus, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid shipping status'
                ], 400);
            }

            // Check if order is in a final status - prevent shipping changes
            if (in_array($order->orderStatus, $finalOrderStatuses)) {
                $statusLabel = ucfirst($order->orderStatus);
                return response()->json([
                    'success' => false,
                    'message' => "Cannot update shipping for an order that is '{$statusLabel}'.",
                    'isFinal' => true
                ], 400);
            }

            // Store old status for audit
            $oldShippingStatus = $order->shippingStatus;

            // Update shipping status
            $order->shippingStatus = $shippingStatus;
            $order->save();

            // Log audit trail
            EcomOrderAuditLog::logAction(
                $order,
                'shipping_change',
                'shippingStatus',
                $oldShippingStatus,
                $shippingStatus,
                "Shipping status changed from '{$oldShippingStatus}' to '{$shippingStatus}'"
            );

            Log::info('Order shipping updated', [
                'order_id' => $id,
                'order_number' => $order->orderNumber,
                'shipping_status' => $shippingStatus,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping status updated successfully',
                'order' => [
                    'id' => $order->id,
                    'orderNumber' => $order->orderNumber,
                    'shippingStatus' => $order->shippingStatus
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating shipping status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating shipping status'
            ], 500);
        }
    }

    /**
     * Cancel an order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder($id)
    {
        try {
            $order = EcomOrder::where('id', $id)
                ->active()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if order can be cancelled
            $nonCancellableStatuses = ['delivered', 'completed', 'cancelled', 'refunded'];
            if (in_array($order->orderStatus, $nonCancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order cannot be cancelled because it is already ' . $order->orderStatus
                ], 400);
            }

            $oldStatus = $order->orderStatus;
            $order->orderStatus = 'cancelled';
            $order->save();

            // Log audit trail
            EcomOrderAuditLog::logAction(
                $order,
                'order_cancelled',
                'orderStatus',
                $oldStatus,
                'cancelled',
                "Order cancelled (previous status: '{$oldStatus}')"
            );

            Log::info('Order cancelled', [
                'order_id' => $id,
                'order_number' => $order->orderNumber,
                'old_status' => $oldStatus,
                'cancelled_by' => Auth::id()
            ]);

            // Trigger flows for order cancellation (status change)
            try {
                $storeId = null;
                $firstItem = $order->items()->first();
                if ($firstItem && $firstItem->productStore) {
                    $store = EcomProductStore::where('storeName', $firstItem->productStore)
                        ->where('deleteStatus', 1)
                        ->first();
                    if ($store) {
                        $storeId = $store->id;
                    }
                }

                $processor = new TriggerFlowProcessorService();
                $processor->triggerFlowsForEvent('order_status_changed', [
                    'clientId' => $order->clientId,
                    'orderId' => $order->id,
                    'storeId' => $storeId,
                    'oldStatus' => $oldStatus,
                    'newStatus' => 'cancelled',
                ], Auth::id());
            } catch (\Exception $e) {
                Log::error('Failed to trigger flows for order cancellation: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order' => [
                    'id' => $order->id,
                    'orderNumber' => $order->orderNumber,
                    'orderStatus' => $order->orderStatus
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling order'
            ], 500);
        }
    }

    /**
     * Save payment verification details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePaymentVerification(Request $request, $id)
    {
        try {
            $order = EcomOrder::where('id', $id)
                ->active()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'paymentMethod' => 'required|in:manual_gcash,manual_maya,manual_instapay,manual_bank,manual_other,online_payment,cod,cop',
                'paymentPayerName' => 'nullable|string|max:255',
                'paymentAmountSent' => 'nullable|numeric|min:0',
                'paymentReferenceNumber' => 'nullable|string|max:100',
                'paymentPhoneNumber' => 'nullable|string|max:20',
                'paymentBankName' => 'nullable|string|max:100',
                'paymentBankAccountName' => 'nullable|string|max:255',
                'paymentBankAccountNumber' => 'nullable|string|max:50',
                'paymentScreenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'paymentNotes' => 'nullable|string|max:1000',
            ], [
                'paymentMethod.required' => 'Please select a payment method.',
                'paymentMethod.in' => 'Invalid payment method selected.',
                'paymentScreenshot.image' => 'The file must be an image.',
                'paymentScreenshot.mimes' => 'Only JPEG, PNG, JPG, GIF, and WEBP images are allowed.',
                'paymentScreenshot.max' => 'The screenshot must not exceed 5MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Store old values for audit
            $oldPaymentMethod = $order->paymentMethod;
            $oldVerificationStatus = $order->paymentVerificationStatus;

            // Handle screenshot upload
            $screenshotPath = $order->paymentScreenshot;
            if ($request->hasFile('paymentScreenshot')) {
                // Delete old screenshot if exists
                if ($order->paymentScreenshot && file_exists(public_path($order->paymentScreenshot))) {
                    unlink(public_path($order->paymentScreenshot));
                }

                $file = $request->file('paymentScreenshot');
                $filename = 'payment_' . $order->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/payment-screenshots'), $filename);
                $screenshotPath = 'images/payment-screenshots/' . $filename;
            }

            // Determine verification status based on payment method
            $manualMethods = ['manual_gcash', 'manual_maya', 'manual_instapay', 'manual_bank', 'manual_other'];
            $verificationStatus = in_array($request->paymentMethod, $manualMethods) ? 'pending' : 'not_required';

            // If already verified, don't change status
            if ($order->paymentVerificationStatus === 'verified') {
                $verificationStatus = 'verified';
            }

            // Update order
            $order->paymentMethod = $request->paymentMethod;
            $order->paymentVerificationStatus = $verificationStatus;
            $order->paymentPayerName = $request->paymentPayerName;
            $order->paymentAmountSent = $request->paymentAmountSent;
            $order->paymentReferenceNumber = $request->paymentReferenceNumber;

            // Phone number (for GCash/Maya)
            $order->paymentPhoneNumber = $request->paymentPhoneNumber;

            // Bank details (for Instapay)
            $order->paymentBankName = $request->paymentBankName;
            $order->paymentBankAccountName = $request->paymentBankAccountName;
            $order->paymentBankAccountNumber = $request->paymentBankAccountNumber;

            $order->paymentScreenshot = $screenshotPath;
            $order->paymentNotes = $request->paymentNotes;
            $order->save();

            // Log audit trail
            $description = $oldPaymentMethod
                ? "Payment details updated (Method: {$request->paymentMethod})"
                : "Payment details added (Method: {$request->paymentMethod})";

            EcomOrderAuditLog::logAction(
                $order,
                'payment_details_updated',
                'paymentMethod',
                $oldPaymentMethod,
                $request->paymentMethod,
                $description
            );

            Log::info('Payment verification details saved', [
                'order_id' => $id,
                'order_number' => $order->orderNumber,
                'payment_method' => $request->paymentMethod,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment details saved successfully',
                'order' => [
                    'id' => $order->id,
                    'orderNumber' => $order->orderNumber,
                    'paymentMethod' => $order->paymentMethod,
                    'paymentVerificationStatus' => $order->paymentVerificationStatus
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving payment verification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving payment details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify or reject payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request, $id)
    {
        try {
            $order = EcomOrder::where('id', $id)
                ->active()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:verify,reject',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $action = $request->action;
            $oldStatus = $order->paymentVerificationStatus;
            $newStatus = $action === 'verify' ? 'verified' : 'rejected';

            $order->paymentVerificationStatus = $newStatus;
            $order->paymentVerifiedAt = now();
            $order->paymentVerifiedBy = Auth::id();

            if ($request->filled('notes')) {
                $existingNotes = $order->paymentNotes;
                $newNote = '[' . now()->format('Y-m-d H:i') . '] ' . ($action === 'verify' ? 'Verified' : 'Rejected') . ': ' . $request->notes;
                $order->paymentNotes = $existingNotes ? $existingNotes . "\n" . $newNote : $newNote;
            }

            $order->save();

            // Log audit trail
            $actionLabel = $action === 'verify' ? 'verified' : 'rejected';
            EcomOrderAuditLog::logAction(
                $order,
                'payment_' . $actionLabel,
                'paymentVerificationStatus',
                $oldStatus,
                $newStatus,
                "Payment {$actionLabel} by " . Auth::user()->name
            );

            Log::info('Payment verification status updated', [
                'order_id' => $id,
                'order_number' => $order->orderNumber,
                'action' => $action,
                'verified_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment ' . $actionLabel . ' successfully',
                'order' => [
                    'id' => $order->id,
                    'orderNumber' => $order->orderNumber,
                    'paymentVerificationStatus' => $order->paymentVerificationStatus,
                    'paymentVerifiedAt' => $order->paymentVerifiedAt ? $order->paymentVerifiedAt->format('M j, Y g:i A') : null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error verifying payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit logs for an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuditLogs(Request $request, $id)
    {
        try {
            $order = EcomOrder::where('id', $id)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $query = EcomOrderAuditLog::active()
                ->forOrder($id)
                ->orderBy('created_at', 'desc');

            // Apply date filters
            if ($request->filled('dateFrom')) {
                $query->whereDate('created_at', '>=', $request->dateFrom);
            }

            if ($request->filled('dateTo')) {
                $query->whereDate('created_at', '<=', $request->dateTo);
            }

            $logs = $query->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'actionType' => $log->actionType,
                    'actionTypeLabel' => $log->actionTypeLabel,
                    'fieldChanged' => $log->fieldChanged,
                    'previousValue' => $log->previousValue,
                    'newValue' => $log->newValue,
                    'formattedPreviousValue' => $log->formattedPreviousValue,
                    'formattedNewValue' => $log->formattedNewValue,
                    'description' => $log->description,
                    'userName' => $log->userName,
                    'ipAddress' => $log->ipAddress,
                    'createdAt' => $log->created_at ? $log->created_at->format('F j, Y g:i:s A') : '',
                    'createdAtShort' => $log->created_at ? $log->created_at->format('M j, Y g:i A') : '',
                ];
            });

            return response()->json([
                'success' => true,
                'orderNumber' => $order->orderNumber,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching audit logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching audit logs'
            ], 500);
        }
    }

    /**
     * Get payments for an order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayments($id)
    {
        try {
            $order = EcomOrder::where('id', $id)->active()->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if we need to migrate payment from old system
            $existingPayments = EcomOrderPayment::active()->forOrder($id)->count();

            if ($existingPayments === 0 && $order->paymentMethod) {
                // Auto-create payment record from order's payment info
                $payment = EcomOrderPayment::create([
                    'orderId' => $order->id,
                    'paymentNumber' => EcomOrderPayment::generatePaymentNumber($order->id),
                    'paymentMethod' => $order->paymentMethod,
                    'paymentStatus' => $order->paymentVerificationStatus === 'verified' ? 'verified' : 'pending',
                    'amountSent' => $order->paymentAmountSent ?? $order->grandTotal,
                    'amountVerified' => $order->paymentAmountVerified,
                    'payerName' => $order->paymentPayerName,
                    'referenceNumber' => $order->paymentReferenceNumber,
                    'phoneNumber' => $order->paymentPhoneNumber,
                    'bankName' => $order->paymentBankName,
                    'bankAccountName' => $order->paymentBankAccountName,
                    'bankAccountNumber' => $order->paymentBankAccountNumber,
                    'screenshot' => $order->paymentScreenshot,
                    'verifiedAt' => $order->paymentVerificationStatus === 'verified' ? $order->updated_at : null,
                    'deleteStatus' => 1,
                ]);

                Log::info('Auto-migrated payment from order', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'payment_method' => $order->paymentMethod
                ]);
            }

            $payments = EcomOrderPayment::active()
                ->forOrder($id)
                ->with('verifier')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'paymentNumber' => $payment->paymentNumber,
                        'paymentMethod' => $payment->paymentMethod,
                        'paymentMethodLabel' => $payment->paymentMethodLabel,
                        'paymentStatus' => $payment->paymentStatus,
                        'paymentStatusLabel' => $payment->paymentStatusLabel,
                        'paymentStatusBadgeClass' => $payment->paymentStatusBadgeClass,
                        'amountSent' => $payment->amountSent,
                        'amountVerified' => $payment->amountVerified,
                        'formattedAmountSent' => $payment->formattedAmountSent,
                        'formattedAmountVerified' => $payment->formattedAmountVerified,
                        'effectiveAmount' => $payment->effectiveAmount,
                        'payerName' => $payment->payerName,
                        'referenceNumber' => $payment->referenceNumber,
                        'phoneNumber' => $payment->phoneNumber,
                        'bankName' => $payment->bankName,
                        'bankAccountName' => $payment->bankAccountName,
                        'bankAccountNumber' => $payment->bankAccountNumber,
                        'screenshot' => $payment->screenshot,
                        'screenshotUrl' => $payment->screenshot ? asset($payment->screenshot) : null,
                        'verifiedAt' => $payment->verifiedAtFormatted,
                        'verifierName' => $payment->verifierName,
                        'verificationNotes' => $payment->verificationNotes,
                        'invoiceNumber' => $payment->invoiceNumber,
                        'invoiceToken' => $payment->invoiceToken,
                        'invoicePath' => $payment->invoicePath,
                        'createdAt' => $payment->created_at ? $payment->created_at->format('M j, Y g:i A') : '',
                    ];
                });

            // Calculate summary
            $totalVerified = $order->totalVerifiedPayments;
            $remainingBalance = $order->remainingBalance;
            $isFullyPaid = $order->isFullyPaid;

            return response()->json([
                'success' => true,
                'orderNumber' => $order->orderNumber,
                'grandTotal' => $order->grandTotal,
                'formattedGrandTotal' => '₱' . number_format($order->grandTotal, 2),
                'totalVerified' => $totalVerified,
                'formattedTotalVerified' => '₱' . number_format($totalVerified, 2),
                'remainingBalance' => $remainingBalance,
                'formattedRemainingBalance' => '₱' . number_format($remainingBalance, 2),
                'isFullyPaid' => $isFullyPaid,
                'payments' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payments'
            ], 500);
        }
    }

    /**
     * Add a new payment to an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPayment(Request $request, $id)
    {
        try {
            $order = EcomOrder::where('id', $id)->active()->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'paymentMethod' => 'required|in:manual_gcash,manual_maya,manual_instapay,manual_bank,manual_other,online_payment,cod,cop',
                'amountSent' => 'required|numeric|min:0.01',
                'payerName' => 'nullable|string|max:255',
                'referenceNumber' => 'nullable|string|max:100',
                'phoneNumber' => 'nullable|string|max:20',
                'bankName' => 'nullable|string|max:100',
                'bankAccountName' => 'nullable|string|max:255',
                'bankAccountNumber' => 'nullable|string|max:50',
                'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ], [
                'paymentMethod.required' => 'Please select a payment method.',
                'amountSent.required' => 'Please enter the payment amount.',
                'amountSent.min' => 'Payment amount must be greater than zero.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle screenshot upload
            $screenshotPath = null;
            if ($request->hasFile('screenshot')) {
                $file = $request->file('screenshot');
                $filename = 'payment_' . $order->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/payment-screenshots'), $filename);
                $screenshotPath = 'images/payment-screenshots/' . $filename;
            }

            // Determine initial status
            $manualMethods = ['manual_gcash', 'manual_maya', 'manual_instapay', 'manual_bank', 'manual_other'];
            $paymentStatus = in_array($request->paymentMethod, $manualMethods) ? 'pending' : 'verified';

            // Create payment
            $payment = EcomOrderPayment::create([
                'orderId' => $order->id,
                'paymentMethod' => $request->paymentMethod,
                'paymentStatus' => $paymentStatus,
                'amountSent' => $request->amountSent,
                'amountVerified' => $paymentStatus === 'verified' ? $request->amountSent : null,
                'payerName' => $request->payerName,
                'referenceNumber' => $request->referenceNumber,
                'phoneNumber' => $request->phoneNumber,
                'bankName' => $request->bankName,
                'bankAccountName' => $request->bankAccountName,
                'bankAccountNumber' => $request->bankAccountNumber,
                'screenshot' => $screenshotPath,
                'verifiedAt' => $paymentStatus === 'verified' ? now() : null,
                'verifiedBy' => $paymentStatus === 'verified' ? Auth::id() : null,
                'deleteStatus' => 1,
            ]);

            // Log audit trail
            EcomOrderAuditLog::logAction(
                $order,
                'payment_added',
                'payments',
                null,
                $payment->paymentNumber,
                "Payment added: {$payment->paymentNumber} ({$payment->formattedAmountSent})"
            );

            Log::info('Payment added to order', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'payment_number' => $payment->paymentNumber,
                'amount' => $payment->amountSent,
                'added_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully',
                'payment' => [
                    'id' => $payment->id,
                    'paymentNumber' => $payment->paymentNumber,
                    'paymentStatus' => $payment->paymentStatus,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify or reject a payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOrderPayment(Request $request, $paymentId)
    {
        try {
            $payment = EcomOrderPayment::active()->find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            $order = $payment->order;
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Associated order not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:verify,reject',
                'amountVerified' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $action = $request->action;
            $oldStatus = $payment->paymentStatus;
            $newStatus = $action === 'verify' ? 'verified' : 'rejected';

            $payment->paymentStatus = $newStatus;
            $payment->verifiedAt = now();
            $payment->verifiedBy = Auth::id();

            if ($action === 'verify') {
                $payment->amountVerified = $request->amountVerified ?? $payment->amountSent;

                // Generate invoice number and token if verified
                if (empty($payment->invoiceNumber)) {
                    $payment->invoiceNumber = EcomOrderPayment::generateInvoiceNumber($order->id);
                    $payment->invoiceToken = EcomOrderPayment::generateInvoiceToken();
                    $payment->invoiceGeneratedAt = now();
                }
            }

            if ($request->filled('notes')) {
                $existingNotes = $payment->verificationNotes;
                $timestamp = now()->format('Y-m-d H:i');
                $actionLabel = $action === 'verify' ? 'Verified' : 'Rejected';
                $newNote = "[{$timestamp}] {$actionLabel}: {$request->notes}";
                $payment->verificationNotes = $existingNotes ? $existingNotes . "\n" . $newNote : $newNote;
            }

            $payment->save();

            // Update order's payment verification status based on payment totals
            $order->refresh(); // Refresh to get updated payment totals
            $totalVerified = $order->totalVerifiedPayments;
            $grandTotal = $order->grandTotal;

            if ($action === 'verify') {
                // Check if fully paid
                if ($totalVerified >= $grandTotal) {
                    $order->paymentVerificationStatus = 'verified';
                } else {
                    // Partially paid - keep as pending
                    $order->paymentVerificationStatus = 'pending';
                }
            } else {
                // Payment rejected - check if there are other verified payments
                if ($totalVerified >= $grandTotal) {
                    $order->paymentVerificationStatus = 'verified';
                } elseif ($totalVerified > 0) {
                    $order->paymentVerificationStatus = 'pending';
                } else {
                    // No verified payments and this one rejected
                    $order->paymentVerificationStatus = 'rejected';
                }
            }
            $order->save();

            // Log audit trail
            $actionLabel = $action === 'verify' ? 'verified' : 'rejected';
            EcomOrderAuditLog::logAction(
                $order,
                'payment_' . $actionLabel,
                'paymentStatus',
                $oldStatus,
                $newStatus,
                "Payment {$payment->paymentNumber} {$actionLabel} by " . Auth::user()->name
            );

            // Trigger flows for payment status change
            try {
                $processor = new TriggerFlowProcessorService();
                $eventType = $action === 'verify' ? 'payment_verified' : 'payment_rejected';

                // Get all order items to trigger flows for each product/variant
                $orderItems = $order->items()->get();

                foreach ($orderItems as $item) {
                    // Get store ID from the item's store name
                    $storeId = null;
                    if ($item->productStore) {
                        $store = EcomProductStore::where('storeName', $item->productStore)
                            ->where('deleteStatus', 1)
                            ->first();
                        if ($store) {
                            $storeId = $store->id;
                        }
                    }

                    // Build the invoice URL
                    $invoiceUrl = '';
                    if ($payment->invoiceToken) {
                        $invoiceUrl = route('invoice.view', ['token' => $payment->invoiceToken]);
                    }

                    // Trigger flow for this specific product/variant
                    $processor->triggerFlowsForEvent($eventType, [
                        'clientId' => $order->clientId,
                        'orderId' => $order->id,
                        'storeId' => $storeId,
                        'productId' => $item->productId,
                        'variantId' => $item->variantId,
                        'product_name' => $item->productName,
                        'variant_name' => $item->variantName,
                        'paymentId' => $payment->id,
                        'paymentNumber' => $payment->paymentNumber,
                        'amountVerified' => $payment->amountVerified,
                        'invoice_url' => $invoiceUrl,
                        'invoice_number' => $payment->invoiceNumber,
                        'payment_amount' => number_format($payment->amountVerified ?? $payment->amountSent, 2),
                        'payment_method' => $payment->paymentMethodLabel ?? $payment->paymentMethod,
                        'payment_date' => $payment->verifiedAt ? $payment->verifiedAt->format('M j, Y') : now()->format('M j, Y'),
                    ], Auth::id());
                }
            } catch (\Exception $e) {
                Log::error('Failed to trigger flows for payment status change: ' . $e->getMessage());
            }

            Log::info('Payment verification updated', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'action' => $action,
                'verified_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment ' . $actionLabel . ' successfully',
                'payment' => [
                    'id' => $payment->id,
                    'paymentNumber' => $payment->paymentNumber,
                    'paymentStatus' => $payment->paymentStatus,
                    'invoiceNumber' => $payment->invoiceNumber,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error verifying payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a payment (soft delete).
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePayment($paymentId)
    {
        try {
            $payment = EcomOrderPayment::active()->find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            $order = $payment->order;

            // Don't allow deleting verified payments
            if ($payment->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a verified payment. Please contact support.'
                ], 400);
            }

            $paymentNumber = $payment->paymentNumber;
            $payment->update(['deleteStatus' => 0]);

            // Log audit trail
            if ($order) {
                EcomOrderAuditLog::logAction(
                    $order,
                    'payment_deleted',
                    'payments',
                    $paymentNumber,
                    null,
                    "Payment deleted: {$paymentNumber}"
                );
            }

            Log::info('Payment deleted', [
                'payment_id' => $paymentId,
                'payment_number' => $paymentNumber,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment'
            ], 500);
        }
    }

    /**
     * Revert a payment verification status back to pending.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revertPaymentVerification(Request $request, $paymentId)
    {
        try {
            $payment = EcomOrderPayment::active()->find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Only allow reverting verified or rejected payments
            if (!in_array($payment->paymentStatus, ['verified', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only verified or rejected payments can be reverted'
                ], 400);
            }

            $order = $payment->order;
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Associated order not found'
                ], 404);
            }

            $oldStatus = $payment->paymentStatus;

            // Revert payment to pending
            $payment->paymentStatus = 'pending';
            $payment->verifiedAt = null;
            $payment->verifiedBy = null;
            $payment->amountVerified = null;

            // Add reversion note
            $timestamp = now()->format('Y-m-d H:i');
            $reason = $request->input('reason', 'No reason provided');
            $revertNote = "[{$timestamp}] Reverted from {$oldStatus} to pending by " . Auth::user()->name . ": {$reason}";
            $payment->verificationNotes = $payment->verificationNotes
                ? $payment->verificationNotes . "\n" . $revertNote
                : $revertNote;

            $payment->save();

            // Recalculate order's payment verification status
            $order->refresh();
            $totalVerified = $order->totalVerifiedPayments;
            $grandTotal = $order->grandTotal;
            $pendingPayments = EcomOrderPayment::active()
                ->where('orderId', $order->id)
                ->where('paymentStatus', 'pending')
                ->count();

            if ($totalVerified >= $grandTotal) {
                $order->paymentVerificationStatus = 'verified';
            } elseif ($pendingPayments > 0 || $totalVerified > 0) {
                $order->paymentVerificationStatus = 'pending';
            } else {
                // Check if all payments are rejected
                $rejectedPayments = EcomOrderPayment::active()
                    ->where('orderId', $order->id)
                    ->where('paymentStatus', 'rejected')
                    ->count();

                if ($rejectedPayments > 0) {
                    $order->paymentVerificationStatus = 'rejected';
                } else {
                    $order->paymentVerificationStatus = 'pending';
                }
            }
            $order->save();

            // Log audit trail
            EcomOrderAuditLog::logAction(
                $order,
                'payment_reverted',
                'paymentStatus',
                $oldStatus,
                'pending',
                "Payment {$payment->paymentNumber} reverted from {$oldStatus} to pending by " . Auth::user()->name . ". Reason: {$reason}"
            );

            Log::info('Payment verification reverted', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'reverted_by' => Auth::id(),
                'reason' => $reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment reverted to pending successfully',
                'payment' => [
                    'id' => $payment->id,
                    'paymentNumber' => $payment->paymentNumber,
                    'paymentStatus' => $payment->paymentStatus,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error reverting payment verification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error reverting payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View public invoice by token.
     *
     * @param  string  $token
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function viewInvoice($token)
    {
        try {
            // Find payment by invoice token
            $payment = EcomOrderPayment::where('invoiceToken', $token)
                ->where('deleteStatus', 1)
                ->where('paymentStatus', 'verified')
                ->first();

            if (!$payment) {
                abort(404, 'Invoice not found');
            }

            $order = $payment->order;
            if (!$order || $order->deleteStatus === 0) {
                abort(404, 'Order not found');
            }

            // Get order items
            $items = $order->items()->active()->get();

            // Get store info from first item
            $storeId = null;
            $storeName = 'Store';
            $firstItem = $items->first();
            if ($firstItem && $firstItem->productStore) {
                $store = EcomProductStore::where('storeName', $firstItem->productStore)
                    ->where('deleteStatus', 1)
                    ->first();
                if ($store) {
                    $storeId = $store->id;
                    $storeName = $store->storeName;
                }
            }

            // Get invoice settings
            $invoiceSettings = null;
            if ($storeId) {
                $invoiceSettings = EcomStoreInvoiceSetting::where('storeId', $storeId)
                    ->where('deleteStatus', 1)
                    ->first();
            }

            // Use defaults if no settings
            if (!$invoiceSettings) {
                $invoiceSettings = new EcomStoreInvoiceSetting([
                    'businessName' => $storeName,
                    'primaryColor' => '#556ee6',
                    'secondaryColor' => '#34c38f',
                    'headerBgColor' => '#556ee6',
                    'headerTextColor' => '#ffffff',
                ]);
            }

            return view('ecommerce.orders.invoice', compact('payment', 'order', 'items', 'invoiceSettings'));

        } catch (\Exception $e) {
            Log::error('Error viewing invoice: ' . $e->getMessage());
            abort(500, 'Error loading invoice');
        }
    }
}
