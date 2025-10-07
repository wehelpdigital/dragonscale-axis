<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductDiscount;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DiscountsController extends Controller
{
    /**
     * Display the discounts page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.discounts.index');
    }

    /**
     * Show the form for creating a new discount.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('ecommerce.discounts.create');
    }

    /**
     * Store a newly created discount in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // Prepare data for creation
            $discountData = [
                'discountName' => $request->discountName,
                'discountDescription' => $request->discountDescription,
                'discountType' => $request->discountType,
                'discountTrigger' => $request->discountTrigger,
                'discountCode' => $request->discountCode,
                'amountType' => $request->amountType,
                'valuePercent' => $request->valuePercent,
                'valueAmount' => $request->valueAmount,
                'valueReplacement' => $request->valueReplacement,
                'discountCapType' => $request->discountCapType,
                'discountCapValue' => $request->discountCapValue,
                'usageLimit' => $request->usageLimit,
                'expirationType' => $request->expirationType,
                'timerCountdown' => $request->countdownMinutes,
                'isActive' => 0,
                'deleteStatus' => 1,
            ];

            // Combine date and time expiration if expirationType is "Time and Date"
            if ($request->expirationType === 'Time and Date' && $request->dateExpiration && $request->timeExpiration) {
                // Parse the date and time
                $dateExpiration = \Carbon\Carbon::parse($request->dateExpiration);
                $timeExpiration = \Carbon\Carbon::parse($request->timeExpiration);

                // Combine date and time
                $dateTimeExpiration = $dateExpiration->format('Y-m-d') . ' ' . $timeExpiration->format('H:i:s');
                $discountData['dateTimeExpiration'] = $dateTimeExpiration;
            } else {
                $discountData['dateTimeExpiration'] = null;
            }

            // Create the discount
            EcomProductDiscount::create($discountData);

            return redirect()->route('ecom-discounts')
                ->with('success', 'Discount has been added successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while saving the discount. Please try again.');
        }
    }

    /**
     * Show the form for editing a discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $discountId = $request->get('id');

        // Get the discount details
        $discount = EcomProductDiscount::active()->find($discountId);

        if (!$discount) {
            return redirect()->route('ecom-discounts')
                ->with('error', 'Discount not found.');
        }

        return view('ecommerce.discounts.edit', compact('discount'));
    }

    /**
     * Update the specified discount in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        try {
            $discountId = $request->discountId;

            // Find the discount
            $discount = EcomProductDiscount::active()->findOrFail($discountId);

            // Prepare data for update
            $discountData = [
                'discountName' => $request->discountName,
                'discountDescription' => $request->discountDescription,
                'discountType' => $request->discountType,
                'discountTrigger' => $request->discountTrigger,
                'amountType' => $request->amountType,
                'discountCapType' => $request->discountCapType,
                'usageLimit' => $request->usageLimit,
                'expirationType' => $request->expirationType,
            ];

            // Handle conditional fields - clear if hidden
            // Discount Code (only if trigger is "Discount Code")
            if ($request->discountTrigger === 'Discount Code') {
                $discountData['discountCode'] = $request->discountCode;
            } else {
                $discountData['discountCode'] = null;
            }

            // Value fields (based on Amount Type)
            if ($request->amountType === 'Percentage') {
                $discountData['valuePercent'] = $request->valuePercent;
                $discountData['valueAmount'] = null;
                $discountData['valueReplacement'] = null;
            } elseif ($request->amountType === 'Specific Amount') {
                $discountData['valuePercent'] = null;
                $discountData['valueAmount'] = $request->valueAmount;
                $discountData['valueReplacement'] = null;
            } elseif ($request->amountType === 'Price Replacement') {
                $discountData['valuePercent'] = null;
                $discountData['valueAmount'] = null;
                $discountData['valueReplacement'] = $request->valueReplacement;
            } else {
                $discountData['valuePercent'] = null;
                $discountData['valueAmount'] = null;
                $discountData['valueReplacement'] = null;
            }

            // Discount Cap Value (only if cap type is not "None")
            if ($request->discountCapType !== 'None' && $request->discountCapType) {
                $discountData['discountCapValue'] = $request->discountCapValue;
            } else {
                $discountData['discountCapValue'] = null;
            }

            // Expiration fields (based on Expiration Type)
            if ($request->expirationType === 'Time and Date' && $request->dateExpiration && $request->timeExpiration) {
                // Parse the date and time
                $dateExpiration = \Carbon\Carbon::parse($request->dateExpiration);
                $timeExpiration = \Carbon\Carbon::parse($request->timeExpiration);

                // Combine date and time
                $dateTimeExpiration = $dateExpiration->format('Y-m-d') . ' ' . $timeExpiration->format('H:i:s');
                $discountData['dateTimeExpiration'] = $dateTimeExpiration;
                $discountData['timerCountdown'] = null;
            } elseif ($request->expirationType === 'Countdown') {
                $discountData['dateTimeExpiration'] = null;
                $discountData['timerCountdown'] = $request->countdownMinutes;
            } else {
                $discountData['dateTimeExpiration'] = null;
                $discountData['timerCountdown'] = null;
            }

            // Update the discount
            $discount->update($discountData);

            return redirect()->route('ecom-discounts')
                ->with('success', 'Discount has been updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the discount. Please try again.');
        }
    }

    /**
     * Update the status of a discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'isActive' => 'required|in:0,1',
            ], [
                'isActive.required' => 'Status is required.',
                'isActive.in' => 'Status must be either Active or Inactive.',
            ]);

            // Find the discount
            $discount = EcomProductDiscount::active()->findOrFail($id);

            // Update the status
            $discount->update(['isActive' => $request->isActive]);

            $statusText = $request->isActive ? 'Active' : 'Inactive';

            return response()->json([
                'success' => true,
                'message' => 'Discount status has been updated to ' . $statusText . ' successfully!',
                'status' => $request->isActive,
                'statusText' => $statusText
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the discount status. Please try again.'
            ], 500);
        }
    }

    /**
     * Get discount details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $discount = EcomProductDiscount::active()->findOrFail($id);

            // Format the discount details
            $details = [];

            // Basic details
            if ($discount->discountName) {
                $details['Discount Name'] = $discount->discountName;
            }

            if ($discount->discountDescription) {
                $details['Description'] = $discount->discountDescription;
            }

            if ($discount->discountType) {
                $details['Discount Type'] = $discount->discountType;
            }

            if ($discount->discountTrigger) {
                $details['Discount Trigger'] = $discount->discountTrigger;
            }

            if ($discount->discountCode) {
                $details['Discount Code'] = $discount->discountCode;
            }

            if ($discount->amountType) {
                $details['Amount Type'] = $discount->amountType;
            }

            // Value fields
            if ($discount->valuePercent !== null) {
                $details['Value Percent'] = $discount->valuePercent . '%';
            }

            if ($discount->valueAmount !== null) {
                $details['Value Amount'] = 'Php ' . number_format($discount->valueAmount, 2);
            }

            if ($discount->valueReplacement !== null) {
                $details['Value Replacement'] = 'Php ' . number_format($discount->valueReplacement, 2);
            }

            if ($discount->discountCapType) {
                $details['Discount Cap Type'] = $discount->discountCapType;
            }

            if ($discount->discountCapValue !== null) {
                $details['Discount Cap Value'] = 'Php ' . number_format($discount->discountCapValue, 2);
            }

            if ($discount->usageLimit !== null) {
                $details['Usage Limit'] = $discount->usageLimit;
            }

            if ($discount->expirationType) {
                $details['Expiration Type'] = $discount->expirationType;
            }

            if ($discount->dateTimeExpiration) {
                $details['Date & Time Expiration'] = \Carbon\Carbon::parse($discount->dateTimeExpiration)->format('F j, Y g:ia');
            }

            if ($discount->timerCountdown !== null) {
                $details['Countdown Minutes'] = $discount->timerCountdown;
            }

            // Status
            $details['Status'] = $discount->isActive ? 'Active' : 'Inactive';

            // Timestamps
            if ($discount->created_at) {
                $details['Date Created'] = \Carbon\Carbon::parse($discount->created_at)->format('F j, Y g:ia');
            }

            if ($discount->updated_at) {
                $details['Last Updated'] = \Carbon\Carbon::parse($discount->updated_at)->format('F j, Y g:ia');
            }

            return response()->json([
                'success' => true,
                'discount' => $discount,
                'details' => $details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found.'
            ], 404);
        }
    }

    /**
     * Soft delete a discount by setting deleteStatus to 0.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $discount = EcomProductDiscount::findOrFail($id);
            $discount->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Discount "' . $discount->discountName . '" has been deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the discount. Please try again.'
            ], 500);
        }
    }

    /**
     * Get discounts data for DataTables.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            try {
                $discounts = EcomProductDiscount::active()
                    ->orderBy('created_at', 'desc')
                    ->get();

                return DataTables::of($discounts)
                    ->addColumn('value', function($discount) {
                        if ($discount->amountType === 'Percentage' && $discount->valuePercent !== null) {
                            return '<span class="badge bg-info">' . $discount->valuePercent . '%</span>';
                        } elseif ($discount->amountType === 'Specific Amount' && $discount->valueAmount !== null) {
                            return '<span class="badge bg-success">Php ' . number_format($discount->valueAmount, 2) . '</span>';
                        } elseif ($discount->amountType === 'Price Replacement' && $discount->valueReplacement !== null) {
                            return '<span class="badge bg-warning">Php ' . number_format($discount->valueReplacement, 2) . '</span>';
                        } else {
                            return '<span class="badge bg-secondary">N/A</span>';
                        }
                    })
                    ->addColumn('active', function($discount) {
                        if ($discount->isActive) {
                            return '<span class="badge bg-success">Yes</span>';
                        } else {
                            return '<span class="badge bg-danger">No</span>';
                        }
                    })
                    ->addColumn('action', function($discount) {
                        $editUrl = route('ecom-discounts.edit', ['id' => $discount->id]);
                        $discountName = htmlspecialchars($discount->discountName ?? '', ENT_QUOTES, 'UTF-8');
                        return '
                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-info badge-style details-btn"
                                        title="Details"
                                        data-discount-id="' . $discount->id . '"
                                        data-discount-name="' . $discountName . '">
                                    <i class="bx bx-info-circle me-1"></i>Details
                                </button>

                                <a href="' . $editUrl . '"
                                   class="btn btn-sm btn-outline-success badge-style"
                                   title="Edit">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-primary badge-style status-btn"
                                        title="Status"
                                        data-discount-id="' . $discount->id . '"
                                        data-discount-name="' . $discountName . '"
                                        data-current-status="' . ($discount->isActive ? 1 : 0) . '">
                                    <i class="bx bx-toggle-right me-1"></i>Status
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                        title="Delete"
                                        data-discount-id="' . $discount->id . '"
                                        data-discount-name="' . $discountName . '">
                                    <i class="bx bx-trash me-1"></i>Delete
                                </button>
                            </div>
                        ';
                    })
                    ->rawColumns(['value', 'active', 'action'])
                    ->make(true);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('DataTables Error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'An error occurred while loading data.'
                ], 500);
            }
        }
    }
}

