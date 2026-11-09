<?php

namespace App\Http\Controllers\Streamr;

use App\Models\StreamrApiRequest;
use App\Models\StreamrClient;
use App\Models\StreamrClientAction;
use App\Models\StreamrDeskMessage;
use App\Services\Streamr\StreamrCallbacks;
use App\Services\Streamr\StreamrConfigBuilder;
use App\Services\Streamr\StreamrSettings;
use App\Services\Streamr\StreamrTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Streamr > Link status: is the link set up (env names only, never their
 * values), what came in and went out lately, how fresh the client registry
 * is, desk messages Streamr has not picked up, payments waiting, recent
 * errors and failed client actions (with Retry), and a Test link button.
 */
class StreamrHealthController extends StreamrController
{
    public function index()
    {
        if (! $this->installed()) {
            return $this->notInstalled('Link status');
        }

        $now = StreamrTime::now();
        $dayAgo = $now->copy()->subDay()->format('Y-m-d H:i:s');
        $appUrl = (string) config('streamr.app_url', '');
        $store = StreamrSettings::storeRow();
        $storeByName = $this->storeByName();
        $storeId = config('streamr.store_id');

        $lastIn = StreamrApiRequest::where('direction', 'in')->orderByDesc('id')->first();
        $lastOut = fn (string $kind) => StreamrApiRequest::where('direction', 'out')->where('note', $kind)->orderByDesc('id')->first();
        $pendingOrders = DB::table('streamr_orders as so')->join('ecom_orders as o', 'o.id', '=', 'so.ecomOrderId')
            ->where('so.delete_status', 'active')->where('o.deleteStatus', 1)
            ->where(fn ($q) => $q->where('o.orderStatus', 'pending')->orWhereNull('o.orderStatus'))
            ->where('o.paymentVerificationStatus', 'pending');

        $newestSync = StreamrClient::active()->max('syncedAt');

        return view('streamr.health', [
            'config' => [
                'appUrlSet' => $appUrl !== '',
                'appHost' => $appUrl !== '' ? (string) parse_url($appUrl, PHP_URL_HOST) : null,
                'tokenSet' => trim((string) config('streamr.api_token', '')) !== '',
                'storeName' => StreamrSettings::storeName(),
                'store' => $store,
                'storeIdSet' => (bool) $storeId,
                'storeIdMatches' => ! $storeId || ($store && $storeByName && (int) $store->id === (int) $storeByName->id),
                'storeIdFound' => ! $storeId || ($store && (int) $store->id === (int) $storeId),
                'qrReadable' => $this->qrReadable(),
                'uploadMax' => (string) ini_get('upload_max_filesize'),
                'postMax' => (string) ini_get('post_max_size'),
                'uploadOk' => $this->iniBytes((string) ini_get('upload_max_filesize')) >= 9 * 1048576
                    && $this->iniBytes((string) ini_get('post_max_size')) >= 9 * 1048576,
            ],
            'inbound' => [
                'last' => $lastIn,
                'count24' => StreamrApiRequest::where('direction', 'in')->where('created_at', '>=', $dayAgo)->count(),
                'errors24' => StreamrApiRequest::where('direction', 'in')->where('created_at', '>=', $dayAgo)
                    ->where(fn ($q) => $q->where('status', '>=', 400)->orWhereNotNull('errorCode'))->count(),
                'lastConfig' => StreamrApiRequest::where('direction', 'in')->where('path', '/api/streamr/v1/config')->orderByDesc('id')->first(),
            ],
            'outbound' => [
                'doorbell' => $lastOut('order_changed'),
                'action' => $lastOut('client_action'),
                'support' => StreamrApiRequest::where('direction', 'out')->whereIn('note', ['support_message', 'support_status'])->orderByDesc('id')->first(),
                'ping' => $lastOut('ping'),
            ],
            'registry' => [
                'clients' => StreamrClient::active()->count(),
                'newest' => $newestSync ? StreamrTime::parse($newestSync) : null,
                'stale' => $newestSync ? StreamrTime::parse($newestSync)->lt($now->copy()->subDay()) : false,
            ],
            'desk' => [
                'waiting' => StreamrDeskMessage::active()->where('origin', 'mother')->whereNull('pulledAt')
                    ->where('created_at', '<=', $now->copy()->subMinutes(5)->format('Y-m-d H:i:s'))->count(),
            ],
            'payments' => [
                'pending' => (clone $pendingOrders)->count(),
                'oldest' => ($oldest = (clone $pendingOrders)->min('so.created_at')) ? StreamrTime::parse($oldest) : null,
            ],
            'apiErrors' => StreamrApiRequest::where(fn ($q) => $q->where('status', '>=', 400)->orWhereNotNull('errorCode'))
                ->orderByDesc('id')->limit(20)->get(),
            'failedActions' => StreamrClientAction::active()->where('status', StreamrClientAction::FAILED)->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    /** POST /streamr-health-ping : a signed GET {STREAMR_APP_URL}/api/mother/ping. */
    public function ping()
    {
        $result = app(StreamrCallbacks::class)->ping();
        if ($result['ok']) {
            $data = $result['data'];

            return $this->json([
                'app' => (string) ($data['app'] ?? ''),
                'version' => (string) ($data['version'] ?? ''),
                'time' => (string) ($data['time'] ?? ''),
            ], 'Streamr answered: ' . ($data['app'] ?? 'streamr') . ' ' . ($data['version'] ?? '') . '.');
        }

        return $this->fail($result['message'], StreamrClientsController::statusFor($result));
    }

    /** POST /streamr-health-retry?id= : send a failed client action again (a new action, linked to this one). */
    public function retryAction(Request $request, $id = null)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }
        $failed = StreamrClientAction::active()->find($this->id($id ?? $request->query('id')));
        if (! $failed || $failed->status !== StreamrClientAction::FAILED) {
            return $this->fail('Only a failed action can be sent again.', 422);
        }

        $result = app(StreamrCallbacks::class)->retry($failed, Auth::user());
        $status = StreamrClientsController::statusFor($result);

        return $status === 200
            ? $this->json(['actionId' => $result['action']->id ?? null], $result['message'])
            : $this->fail($status === 422 ? 'Streamr couldn\'t apply it: ' . $result['message'] : $result['message'], $status);
    }

    private function storeByName()
    {
        try {
            return \App\Models\EcomProductStore::where('storeName', StreamrSettings::storeName())->where('deleteStatus', 1)->orderBy('id')->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function qrReadable(): bool
    {
        try {
            return app(StreamrConfigBuilder::class)->gcashQrReadable();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
