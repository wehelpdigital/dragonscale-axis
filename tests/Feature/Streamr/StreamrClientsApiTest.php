<?php

namespace Tests\Feature\Streamr;

use App\Models\StreamrClient;
use Illuminate\Support\Facades\DB;

/**
 * PUT /api/streamr/v1/clients/{streamrUserId}: the registry keeps the newest
 * snapshot (by snapshot_at), mapped to its columns, with the full JSON.
 */
class StreamrClientsApiTest extends StreamrTestCase
{
    public function test_a_snapshot_is_stored_with_its_columns_and_the_full_json(): void
    {
        $snapshot = $this->snapshot();

        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $snapshot)->assertOk()->assertJsonPath('data', ['stored' => true]);

        $client = StreamrClient::firstOrFail();
        $this->assertSame([42, 'lia@example.ph', 'Lia', 'Santos', '09171234567', 'client', 'active'],
            [$client->streamrUserId, $client->email, $client->firstName, $client->lastName, $client->phone, $client->role, $client->accountStatus]);
        $this->assertSame([7, "Lia's Preloved Bags", 'lias-preloved', 'https://lias-preloved.streamr.shop'],
            [$client->storeId, $client->storeName, $client->storeHandle, $client->storeUrl]);
        $this->assertSame(['yearly', 'Yearly', '11000.00', 'active', false], [$client->planKey, $client->planName, $client->planPrice, $client->subscriptionState, $client->isLifetime]);
        $this->assertSame([820, 3450, 4270], [$client->planCredits, $client->purchasedCredits, $client->availableCredits]);
        $this->assertSame(['23000.00', 3, 'STR-20261108-7KQ4'], [$client->totalPaid, $client->ordersCount, $client->lastOrderNumber]);
        $this->assertSame([12, 1440, 530, 210, 5368709120, 1, 3], [$client->livesCount, $client->liveMinutes, $client->leadsCount, $client->productsCount,
            $client->storageBytes, $client->openTickets, $client->notesCount]);
        $this->assertSame('Asked about lifetime.', $client->recentNotes[0]['body']);
        $this->assertSame($snapshot, $client->snapshot);
        $this->assertSame([88], array_column($client->queuedPeriods(), 'id'));
        $this->assertNotNull($client->syncedAt);
        // Manila wall-clock in the columns.
        $this->assertSame(\Illuminate\Support\Carbon::parse($snapshot['subscription']['expires_at'])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s'),
            DB::table('streamr_clients')->value('expiresAt'));
    }

    public function test_an_older_snapshot_changes_nothing(): void
    {
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot())->assertOk();

        $older = $this->snapshot(['first_name' => 'Old', 'snapshot_at' => now('Asia/Manila')->subHour()->toIso8601String()]);
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $older)->assertOk()->assertJsonPath('data', ['stored' => false, 'stale' => true]);
        $this->assertSame('Lia', StreamrClient::firstOrFail()->firstName);

        $newer = $this->snapshot(['first_name' => 'Liana', 'subscription' => null, 'credits' => ['plan' => 0, 'purchased' => 0, 'available' => null, 'unlimited' => true],
            'snapshot_at' => now('Asia/Manila')->addMinute()->toIso8601String()]);
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $newer)->assertOk()->assertJsonPath('data', ['stored' => true]);
        $client = StreamrClient::firstOrFail();
        $this->assertSame(['Liana', 'none', null, 0], [$client->firstName, $client->subscriptionState, $client->planKey, $client->availableCredits]);
        $this->assertSame(1, StreamrClient::count());
    }

    public function test_the_address_and_the_body_must_name_the_same_client(): void
    {
        $this->signedJson('PUT', '/api/streamr/v1/clients/43', $this->snapshot())
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['streamr_user_id']]]);

        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot(['email' => null]))
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')->assertJsonStructure(['error' => ['fields' => ['email']]]);

        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot(['snapshot_at' => null]))->assertStatus(422);
        $this->assertSame(0, StreamrClient::count());
    }

    public function test_a_store_address_that_is_not_http_is_dropped(): void
    {
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot(['store.url' => 'javascript:alert(1)']))->assertOk();

        $this->assertNull(StreamrClient::firstOrFail()->storeUrl);
    }
}
