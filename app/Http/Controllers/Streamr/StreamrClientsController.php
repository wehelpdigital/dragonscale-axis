<?php

namespace App\Http\Controllers\Streamr;

use App\Models\StreamrClient;
use App\Models\StreamrClientAction;
use App\Models\StreamrDeskTicket;
use App\Models\StreamrOrder;
use App\Services\Streamr\StreamrCallbacks;
use App\Services\Streamr\StreamrTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

/**
 * Streamr > Clients: every Streamr user as Streamr last described them
 * (streamr_clients, pushed by Streamr), with filters, a CSV, a details modal
 * and the actions. An action is sent to Streamr at once and signed
 * (StreamrCallbacks::clientAction); Streamr applies it, emails the client
 * when it should, and answers with the client's fresh snapshot, which is
 * stored here. Nothing about a client is decided in this app.
 */
class StreamrClientsController extends StreamrController
{
    public function index(Request $request)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Clients');
        }

        $now = StreamrTime::now()->format('Y-m-d H:i:s');
        $soon = StreamrTime::now()->addDays(7)->format('Y-m-d H:i:s');
        $base = StreamrClient::active();
        $paying = fn () => (clone $base)->whereNotIn('role', StreamrClient::ADMIN_ROLES);

        $stats = [
            'clients' => (clone $base)->count(),
            'activePaid' => $this->whereState($paying(), 'active', $now)->where('planPrice', '>', 0)->count(),
            'expiring' => $paying()->where('subscriptionState', 'active')->where('isLifetime', false)
                ->whereNotNull('expiresAt')->where('expiresAt', '>', $now)->where('expiresAt', '<=', $soon)->count(),
            'lifetime' => $paying()->where('subscriptionState', 'active')->where('isLifetime', true)->count(),
            'closed' => (clone $base)->where(fn ($q) => $q->where('subscriptionState', 'suspended')
                ->orWhereIn('accountStatus', ['suspended', 'revoked', 'deleted']))->count(),
            'admins' => (clone $base)->whereIn('role', StreamrClient::ADMIN_ROLES)->count(),
        ];

        return view('streamr.clients', [
            'stats' => $stats,
            'open' => $this->id($request->query('open')),
            'streamrBase' => rtrim((string) config('streamr.app_url', ''), '/'),
            'linked' => app(StreamrCallbacks::class)->configured(),
        ]);
    }

    /** DataTables (server-side) rows. */
    public function data(Request $request)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }
        $now = StreamrTime::now();

        return DataTables::of($this->filtered($request))
            ->addColumn('fullName', fn (StreamrClient $c) => $c->fullName())
            ->addColumn('isAdmin', fn (StreamrClient $c) => $c->isAdmin())
            ->addColumn('accountLabel', fn (StreamrClient $c) => StreamrClient::ACCOUNTS[$c->accountStatus] ?? $c->accountStatus)
            ->addColumn('accountBadge', fn (StreamrClient $c) => StreamrClient::ACCOUNT_BADGES[$c->accountStatus] ?? 'bg-secondary')
            ->addColumn('stateKey', fn (StreamrClient $c) => $c->computedState($now))
            ->addColumn('stateLabel', fn (StreamrClient $c) => StreamrClient::STATES[$c->computedState($now)])
            ->addColumn('stateBadge', fn (StreamrClient $c) => StreamrClient::STATE_BADGES[$c->computedState($now)])
            ->addColumn('planLabel', fn (StreamrClient $c) => $c->planName ?: (StreamrClient::PLANS[$c->planKey] ?? null))
            ->addColumn('priceFormatted', fn (StreamrClient $c) => $c->planPrice !== null ? '₱' . number_format((float) $c->planPrice, 2) : null)
            ->addColumn('startsFormatted', fn (StreamrClient $c) => $c->startsAt ? StreamrTime::show($c->startsAt, '', 'M j, Y') : null)
            ->addColumn('expiresFormatted', fn (StreamrClient $c) => $c->isLifetime ? 'Lifetime' : ($c->expiresAt ? StreamrTime::show($c->expiresAt, '', 'M j, Y') : null))
            ->addColumn('registeredFormatted', fn (StreamrClient $c) => $c->registeredAt ? StreamrTime::show($c->registeredAt, '', 'M j, Y') : null)
            ->addColumn('lastLoginFormatted', fn (StreamrClient $c) => $c->lastLoginAt ? StreamrTime::show($c->lastLoginAt) : null)
            ->addColumn('lastOrderUrl', fn (StreamrClient $c) => $c->lastOrderNumber ? route('streamr-payments.index', ['open' => $c->lastOrderNumber]) : null)
            ->escapeColumns([])
            ->make(true);
    }

    /** One client for the details modal: /streamr-clients-show?id= */
    public function show(Request $request, $id = null)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }
        $client = StreamrClient::active()->find($this->id($id ?? $request->query('id')));
        if (! $client) {
            return $this->fail('That client was not found.', 404);
        }

        return $this->json($this->details($client));
    }

    public function export(Request $request)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Clients');
        }
        $query = $this->filtered($request);
        $now = StreamrTime::now();

        return $this->csv('streamr-clients-' . $now->format('Ymd-His') . '.csv', [
            'Streamr user ID', 'First name', 'Last name', 'Email', 'Phone', 'Role', 'Account', 'Store', 'Handle', 'Store address',
            'Plan', 'Price', 'State', 'Starts', 'Expires', 'Lifetime', 'Plan credits', 'Purchased credits', 'Available credits',
            'Total paid', 'Orders', 'Last order', 'Lives', 'Live minutes', 'Leads', 'Products', 'Registered', 'Last login', 'Synced',
        ], function (callable $emit) use ($query, $now) {
            $query->orderBy('id')->chunk(500, function ($clients) use ($emit, $now) {
                foreach ($clients as $c) {
                    $emit([
                        $c->streamrUserId, $c->firstName, $c->lastName, $c->email, $c->phone, $c->role, $c->accountStatus,
                        $c->storeName, $c->storeHandle, $c->storeUrl, $c->planName ?: $c->planKey,
                        $c->planPrice !== null ? number_format((float) $c->planPrice, 2, '.', '') : '', $c->computedState($now),
                        StreamrTime::show($c->startsAt, '', 'Y-m-d H:i:s'), StreamrTime::show($c->expiresAt, '', 'Y-m-d H:i:s'),
                        $c->isLifetime ? 'yes' : 'no', $c->planCredits, $c->purchasedCredits, $c->availableCredits,
                        number_format((float) $c->totalPaid, 2, '.', ''), $c->ordersCount, $c->lastOrderNumber, $c->livesCount,
                        $c->liveMinutes, $c->leadsCount, $c->productsCount, StreamrTime::show($c->registeredAt, '', 'Y-m-d H:i:s'),
                        StreamrTime::show($c->lastLoginAt, '', 'Y-m-d H:i:s'), StreamrTime::show($c->syncedAt, '', 'Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    /** POST /streamr-clients-action?id=&action= : validated here, applied by Streamr. */
    public function action(Request $request, $id = null, $action = null)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }
        $client = StreamrClient::active()->find($this->id($id ?? $request->query('id')));
        if (! $client) {
            return $this->fail('That client was not found.', 404);
        }
        $action = (string) ($action ?? $request->query('action', ''));
        if (! array_key_exists($action, StreamrClientAction::ACTIONS)) {
            return $this->fail('That action is not one Streamr knows.', 422);
        }

        [$payload, $error] = $this->payloadFor($action, $request, $client);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $result = app(StreamrCallbacks::class)->clientAction((int) $client->streamrUserId, $action, $payload, Auth::user());

        return $this->answer($result, $client);
    }

    /** POST /streamr-clients-refresh?id= : read the client's snapshot from Streamr now. */
    public function refresh(Request $request, $id = null)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }
        $client = StreamrClient::active()->find($this->id($id ?? $request->query('id')));
        if (! $client) {
            return $this->fail('That client was not found.', 404);
        }

        $result = app(StreamrCallbacks::class)->client((int) $client->streamrUserId);
        if ($result['ok']) {
            $result['message'] = 'Refreshed from Streamr.';
        }

        return $this->answer($result, $client);
    }

    // ------------------------------------------------------------------

    /** How a Streamr answer reaches the page: 200 applied, 422 refused (its message), 502/503 not reached. */
    public static function statusFor(array $result): int
    {
        if ($result['ok']) {
            return 200;
        }
        if ($result['code'] === 'not_configured') {
            return 503;
        }
        $status = (int) ($result['status'] ?? 0);

        return $status === 0 || $status === 401 || $status >= 500 ? 502 : 422;
    }

    private function answer(array $result, StreamrClient $client)
    {
        $fresh = StreamrClient::find($client->id) ?? $client;
        $status = self::statusFor($result);
        if ($status === 200) {
            return $this->json(['client' => $this->details($fresh)], (string) $result['message']);
        }
        $message = $status === 422 ? 'Streamr couldn\'t apply it: ' . $result['message'] : $result['message'];

        return $this->fail($message, $status, ['client' => $this->details($fresh), 'actionId' => $result['action']->id ?? null]);
    }

    /**
     * The payload Streamr expects for each action (its architecture §10.3),
     * checked here first; Streamr checks again.
     *
     * @return array{0: array, 1: ?string}
     */
    private function payloadFor(string $action, Request $request, StreamrClient $client): array
    {
        $reason = ['required', 'string', 'min:3', 'max:500'];
        $rules = match ($action) {
            'suspend_subscription', 'revoke_subscription', 'suspend_account' => ['reason' => $reason],
            'extend_days' => ['days' => ['required', 'integer', 'min:1', 'max:3650'], 'reason' => $reason],
            'set_expiry' => ['expires_at' => ['required', 'string', 'max:40'], 'reason' => $reason],
            'cancel_queued' => ['subscription_id' => ['required', 'integer', 'min:1'], 'reason' => $reason],
            'change_plan' => ['plan_key' => ['required', 'in:monthly,yearly,lifetime'], 'keep_expiry' => ['nullable', 'boolean'], 'reason' => $reason],
            'adjust_credits' => ['delta' => ['required', 'integer', 'not_in:0', 'min:-10000000', 'max:10000000'],
                'bucket' => ['required', 'in:purchased,plan'], 'reason' => ['required', 'string', 'min:3', 'max:191']],
            'update_profile' => ['first_name' => ['nullable', 'string', 'max:60'], 'last_name' => ['nullable', 'string', 'max:60'],
                'phone' => ['nullable', 'regex:/^(09|\+639)\d{9}$/'], 'store_name' => ['nullable', 'string', 'min:2', 'max:80']],
            'change_email' => ['email' => ['required', 'email', 'max:191']],
            'add_note' => ['body' => ['required', 'string', 'min:1', 'max:2000']],
            default => [],
        };

        $input = $request->except(['_token', 'id', 'action']);
        if (isset($input['phone'])) {
            $input['phone'] = preg_replace('/[\s\-]+/', '', (string) $input['phone']);
        }
        $validator = Validator::make($input, $rules, [
            'reason.required' => 'Write the reason (Streamr keeps it with the change).',
            'reason.min' => 'Write a reason of at least 3 characters.',
            'delta.not_in' => 'The credits to add or take must not be 0.',
            'phone.regex' => 'The mobile number looks like 09XXXXXXXXX.',
            'store_name.min' => 'The store name needs at least 2 characters.',
        ]);
        if ($validator->fails()) {
            return [[], $validator->errors()->first()];
        }

        $data = $validator->validated();
        switch ($action) {
            case 'extend_days':
                return [['days' => (int) $data['days'], 'reason' => trim($data['reason'])], null];

            case 'set_expiry':
                if ($client->isLifetime) {
                    return [[], 'A lifetime plan has no end date to change.'];
                }
                $at = $this->manilaInput((string) $data['expires_at']);
                $now = StreamrTime::now();
                if (! $at) {
                    return [[], 'Pick the new end date and time.'];
                }
                if ($at->lt($now->copy()->addHour()) || $at->gt($now->copy()->addDays(3650))) {
                    return [[], 'The new end date must be at least an hour from now and within 10 years.'];
                }

                return [['expires_at' => $at->toIso8601String(), 'reason' => trim($data['reason'])], null];

            case 'cancel_queued':
                $ids = array_map(fn ($q) => (int) $q['id'], $client->queuedPeriods());
                if (! in_array((int) $data['subscription_id'], $ids, true)) {
                    return [[], 'That upcoming period is not on this client\'s plan (refresh the client from Streamr).'];
                }

                return [['subscription_id' => (int) $data['subscription_id'], 'reason' => trim($data['reason'])], null];

            case 'change_plan':
                return [['plan_key' => $data['plan_key'], 'keep_expiry' => $request->boolean('keep_expiry'), 'reason' => trim($data['reason'])], null];

            case 'adjust_credits':
                return [['delta' => (int) $data['delta'], 'bucket' => $data['bucket'], 'reason' => trim($data['reason'])], null];

            case 'update_profile':
                $profile = [];
                foreach (['first_name', 'last_name', 'phone', 'store_name'] as $key) {
                    $value = trim((string) ($data[$key] ?? ''));
                    if ($value !== '') {
                        $profile[$key] = $value;
                    }
                }
                if ($profile === []) {
                    return [[], 'Change at least one of the details.'];
                }

                return [$profile, null];

            case 'change_email':
                return [['email' => mb_strtolower(trim($data['email']))], null];

            case 'add_note':
                return [['body' => trim($data['body'])], null];

            case 'suspend_subscription':
            case 'revoke_subscription':
            case 'suspend_account':
                return [['reason' => trim($data['reason'])], null];
        }

        return [[], null];
    }

    /** "2026-12-08T14:30" from a datetime-local box, read as Manila time. */
    private function manilaInput(string $value): ?Carbon
    {
        $value = trim($value);
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
            try {
                $at = Carbon::createFromFormat($format, $value, StreamrTime::ZONE);
                if ($at && $at->format($format) === $value) {
                    return $at;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function filtered(Request $request)
    {
        $query = StreamrClient::active();
        $now = StreamrTime::now()->format('Y-m-d H:i:s');

        if ($request->filled('searchFilter')) {
            // Every word must match somewhere ("Lia Santos" finds first + last name), the same on MySQL and SQLite.
            $words = array_slice(preg_split('/\s+/', trim((string) $request->input('searchFilter'))) ?: [], 0, 5);
            foreach ($words as $word) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $word) . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('firstName', 'like', $like)->orWhere('lastName', 'like', $like)->orWhere('email', 'like', $like)
                        ->orWhere('storeName', 'like', $like)->orWhere('storeHandle', 'like', $like);
                });
            }
        }
        $plan = (string) $request->input('plan', '');
        if (in_array($plan, ['monthly', 'yearly', 'lifetime'], true)) {
            $query->where('planKey', $plan);
        } elseif ($plan === 'none') {
            $query->where(fn ($q) => $q->whereNull('planKey')->orWhere('subscriptionState', 'none'));
        }
        $state = (string) $request->input('state', '');
        if (array_key_exists($state, StreamrClient::STATES)) {
            $this->whereState($query, $state, $now);
        }
        $days = (int) $request->input('expiringDays');
        if (in_array($days, [7, 14, 30], true)) {
            $query->where('subscriptionState', 'active')->where('isLifetime', false)->whereNotNull('expiresAt')
                ->where('expiresAt', '>', $now)->where('expiresAt', '<=', StreamrTime::now()->addDays($days)->format('Y-m-d H:i:s'));
        }
        $role = (string) $request->input('role', '');
        if ($role === 'admin') {
            $query->whereIn('role', StreamrClient::ADMIN_ROLES);
        } elseif ($role === 'client') {
            $query->whereNotIn('role', StreamrClient::ADMIN_ROLES);
        }
        $account = (string) $request->input('account', '');
        if (array_key_exists($account, StreamrClient::ACCOUNTS)) {
            $query->where('accountStatus', $account);
        }
        foreach (['registeredFrom' => '>=', 'registeredTo' => '<='] as $param => $op) {
            $day = (string) $request->input($param, '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
                $query->where('registeredAt', $op, $day . ($op === '>=' ? ' 00:00:00' : ' 23:59:59'));
            }
        }

        return $query;
    }

    /** The state as Clients shows it: an "active" plan whose end has passed counts as expired. */
    private function whereState($query, string $state, string $now)
    {
        switch ($state) {
            case 'active':
                $query->where('subscriptionState', 'active')
                    ->where(fn ($q) => $q->where('isLifetime', true)->orWhereNull('expiresAt')->orWhere('expiresAt', '>', $now));
                break;
            case 'grace':
                $query->where('subscriptionState', 'grace')->where(fn ($q) => $q->whereNull('graceUntil')->orWhere('graceUntil', '>', $now));
                break;
            case 'expired':
                $query->where(fn ($q) => $q->where('subscriptionState', 'expired')
                    ->orWhere(fn ($a) => $a->where('subscriptionState', 'active')->where('isLifetime', false)
                        ->whereNotNull('expiresAt')->where('expiresAt', '<=', $now))
                    ->orWhere(fn ($g) => $g->where('subscriptionState', 'grace')->whereNotNull('graceUntil')->where('graceUntil', '<=', $now)));
                break;
            default:
                $query->where('subscriptionState', $state);
        }

        return $query;
    }

    private function details(StreamrClient $c): array
    {
        $now = StreamrTime::now();
        $state = $c->computedState($now);
        $snapshot = is_array($c->snapshot) ? $c->snapshot : [];

        $mine = StreamrOrder::active()->where('streamrUserId', $c->streamrUserId)->orderByDesc('id')->limit(20)->get();
        $ecomRows = $mine->isEmpty() ? collect() : DB::table('ecom_orders')->whereIn('id', $mine->pluck('ecomOrderId')->all())
            ->get(['id', 'orderStatus', 'paymentVerificationStatus', 'created_at'])->keyBy('id');
        $orders = $mine
            ->map(function (StreamrOrder $o) use ($ecomRows) {
                $ecom = $ecomRows->get($o->ecomOrderId);

                return [
                    'number' => $o->orderNumber,
                    'item' => $o->variantName,
                    'total' => (float) $o->total,
                    'orderStatus' => (string) ($ecom->orderStatus ?? 'pending'),
                    'paymentStatus' => (string) ($ecom->paymentVerificationStatus ?? ''),
                    'created' => StreamrTime::show($ecom->created_at ?? $o->created_at),
                    'url' => route('streamr-payments.index', ['open' => $o->orderNumber]),
                ];
            })->values();

        $actions = StreamrClientAction::active()->where('streamrUserId', $c->streamrUserId)->orderByDesc('id')->limit(20)->get()
            ->map(fn (StreamrClientAction $a) => [
                'id' => $a->id,
                'label' => $a->label(),
                'action' => $a->action,
                'by' => (string) ($a->requestedByName ?: 'An admin'),
                'status' => $a->status,
                'badge' => StreamrClientAction::STATUS_BADGES[$a->status] ?? 'bg-secondary',
                'message' => (string) ($a->result['message'] ?? ''),
                'error' => (string) ($a->error ?? ''),
                'httpStatus' => $a->httpStatus,
                'at' => StreamrTime::show($a->created_at),
                'canRetry' => $a->status === StreamrClientAction::FAILED,
            ])->values();

        $tickets = StreamrDeskTicket::active()->where('streamrUserId', $c->streamrUserId)->where('status', '!=', 'closed')
            ->orderByDesc('lastMessageAt')->limit(10)->get()
            ->map(fn (StreamrDeskTicket $t) => [
                'id' => $t->id,
                'number' => $t->ticketNumber,
                'subject' => $t->subject,
                'status' => $t->status,
                'statusLabel' => StreamrDeskTicket::STATUSES[$t->status] ?? $t->status,
                'badge' => StreamrDeskTicket::STATUS_BADGES[$t->status] ?? 'bg-secondary',
                'url' => route('streamr-support.index', ['id' => $t->id]),
            ])->values();

        return [
            'id' => $c->id,
            'streamrUserId' => $c->streamrUserId,
            'name' => $c->fullName(),
            'firstName' => (string) $c->firstName,
            'lastName' => (string) $c->lastName,
            'email' => $c->email,
            'phone' => (string) $c->phone,
            'role' => $c->role,
            'isAdmin' => $c->isAdmin(),
            'account' => ['key' => $c->accountStatus, 'label' => StreamrClient::ACCOUNTS[$c->accountStatus] ?? $c->accountStatus,
                'badge' => StreamrClient::ACCOUNT_BADGES[$c->accountStatus] ?? 'bg-secondary'],
            'store' => ['id' => $c->storeId, 'name' => $c->storeName, 'handle' => $c->storeHandle, 'url' => $c->storeUrl,
                'customDomain' => $c->customDomain],
            'subscription' => [
                'planKey' => $c->planKey,
                'planName' => $c->planName ?: (StreamrClient::PLANS[$c->planKey] ?? null),
                'price' => $c->planPrice !== null ? (float) $c->planPrice : null,
                'state' => ['key' => $state, 'label' => StreamrClient::STATES[$state], 'badge' => StreamrClient::STATE_BADGES[$state]],
                'storedState' => $c->subscriptionState,
                'startsAt' => StreamrTime::show($c->startsAt, ''),
                'expiresAt' => $c->isLifetime ? 'Lifetime' : StreamrTime::show($c->expiresAt, ''),
                'expiresAtInput' => $c->expiresAt && ! $c->isLifetime ? StreamrTime::parse($c->expiresAt)->format('Y-m-d\TH:i') : '',
                'isLifetime' => (bool) $c->isLifetime,
                'graceUntil' => StreamrTime::show($c->graceUntil, ''),
                'queued' => array_map(fn ($q) => [
                    'id' => (int) $q['id'],
                    'plan' => StreamrClient::PLANS[$q['plan_key'] ?? ''] ?? ucfirst((string) ($q['plan_key'] ?? 'plan')),
                    'startsAt' => StreamrTime::show($q['starts_at'] ?? null, '?', 'M j, Y'),
                    'expiresAt' => StreamrTime::show($q['expires_at'] ?? null, '?', 'M j, Y'),
                ], $c->queuedPeriods()),
            ],
            'credits' => [
                'plan' => $c->planCredits,
                'purchased' => $c->purchasedCredits,
                'available' => $c->availableCredits,
                'unlimited' => $c->isAdmin() || (bool) ($snapshot['credits']['unlimited'] ?? false),
                'cycleEndsAt' => StreamrTime::show($c->planCycleEndsAt, ''),
            ],
            'totals' => ['paid' => (float) $c->totalPaid, 'orders' => $c->ordersCount, 'lastOrderNumber' => $c->lastOrderNumber],
            'usage' => ['lives' => $c->livesCount, 'liveMinutes' => $c->liveMinutes, 'leads' => $c->leadsCount, 'products' => $c->productsCount,
                'storage' => $this->bytes((int) $c->storageBytes)],
            'notes' => [
                'count' => $c->notesCount,
                'recent' => array_values(array_map(fn ($n) => [
                    'at' => StreamrTime::show($n['at'] ?? null, ''),
                    'by' => (string) ($n['by'] ?? ''),
                    'body' => (string) ($n['body'] ?? ''),
                ], array_filter((array) ($c->recentNotes ?? []), 'is_array'))),
            ],
            'orders' => $orders,
            'actions' => $actions,
            'tickets' => $tickets,
            'registeredAt' => StreamrTime::show($c->registeredAt, ''),
            'lastLoginAt' => StreamrTime::show($c->lastLoginAt, ''),
            'lastLiveAt' => StreamrTime::show($c->lastLiveAt, ''),
            'snapshotAt' => StreamrTime::show($c->snapshotAt, ''),
            'syncedAt' => StreamrTime::show($c->syncedAt, ''),
            'links' => ['streamr' => $this->streamrUrl('/admin/customers/' . $c->streamrUserId)],
        ];
    }

    private function bytes(int $bytes): string
    {
        foreach (['GB' => 1073741824, 'MB' => 1048576, 'KB' => 1024] as $unit => $size) {
            if ($bytes >= $size) {
                return number_format($bytes / $size, 1) . ' ' . $unit;
            }
        }

        return $bytes . ' B';
    }
}
