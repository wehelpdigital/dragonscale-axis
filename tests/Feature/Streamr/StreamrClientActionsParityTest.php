<?php

namespace Tests\Feature\Streamr;

use App\Models\StreamrClient;
use App\Models\StreamrClientAction;
use Illuminate\Support\Facades\Http;

/**
 * The three client actions added for parity with Streamr's own admin
 * (Edit client details, Change end date, Cancel upcoming period): what the
 * mother sends Streamr for each, and what it refuses before sending.
 */
class StreamrClientActionsParityTest extends StreamrTestCase
{
    private StreamrClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot())->assertOk();
        $this->client = StreamrClient::firstOrFail();
        $this->fakeStreamr(['/clients/42/actions' => fn ($request) => Http::response(['success' => true, 'data' => [
            'action_uuid' => $request->data()['uuid'], 'status' => 'applied', 'message' => 'Done in Streamr.',
            'client' => $this->snapshot(['snapshot_at' => now('Asia/Manila')->addMinute()->toIso8601String()]),
        ]])]);
    }

    private function act(string $action, array $data)
    {
        return $this->actingAs($this->admin())->postJson(route('streamr-clients.action', ['id' => $this->client->id, 'action' => $action]), $data);
    }

    private function lastPayload(): array
    {
        return (array) collect($this->streamrCalls())->last()[2]['payload'];
    }

    public function test_edit_client_details_sends_only_what_was_filled(): void
    {
        $this->act('update_profile', ['first_name' => 'Liza', 'last_name' => '', 'phone' => '0917 765 4321', 'store_name' => 'Liza Bags'])
            ->assertOk()->assertJsonPath('message', 'Done in Streamr.');

        $this->assertSame(['first_name' => 'Liza', 'phone' => '09177654321', 'store_name' => 'Liza Bags'], $this->lastPayload());
        $this->assertSame('update_profile', StreamrClientAction::firstOrFail()->action);

        $this->act('update_profile', ['phone' => '12345'])->assertStatus(422)->assertJsonPath('message', 'The mobile number looks like 09XXXXXXXXX.');
        $this->act('update_profile', ['store_name' => 'X'])->assertStatus(422);
        $this->act('update_profile', [])->assertStatus(422)->assertJsonPath('message', 'Change at least one of the details.');
        $this->assertCount(1, $this->streamrCalls());
    }

    public function test_change_end_date_sends_manila_time_with_its_offset(): void
    {
        $at = now('Asia/Manila')->addDays(40)->setTime(14, 30);

        $this->act('set_expiry', ['expires_at' => $at->format('Y-m-d\TH:i'), 'reason' => 'Agreed extension'])->assertOk();

        $this->assertSame(['expires_at' => $at->format('Y-m-d\TH:i:sP'), 'reason' => 'Agreed extension'], $this->lastPayload());
        $this->assertStringEndsWith('+08:00', $this->lastPayload()['expires_at']);

        $this->act('set_expiry', ['expires_at' => now('Asia/Manila')->addMinutes(10)->format('Y-m-d\TH:i'), 'reason' => 'Too soon'])
            ->assertStatus(422)->assertJsonPath('message', 'The new end date must be at least an hour from now and within 10 years.');
        $this->act('set_expiry', ['expires_at' => 'next week', 'reason' => 'Bad date'])->assertStatus(422);
        $this->assertCount(1, $this->streamrCalls());
    }

    public function test_change_end_date_is_refused_for_lifetime(): void
    {
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot(['subscription.is_lifetime' => true, 'subscription.plan_key' => 'lifetime',
            'snapshot_at' => now('Asia/Manila')->addMinutes(5)->toIso8601String()]))->assertOk();

        $this->act('set_expiry', ['expires_at' => now('Asia/Manila')->addDays(40)->format('Y-m-d\TH:i'), 'reason' => 'x x x'])
            ->assertStatus(422)->assertJsonPath('message', 'A lifetime plan has no end date to change.');
        $this->assertCount(0, $this->streamrCalls());
    }

    public function test_cancel_upcoming_period_names_a_queued_period(): void
    {
        $this->act('cancel_queued', ['subscription_id' => 88, 'reason' => 'Client asked'])->assertOk();
        $this->assertSame(['subscription_id' => 88, 'reason' => 'Client asked'], $this->lastPayload());

        $this->act('cancel_queued', ['subscription_id' => 77, 'reason' => 'Client asked'])
            ->assertStatus(422)->assertJsonPath('message', "That upcoming period is not on this client's plan (refresh the client from Streamr).");
        $this->assertCount(1, $this->streamrCalls());
    }

    public function test_the_other_actions_send_the_catalogue_payloads(): void
    {
        $this->act('change_plan', ['plan_key' => 'lifetime', 'keep_expiry' => 1, 'reason' => 'Upgrade promo'])->assertOk();
        $this->assertSame(['plan_key' => 'lifetime', 'keep_expiry' => true, 'reason' => 'Upgrade promo'], $this->lastPayload());

        $this->act('adjust_credits', ['delta' => -150, 'bucket' => 'plan', 'reason' => 'Refund of a failed live'])->assertOk();
        $this->assertSame(['delta' => -150, 'bucket' => 'plan', 'reason' => 'Refund of a failed live'], $this->lastPayload());

        $this->act('change_email', ['email' => 'Lia.New@Example.PH'])->assertOk();
        $this->assertSame(['email' => 'lia.new@example.ph'], $this->lastPayload());

        $this->act('add_note', ['body' => 'Called her about the outage.'])->assertOk();
        $this->assertSame(['body' => 'Called her about the outage.'], $this->lastPayload());

        foreach (['unsuspend_subscription', 'restore_account', 'send_login_link', 'resync'] as $action) {
            $this->act($action, [])->assertOk();
            $this->assertSame([], $this->lastPayload());
        }
        foreach (['suspend_subscription', 'revoke_subscription', 'suspend_account'] as $action) {
            $this->act($action, ['reason' => 'Chargeback'])->assertOk();
            $this->assertSame(['reason' => 'Chargeback'], $this->lastPayload());
        }

        $this->assertSame(11, StreamrClientAction::where('status', 'applied')->count());
    }
}
