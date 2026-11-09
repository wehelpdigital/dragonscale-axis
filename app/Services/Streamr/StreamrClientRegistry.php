<?php

namespace App\Services\Streamr;

use App\Models\StreamrClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * The client registry (streamr_clients): Streamr pushes a snapshot of each
 * user (PUT /api/streamr/v1/clients/{id}, and in every action answer) and the
 * newest one wins, by Streamr's snapshot_at. The full snapshot is kept; the
 * columns are what the Clients page filters and sorts on.
 */
final class StreamrClientRegistry
{
    /** Validation of a snapshot (only the keys the registry relies on). */
    public static function rules(): array
    {
        return [
            'streamr_user_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email', 'max:191'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'max:20'],
            'account_status' => ['nullable', 'string', 'max:20'],
            'store' => ['nullable', 'array'],
            'subscription' => ['nullable', 'array'],
            'subscription.queued' => ['nullable', 'array'],
            'credits' => ['nullable', 'array'],
            'totals' => ['nullable', 'array'],
            'usage' => ['nullable', 'array'],
            'notes' => ['nullable', 'array'],
            'snapshot_at' => ['required', 'date'],
        ];
    }

    /** Store a snapshot when it is newer than the one held. false = it was older (stale) and changed nothing. */
    public function store(array $snapshot): bool
    {
        $id = (int) ($snapshot['streamr_user_id'] ?? 0);
        $at = StreamrTime::parse($snapshot['snapshot_at'] ?? null);
        if ($id <= 0 || ! $at) {
            return false;
        }

        $client = StreamrClient::where('streamrUserId', $id)->first();
        if ($client && $client->snapshotAt && $at->lte($client->snapshotAt)) {
            return false;
        }

        $values = $this->columns($snapshot, $at);

        try {
            if ($client) {
                $client->fill($values)->save();
            } else {
                StreamrClient::create($values + ['streamrUserId' => $id, 'delete_status' => 'active']);
            }
        } catch (QueryException $e) {
            // Two pushes at once: the first made the row; apply this one if it is still newer.
            $client = StreamrClient::where('streamrUserId', $id)->first();
            if (! $client) {
                throw $e;
            }
            if ($client->snapshotAt && $at->lte($client->snapshotAt)) {
                return false;
            }
            $client->fill($values)->save();
        }

        return true;
    }

    /** The plan state as of now (the registry can lag Streamr). */
    public static function computedState(StreamrClient $client, ?Carbon $now = null): string
    {
        return $client->computedState($now);
    }

    private function columns(array $s, Carbon $at): array
    {
        $store = is_array($s['store'] ?? null) ? $s['store'] : [];
        $sub = is_array($s['subscription'] ?? null) ? $s['subscription'] : [];
        $credits = is_array($s['credits'] ?? null) ? $s['credits'] : [];
        $totals = is_array($s['totals'] ?? null) ? $s['totals'] : [];
        $usage = is_array($s['usage'] ?? null) ? $s['usage'] : [];
        $support = is_array($s['support'] ?? null) ? $s['support'] : [];
        $notes = is_array($s['notes'] ?? null) ? $s['notes'] : [];

        $state = (string) ($sub['state'] ?? 'none');
        $recent = array_slice(array_values(array_filter((array) ($notes['recent'] ?? []), 'is_array')), 0, 3);

        return [
            'email' => mb_substr(trim((string) $s['email']), 0, 191),
            'firstName' => $this->text($s['first_name'] ?? null, 100),
            'lastName' => $this->text($s['last_name'] ?? null, 100),
            'phone' => $this->text($s['phone'] ?? null, 20),
            'role' => $this->text($s['role'] ?? null, 20) ?? 'client',
            'accountStatus' => $this->text($s['account_status'] ?? null, 20) ?? 'active',
            'storeId' => isset($store['id']) && is_numeric($store['id']) ? (int) $store['id'] : null,
            'storeName' => $this->text($store['name'] ?? null, 150),
            'storeHandle' => $this->text($store['handle'] ?? null, 80),
            'storeUrl' => $this->url($store['url'] ?? null),
            'customDomain' => $this->text($store['custom_domain'] ?? null, 191),
            'planKey' => $this->text($sub['plan_key'] ?? null, 20),
            'planName' => $this->text($sub['plan_name'] ?? null, 60),
            'planPrice' => isset($sub['price']) && is_numeric($sub['price']) ? round((float) $sub['price'], 2) : null,
            'subscriptionState' => in_array($state, ['active', 'grace', 'expired', 'suspended', 'none'], true) ? $state : 'none',
            'startsAt' => StreamrTime::parse($sub['starts_at'] ?? null),
            'expiresAt' => StreamrTime::parse($sub['expires_at'] ?? null),
            'graceUntil' => StreamrTime::parse($sub['grace_until'] ?? null),
            'isLifetime' => (bool) filter_var($sub['is_lifetime'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'planCredits' => $this->int($credits['plan'] ?? 0),
            'purchasedCredits' => $this->int($credits['purchased'] ?? 0),
            'availableCredits' => $this->int($credits['available'] ?? 0),
            'planCycleEndsAt' => StreamrTime::parse($credits['plan_cycle_ends_at'] ?? null),
            'totalPaid' => isset($totals['paid']) && is_numeric($totals['paid']) ? round((float) $totals['paid'], 2) : 0,
            'ordersCount' => max(0, $this->int($totals['orders'] ?? 0)),
            'lastOrderNumber' => $this->text($totals['last_order_number'] ?? null, 50),
            'livesCount' => max(0, $this->int($usage['lives'] ?? 0)),
            'liveMinutes' => max(0, $this->int($usage['live_minutes'] ?? 0)),
            'leadsCount' => max(0, $this->int($usage['leads'] ?? 0)),
            'productsCount' => max(0, $this->int($usage['products'] ?? 0)),
            'storageBytes' => max(0, $this->int($usage['storage_bytes'] ?? 0)),
            'openTickets' => max(0, $this->int($support['open_tickets'] ?? 0)),
            'notesCount' => max(0, $this->int($notes['count'] ?? 0)),
            'recentNotes' => $recent,
            'snapshot' => $s,
            'registeredAt' => StreamrTime::parse($s['registered_at'] ?? null),
            'lastLoginAt' => StreamrTime::parse($s['last_login_at'] ?? null),
            'lastLiveAt' => StreamrTime::parse($s['last_live_at'] ?? null),
            'snapshotAt' => $at,
            'syncedAt' => StreamrTime::now(),
        ];
    }

    private function text($value, int $max): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function url($value): ?string
    {
        $url = $this->text($value, 255);

        return $url && preg_match('#^https?://#i', $url) ? $url : null;
    }

    private function int($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
