<?php

namespace Tests\Feature\Streamr;

use App\Models\StreamrClient;
use App\Models\StreamrClientAction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Streamr > Clients: the DataTable filters (incl. a plan whose end passed
 * before Streamr's next push), the details JSON, actions sent to Streamr
 * signed (applied / refused / not reached), Retry and Refresh, the CSV.
 */
class StreamrClientsPageTest extends StreamrTestCase
{
    private function client(int $id, array $over = []): void
    {
        $this->signedJson('PUT', '/api/streamr/v1/clients/' . $id, $this->snapshot(array_merge(['streamr_user_id' => $id], $over)))->assertOk();
    }

    private function rows(array $filters): array
    {
        return array_column($this->actingAs($this->admin())->getJson(route('streamr-clients.data', array_merge(['draw' => 1, 'start' => 0, 'length' => 50], $filters)))
            ->assertOk()->json('data'), 'streamrUserId');
    }

    private function seedClients(): void
    {
        $this->client(1, ['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana@example.ph', 'store.handle' => 'ana-shop', 'store.name' => 'Ana Shop',
            'subscription.plan_key' => 'monthly', 'subscription.expires_at' => now('Asia/Manila')->addDays(5)->toIso8601String()]);
        // Streamr still says "active", but the end has passed: shown as expired.
        $this->client(2, ['first_name' => 'Ben', 'email' => 'ben@example.ph', 'store.handle' => 'ben', 'subscription.plan_key' => 'monthly',
            'subscription.expires_at' => now('Asia/Manila')->subDay()->toIso8601String()]);
        $this->client(3, ['first_name' => 'Cara', 'email' => 'cara@example.ph', 'store.handle' => 'cara', 'subscription.plan_key' => 'lifetime',
            'subscription.is_lifetime' => true, 'subscription.expires_at' => null, 'subscription.price' => '35000.00']);
        $this->client(4, ['first_name' => 'Dan', 'email' => 'dan@example.ph', 'store.handle' => 'dan', 'role' => 'admin', 'subscription' => null,
            'account_status' => 'active']);
        $this->client(5, ['first_name' => 'Eve', 'email' => 'eve@example.ph', 'store.handle' => 'eve', 'account_status' => 'suspended',
            'subscription.state' => 'suspended', 'registered_at' => '2026-01-15T10:00:00+08:00']);
    }

    public function test_the_table_filters(): void
    {
        $this->seedClients();

        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5], $this->rows([]));
        $this->assertSame([1], $this->rows(['searchFilter' => 'ana-shop']));
        $this->assertSame([1], $this->rows(['searchFilter' => 'Ana Cruz']));
        $this->assertSame([2], $this->rows(['searchFilter' => 'BEN@example']));
        $this->assertEqualsCanonicalizing([1, 2], $this->rows(['plan' => 'monthly']));
        $this->assertSame([4], $this->rows(['plan' => 'none']));
        $this->assertEqualsCanonicalizing([1, 3], $this->rows(['state' => 'active']));
        $this->assertSame([2], $this->rows(['state' => 'expired']));
        $this->assertSame([5], $this->rows(['state' => 'suspended']));
        $this->assertSame([1], $this->rows(['expiringDays' => 7]));
        $this->assertSame([4], $this->rows(['role' => 'admin']));
        $this->assertEqualsCanonicalizing([1, 2, 3, 5], $this->rows(['role' => 'client']));
        $this->assertSame([5], $this->rows(['account' => 'suspended']));
        $this->assertSame([5], $this->rows(['registeredFrom' => '2026-01-01', 'registeredTo' => '2026-01-31']));

        $row = collect($this->actingAs($this->admin())->getJson(route('streamr-clients.data', ['draw' => 1, 'start' => 0, 'length' => 50]))->json('data'))
            ->firstWhere('streamrUserId', 2);
        $this->assertSame(['expired', 'Expired', 'bg-secondary'], [$row['stateKey'], $row['stateLabel'], $row['stateBadge']]);
        $admin = collect($this->actingAs($this->admin())->getJson(route('streamr-clients.data', ['draw' => 1, 'start' => 0, 'length' => 50]))->json('data'))
            ->firstWhere('streamrUserId', 4);
        $this->assertTrue($admin['isAdmin']);
    }

    public function test_the_details_json(): void
    {
        $this->client(42);
        $this->postOrder()->assertOk();
        $client = StreamrClient::firstOrFail();

        $data = $this->actingAs($this->admin())->getJson(route('streamr-clients.show', ['id' => $client->id]))->assertOk()->json('data');

        $this->assertSame(['Lia Santos', 'lia@example.ph', 42], [$data['name'], $data['email'], $data['streamrUserId']]);
        $this->assertSame(['active', 'Active', 'bg-success'], [$data['subscription']['state']['key'], $data['subscription']['state']['label'], $data['subscription']['state']['badge']]);
        $this->assertSame([88], array_column($data['subscription']['queued'], 'id'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $data['subscription']['expiresAtInput']);
        $this->assertSame(4270, $data['credits']['available']);
        $this->assertSame(['STR-20261108-7KQ4'], array_column($data['orders'], 'number'));
        $this->assertSame(['pending', 'pending'], [$data['orders'][0]['orderStatus'], $data['orders'][0]['paymentStatus']]);
        $this->assertSame(self::STREAMR . '/admin/customers/42', $data['links']['streamr']);

        $this->actingAs($this->admin())->getJson(route('streamr-clients.show', ['id' => 999]))->assertNotFound();
    }

    public function test_an_action_is_sent_signed_and_streamrs_snapshot_is_kept(): void
    {
        $this->client(42);
        $client = StreamrClient::firstOrFail();
        $fresh = $this->snapshot(['subscription.expires_at' => now('Asia/Manila')->addMonths(12)->toIso8601String(), 'snapshot_at' => now('Asia/Manila')->addSecond()->toIso8601String()]);
        $this->fakeStreamr(['/clients/42/actions' => fn ($request) => Http::response(['success' => true, 'data' => [
            'action_uuid' => $request->data()['uuid'], 'status' => 'applied', 'message' => 'Extended by 30 days — now ends Dec 8, 2027.', 'client' => $fresh,
        ]])]);
        $admin = $this->admin('Jun Reyes', 'jun@example.test');

        $this->actingAs($admin)->postJson(route('streamr-clients.action', ['id' => $client->id, 'action' => 'extend_days']), ['days' => 30, 'reason' => 'Goodwill for the outage'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('message', 'Extended by 30 days — now ends Dec 8, 2027.')
            ->assertJsonPath('data.client.streamrUserId', 42);

        $calls = $this->streamrCalls();
        $this->assertCount(1, $calls);
        [$method, $path, $body, $request] = $calls[0];
        $this->assertSame(['POST', '/api/mother/clients/42/actions'], [$method, $path]);
        $this->assertSame('extend_days', $body['action']);
        $this->assertSame(['days' => 30, 'reason' => 'Goodwill for the outage'], $body['payload']);
        $this->assertSame(['id' => $admin->id, 'name' => 'Jun Reyes'], $body['admin']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $body['uuid']);
        $this->assertStringEndsWith('+08:00', $body['requested_at']);
        $this->assertSame($body['uuid'], $request->header('Idempotency-Key')[0]);
        $this->assertSignedForStreamr($request);

        $log = StreamrClientAction::firstOrFail();
        $this->assertSame([$body['uuid'], 'applied', 200, 'Jun Reyes'], [$log->uuid, $log->status, $log->httpStatus, $log->requestedByName]);
        $this->assertSame('Extended by 30 days — now ends Dec 8, 2027.', $log->result['message']);
        $this->assertSame(now('Asia/Manila')->addMonths(12)->format('Y-m-d'), StreamrClient::firstOrFail()->expiresAt->format('Y-m-d'));
    }

    public function test_a_refusal_shows_streamrs_message_and_is_logged_failed(): void
    {
        $this->client(42);
        $client = StreamrClient::firstOrFail();
        $this->fakeStreamr(['/clients/42/actions' => Http::response(['success' => false, 'error' => ['code' => 'not_applicable', 'message' => 'Lifetime plans have no expiry to extend.']], 409)]);

        $this->actingAs($this->admin())->postJson(route('streamr-clients.action', ['id' => $client->id, 'action' => 'extend_days']), ['days' => 30, 'reason' => 'Goodwill'])
            ->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonPath('message', "Streamr couldn't apply it: Lifetime plans have no expiry to extend.");

        $log = StreamrClientAction::firstOrFail();
        $this->assertSame(['failed', 409, 'Lifetime plans have no expiry to extend.'], [$log->status, $log->httpStatus, $log->error]);
    }

    public function test_streamr_unreachable_is_502_and_retry_sends_a_new_action(): void
    {
        $this->client(42);
        $client = StreamrClient::firstOrFail();
        Http::fake(['streamr.test/*' => fn () => throw new ConnectionException('Connection timed out')]);

        $this->actingAs($this->admin())->postJson(route('streamr-clients.action', ['id' => $client->id, 'action' => 'send_login_link']))
            ->assertStatus(502)->assertJsonPath('message', 'Streamr is not reachable — try again.');
        $failed = StreamrClientAction::firstOrFail();
        $this->assertSame(['failed', null], [$failed->status, $failed->httpStatus]);

        // Streamr is back: Retry sends the same action under a new uuid, linked to the failed one.
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        $this->fakeStreamr(['/clients/42/actions' => fn ($request) => Http::response(['success' => true, 'data' => [
            'action_uuid' => $request->data()['uuid'], 'status' => 'applied', 'message' => 'Login link sent.', 'client' => $this->snapshot(['snapshot_at' => now('Asia/Manila')->addMinute()->toIso8601String()]),
        ]])]);
        $this->actingAs($this->admin())->postJson(route('streamr-health.retry', ['id' => $failed->id]))->assertOk()->assertJsonPath('message', 'Login link sent.');

        $retry = StreamrClientAction::orderByDesc('id')->firstOrFail();
        $this->assertNotSame($failed->uuid, $retry->uuid);
        $this->assertSame(['applied', 'send_login_link', $failed->uuid], [$retry->status, $retry->action, $retry->payload['retry_of']]);
        $sent = collect($this->streamrCalls())->last();
        $this->assertSame($retry->uuid, $sent[2]['uuid']);
        $this->assertArrayNotHasKey('retry_of', (array) $sent[2]['payload']);

        // Only failed actions can be sent again.
        $this->actingAs($this->admin())->postJson(route('streamr-health.retry', ['id' => $retry->id]))->assertStatus(422);
    }

    public function test_actions_are_checked_here_before_streamr_is_called(): void
    {
        $this->client(42);
        $client = StreamrClient::firstOrFail();
        Http::fake();
        $url = fn (string $action) => route('streamr-clients.action', ['id' => $client->id, 'action' => $action]);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson($url('extend_days'), ['days' => 0, 'reason' => 'x'])->assertStatus(422);
        $this->actingAs($admin)->postJson($url('suspend_subscription'), [])->assertStatus(422)->assertJsonPath('message', 'Write the reason (Streamr keeps it with the change).');
        $this->actingAs($admin)->postJson($url('adjust_credits'), ['delta' => 0, 'bucket' => 'purchased', 'reason' => 'Promo'])->assertStatus(422);
        $this->actingAs($admin)->postJson($url('change_plan'), ['plan_key' => 'weekly', 'reason' => 'Upgrade'])->assertStatus(422);
        $this->actingAs($admin)->postJson($url('change_email'), ['email' => 'nope'])->assertStatus(422);
        $this->actingAs($admin)->postJson($url('add_note'), ['body' => ''])->assertStatus(422);
        $this->actingAs($admin)->postJson($url('make_coffee'), [])->assertStatus(422);
        $this->actingAs($admin)->postJson(route('streamr-clients.action', ['id' => 999, 'action' => 'resync']))->assertNotFound();

        Http::assertNothingSent();
        $this->assertSame(0, StreamrClientAction::count());
    }

    public function test_not_linked_is_said_plainly(): void
    {
        $this->client(42);
        config(['streamr.app_url' => '']);

        $this->actingAs($this->admin())->postJson(route('streamr-clients.action', ['id' => StreamrClient::firstOrFail()->id, 'action' => 'resync']))
            ->assertStatus(503)->assertJsonPath('message', 'Streamr is not linked yet: set STREAMR_APP_URL and STREAMR_API_TOKEN.');
    }

    public function test_refresh_reads_the_snapshot_from_streamr(): void
    {
        $this->client(42);
        $client = StreamrClient::firstOrFail();
        $this->fakeStreamr(['/clients/42' => Http::response(['success' => true, 'data' => $this->snapshot(['first_name' => 'Liza', 'snapshot_at' => now('Asia/Manila')->addMinute()->toIso8601String()])])]);

        $this->actingAs($this->admin())->postJson(route('streamr-clients.refresh', ['id' => $client->id]))
            ->assertOk()->assertJsonPath('message', 'Refreshed from Streamr.')->assertJsonPath('data.client.firstName', 'Liza');

        [$method, $path, , $request] = $this->streamrCalls()[0];
        $this->assertSame(['GET', '/api/mother/clients/42'], [$method, $path]);
        $this->assertSignedForStreamr($request);
    }

    public function test_the_csv_export_follows_the_filters(): void
    {
        $this->seedClients();

        $response = $this->actingAs($this->admin())->get(route('streamr-clients.export', ['plan' => 'monthly']))->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = array_values(array_filter(explode("\n", trim(substr($csv, 3)))));
        $this->assertCount(3, $lines);   // header + Ana + Ben
        $this->assertStringContainsString('ana@example.ph', $csv);
        $this->assertStringContainsString('expired', $csv);   // Ben, as the page shows him
        $this->assertStringNotContainsString('cara@example.ph', $csv);
    }
}
