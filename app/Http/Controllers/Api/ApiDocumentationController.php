<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\EcomProductStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiDocumentationController extends Controller
{
    /**
     * Display the Leads API documentation
     */
    public function leads()
    {
        $user = Auth::user();

        // Get available stores for the example
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        // Get or generate API key for the user
        if (!$user->api_key) {
            $user->api_key = $this->generateApiKey();
            $user->save();
        }

        // Get lead field options for documentation
        $statusOptions = CrmLead::STATUS_OPTIONS;
        $priorityOptions = CrmLead::PRIORITY_OPTIONS;
        $companySizeOptions = CrmLead::COMPANY_SIZE_OPTIONS;

        return view('api-docs.leads', compact(
            'user',
            'stores',
            'statusOptions',
            'priorityOptions',
            'companySizeOptions'
        ));
    }

    /**
     * Regenerate API key for the user
     */
    public function regenerateApiKey()
    {
        $user = Auth::user();
        $user->api_key = $this->generateApiKey();
        $user->save();

        return response()->json([
            'success' => true,
            'api_key' => $user->api_key,
            'message' => 'API key regenerated successfully'
        ]);
    }

    /**
     * Generate a unique API key
     */
    private function generateApiKey(): string
    {
        do {
            $key = 'dsaxis_' . Str::random(48);
        } while (User::where('api_key', $key)->exists());

        return $key;
    }
}
