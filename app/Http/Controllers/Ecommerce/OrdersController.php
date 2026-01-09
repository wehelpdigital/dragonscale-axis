<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomOrderItem;
use App\Models\EcomOrderDiscount;
use App\Models\EcomOrderAffiliateCommission;
use App\Models\EcomOrderAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            ->rawColumns(['orderStatus', 'shippingStatusBadge', 'formatted_discount', 'formatted_grand_total', 'action'])
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
            $order = EcomOrder::with(['user', 'items', 'discounts', 'affiliateCommissions'])
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
}
