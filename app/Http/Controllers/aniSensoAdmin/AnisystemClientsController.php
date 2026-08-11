<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemSubscription;
use App\Models\AnisystemUser;
use App\Services\AnisystemSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AnisystemClientsController extends Controller
{
    /**
     * Display the AniSystem clients listing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('aniSensoAdmin.anisystemClients.index');
    }

    /**
     * Get clients data for DataTables (each row = client + latest subscription).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        $now = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

        // Latest (highest id) non-deleted subscription per client.
        $latestSub = DB::table('anisystem_subscriptions')
            ->select('userId', DB::raw('MAX(id) as latestSubId'))
            ->where('deleteStatus', 1)
            ->groupBy('userId');

        $query = AnisystemUser::query()
            ->leftJoinSub($latestSub, 'ls', 'ls.userId', '=', 'anisystem_users.id')
            ->leftJoin('anisystem_subscriptions as sub', 'sub.id', '=', 'ls.latestSubId')
            ->where('anisystem_users.deleteStatus', 1)
            ->select(
                'anisystem_users.*',
                'sub.id as subId',
                'sub.planName as subPlanName',
                'sub.price as subPrice',
                'sub.status as subStatus',
                'sub.startsAt as subStartsAt',
                'sub.expiresAt as subExpiresAt',
                'sub.orderNumber as subOrderNumber'
            )
            // AI Credit balance is SUM(delta) over the ledger — a subquery so it
            // paginates with the rows instead of needing a second round trip.
            ->selectSub(
                DB::table('anisystem_ai_credit_ledger')
                    ->selectRaw('COALESCE(SUM(delta), 0)')
                    ->whereColumn('anisystem_ai_credit_ledger.userId', 'anisystem_users.id')
                    ->where('anisystem_ai_credit_ledger.deleteStatus', 1),
                'aiCreditBalance'
            )
            // Role signals: an active worker grant makes them a worker, an
            // owned schedule (or any subscription) a client; adminUserId marks
            // the super admins bridged from this mother site.
            ->selectSub(
                DB::table('as_worker_grants')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('as_worker_grants.workerUserId', 'anisystem_users.id')
                    ->where('as_worker_grants.deleteStatus', 1)
                    ->where('as_worker_grants.status', 'active'),
                'workerGrantCount'
            )
            ->selectSub(
                DB::table('as_cropping_schedules')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('as_cropping_schedules.anisystemUserId', 'anisystem_users.id')
                    ->where('as_cropping_schedules.deleteStatus', 1),
                'ownedScheduleCount'
            )
            ->orderBy('anisystem_users.created_at', 'desc');

        // Filter: name / email search (custom param name — DataTables reserves 'search')
        if ($request->filled('searchFilter')) {
            $s = $request->searchFilter;
            $query->where(function ($w) use ($s) {
                $w->where('anisystem_users.firstName', 'like', "%{$s}%")
                    ->orWhere('anisystem_users.lastName', 'like', "%{$s}%")
                    ->orWhere(DB::raw("CONCAT(anisystem_users.firstName, ' ', anisystem_users.lastName)"), 'like', "%{$s}%")
                    ->orWhere('anisystem_users.email', 'like', "%{$s}%");
            });
        }

        // Filter: subscription status (computed — 'active' rows past expiry count as expired)
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'expired') {
                $query->where(function ($w) use ($now) {
                    $w->where('sub.status', 'expired')
                        ->orWhere(function ($q) use ($now) {
                            $q->where('sub.status', 'active')
                                ->whereNotNull('sub.expiresAt')
                                ->where('sub.expiresAt', '<=', $now);
                        });
                });
            } elseif ($status === 'active') {
                $query->where('sub.status', 'active')
                    ->where(function ($w) use ($now) {
                        $w->whereNull('sub.expiresAt')
                            ->orWhere('sub.expiresAt', '>', $now);
                    });
            } elseif ($status === 'none') {
                $query->whereNull('sub.id');
            } else {
                $query->where('sub.status', $status);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('clientName', function ($client) {
                return trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''));
            })
            ->addColumn('effectiveStatus', function ($client) use ($now) {
                if (!$client->subId) {
                    return 'none';
                }
                if (
                    $client->subStatus === 'active'
                    && $client->subExpiresAt
                    && $client->subExpiresAt <= $now
                ) {
                    return 'expired';
                }
                return $client->subStatus;
            })
            ->addColumn('accountStatus', function ($client) {
                return $client->status; // active | disabled (anisystem_users.status)
            })
            ->addColumn('roles', function ($client) {
                $roles = [];
                if (! empty($client->adminUserId)) {
                    $roles[] = 'admin';
                }
                if (((int) ($client->ownedScheduleCount ?? 0)) > 0 || $client->subId) {
                    $roles[] = 'client';
                }
                if (((int) ($client->workerGrantCount ?? 0)) > 0) {
                    $roles[] = 'worker';
                }
                // Registered but nothing yet — still a plain client account.
                return $roles ?: ['client'];
            })
            ->addColumn('startsAtFormatted', function ($client) {
                return $client->subStartsAt ? Carbon::parse($client->subStartsAt)->format('M j, Y') : null;
            })
            ->addColumn('expiresAtFormatted', function ($client) {
                return $client->subExpiresAt ? Carbon::parse($client->subExpiresAt)->format('M j, Y') : null;
            })
            ->addColumn('registeredFormatted', function ($client) {
                return $client->created_at ? $client->created_at->format('M j, Y') : '';
            })
            ->addColumn('priceFormatted', function ($client) {
                return $client->subPrice !== null ? '₱' . number_format((float) $client->subPrice, 2) : null;
            })
            ->addColumn('aiCredits', function ($client) {
                return (float) ($client->aiCreditBalance ?? 0);
            })
            ->make(true);
    }

    /**
     * Adjust a client's AI Credits by hand (grant a top-up, correct a mistake).
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adjustCredits($id, Request $request)
    {
        try {
            $client = AnisystemUser::active()->find($id);
            if (! $client) {
                return response()->json(['success' => false, 'message' => 'Client not found.'], 404);
            }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'delta' => 'required|numeric|not_in:0|min:-1000000|max:1000000',
                'reason' => 'required|string|max:191',
            ], [
                'delta.not_in' => 'Enter a positive number to add credits, or a negative one to remove them.',
                'reason.required' => 'Say why — it is written to the client\'s credit history.',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $delta = (float) $request->input('delta');
            $service = app(\App\Services\AnisystemAiCreditService::class);

            // Never push a balance below zero from the admin side.
            $current = $service->balance($client->id);
            if ($delta < 0 && $current + $delta < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'That would take the balance below zero — they have '
                        . rtrim(rtrim(number_format($current, 2), '0'), '.') . ' credits.',
                ], 422);
            }

            $balance = $service->adjust(
                $client->id,
                $delta,
                $request->input('reason'),
                'admin',
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => ($delta > 0 ? 'Added ' : 'Removed ') . abs($delta) . ' credits for ' . $client->fullName . '.',
                'data' => ['balance' => $balance],
            ]);
        } catch (\Throwable $e) {
            Log::error('AniSystem AI credit adjustment failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not adjust credits.'], 500);
        }
    }

    /**
     * Client details JSON: user + subscription history + linked ecom orders.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $client = AnisystemUser::active()->find($id);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $subscriptions = AnisystemSubscription::active()
                ->where('userId', $client->id)
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'planName' => $sub->planName,
                        'planKey' => $sub->planKey,
                        'price' => $sub->price !== null ? '₱' . number_format((float) $sub->price, 2) : null,
                        'durationDays' => $sub->durationDays,
                        'status' => $sub->status,
                        'effectiveStatus' => $sub->effective_status,
                        'orderNumber' => $sub->orderNumber,
                        'ecomOrderId' => $sub->ecomOrderId,
                        'startsAt' => $sub->startsAt ? $sub->startsAt->format('M j, Y g:i A') : null,
                        'expiresAt' => $sub->expiresAt ? $sub->expiresAt->format('M j, Y g:i A') : null,
                        'verifiedAt' => $sub->verifiedAt ? $sub->verifiedAt->format('M j, Y g:i A') : null,
                        'suspendedAt' => $sub->suspendedAt ? $sub->suspendedAt->format('M j, Y g:i A') : null,
                        'cancelledAt' => $sub->cancelledAt ? $sub->cancelledAt->format('M j, Y g:i A') : null,
                        'notes' => $sub->notes,
                        'createdAt' => $sub->created_at ? $sub->created_at->format('M j, Y g:i A') : null,
                    ];
                })
                ->values();

            $orderIds = AnisystemSubscription::active()
                ->where('userId', $client->id)
                ->whereNotNull('ecomOrderId')
                ->pluck('ecomOrderId')
                ->unique()
                ->values();

            $orders = collect();
            if ($orderIds->isNotEmpty()) {
                $orders = DB::table('ecom_orders')
                    ->whereIn('id', $orderIds)
                    ->where('deleteStatus', 1)
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function ($o) {
                        return [
                            'id' => $o->id,
                            'orderNumber' => $o->orderNumber,
                            'orderStatus' => $o->orderStatus,
                            'paymentVerificationStatus' => $o->paymentVerificationStatus,
                            'grandTotal' => '₱' . number_format((float) $o->grandTotal, 2),
                            'createdAt' => $o->created_at ? Carbon::parse($o->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A') : null,
                        ];
                    })
                    ->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Client details loaded',
                'data' => [
                    'client' => [
                        'id' => $client->id,
                        'firstName' => $client->firstName,
                        'lastName' => $client->lastName,
                        'fullName' => $client->fullName,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'status' => $client->status,
                        'registeredAt' => $client->created_at ? $client->created_at->format('M j, Y g:i A') : null,
                    ],
                    'subscriptions' => $subscriptions,
                    'orders' => $orders,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading AniSystem client details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading client details'
            ], 500);
        }
    }

    /**
     * Suspend the client's latest usable subscription.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspend($id, AnisystemSubscriptionService $service)
    {
        try {
            $client = AnisystemUser::active()->find($id);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $subscription = $service->suspend($client);

            return response()->json([
                'success' => true,
                'message' => 'Subscription suspended for ' . $client->fullName . '.',
                'data' => [
                    'subscriptionId' => $subscription->id,
                    'status' => $subscription->status,
                ]
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error suspending AniSystem subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error suspending subscription'
            ], 500);
        }
    }

    /**
     * Unsuspend the client's suspended subscription.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsuspend($id, AnisystemSubscriptionService $service)
    {
        try {
            $client = AnisystemUser::active()->find($id);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $subscription = $service->unsuspend($client);

            $label = $subscription->status === 'active'
                ? 'Subscription restored to active.'
                : 'Suspension lifted — subscription had already expired.';

            return response()->json([
                'success' => true,
                'message' => $label,
                'data' => [
                    'subscriptionId' => $subscription->id,
                    'status' => $subscription->status,
                ]
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error unsuspending AniSystem subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error unsuspending subscription'
            ], 500);
        }
    }

    /**
     * Cancel the client's latest cancellable subscription (and its linked
     * unverified ecom order, when applicable).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id, AnisystemSubscriptionService $service)
    {
        try {
            $client = AnisystemUser::active()->find($id);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $subscription = $service->cancel($client);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled for ' . $client->fullName . '.',
                'data' => [
                    'subscriptionId' => $subscription->id,
                    'status' => $subscription->status,
                ]
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error cancelling AniSystem subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling subscription'
            ], 500);
        }
    }
}
