<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomClientShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientShippingsController extends Controller
{
    /**
     * Display the client shippings list page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get unique provinces for filter dropdown
        $provinces = EcomClientShippingAddress::active()
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        return view('ecommerce.client-shippings.index', compact('provinces'));
    }

    /**
     * Get shipping addresses data for AJAX with pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        try {
            $query = EcomClientShippingAddress::active();

            // Search by recipient name, phone, email, or address
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('firstName', 'like', "%{$search}%")
                      ->orWhere('middleName', 'like', "%{$search}%")
                      ->orWhere('lastName', 'like', "%{$search}%")
                      ->orWhere('phoneNumber', 'like', "%{$search}%")
                      ->orWhere('emailAddress', 'like', "%{$search}%")
                      ->orWhere('municipality', 'like', "%{$search}%")
                      ->orWhere('province', 'like', "%{$search}%")
                      ->orWhere('street', 'like', "%{$search}%")
                      ->orWhere('addressLabel', 'like', "%{$search}%");
                });
            }

            // Filter by province
            if ($request->filled('province')) {
                $query->where('province', $request->province);
            }

            // Filter by municipality
            if ($request->filled('municipality')) {
                $query->where('municipality', 'like', "%{$request->municipality}%");
            }

            // Get total count before pagination
            $totalCount = EcomClientShippingAddress::active()->count();
            $filteredCount = $query->count();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            $addresses = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Format data
            $addressData = $addresses->map(function($address) {
                return [
                    'id' => $address->id,
                    'addressLabel' => $address->addressLabel,
                    'recipientName' => $address->full_name,
                    'firstName' => $address->firstName,
                    'middleName' => $address->middleName,
                    'lastName' => $address->lastName,
                    'phoneNumber' => $address->phoneNumber,
                    'emailAddress' => $address->emailAddress,
                    'fullAddress' => $address->full_address,
                    'houseNumber' => $address->houseNumber,
                    'street' => $address->street,
                    'zone' => $address->zone,
                    'municipality' => $address->municipality,
                    'province' => $address->province,
                    'zipCode' => $address->zipCode,
                    'createdAt' => $address->created_at->format('M d, Y h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $addressData,
                'pagination' => [
                    'current_page' => $addresses->currentPage(),
                    'last_page' => $addresses->lastPage(),
                    'per_page' => $addresses->perPage(),
                    'total' => $addresses->total(),
                    'from' => $addresses->firstItem(),
                    'to' => $addresses->lastItem(),
                ],
                'total_count' => $totalCount,
                'filtered_count' => $filteredCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client shipping addresses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching shipping addresses.'
            ], 500);
        }
    }

    /**
     * Store a new shipping address.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'addressLabel' => 'nullable|string|max:100',
                'firstName' => 'required|string|max:100',
                'middleName' => 'nullable|string|max:100',
                'lastName' => 'required|string|max:100',
                'phoneNumber' => 'required|string|max:50',
                'emailAddress' => 'nullable|email|max:255',
                'houseNumber' => 'nullable|string|max:100',
                'street' => 'nullable|string|max:255',
                'zone' => 'nullable|string|max:100',
                'municipality' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'zipCode' => 'nullable|string|max:20',
            ], [
                'firstName.required' => 'First name is required.',
                'lastName.required' => 'Last name is required.',
                'phoneNumber.required' => 'Phone number is required.',
                'municipality.required' => 'Municipality/City is required.',
                'province.required' => 'Province is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $address = EcomClientShippingAddress::create([
                'clientId' => null,
                'orderId' => null,
                'addressLabel' => $request->addressLabel,
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'phoneNumber' => $request->phoneNumber,
                'emailAddress' => $request->emailAddress,
                'houseNumber' => $request->houseNumber,
                'street' => $request->street,
                'zone' => $request->zone,
                'municipality' => $request->municipality,
                'province' => $request->province,
                'zipCode' => $request->zipCode,
                'deleteStatus' => 1,
            ]);

            Log::info('Shipping address created manually', ['address_id' => $address->id]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping address added successfully!',
                'address_id' => $address->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating shipping address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the shipping address.'
            ], 500);
        }
    }
}
