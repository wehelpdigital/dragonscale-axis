<?php

namespace Tests\Feature\Streamr;

use App\Models\StreamrClient;
use App\Models\StreamrDeskTicket;
use Illuminate\Support\Facades\DB;

/**
 * Every Streamr page and JSON endpoint renders for a signed-in admin with
 * fixtures (a client, a pending and an auto-approved order, a ticket, a
 * failed action), escapes what clients typed, and the client action forms
 * (Edit client details, Change end date, Cancel upcoming period) are there.
 */
class StreamrPagesRenderTest extends StreamrTestCase
{
    private const XSS = '<script>alert("x")</script>';

    private function seedFixtures(): void
    {
        $this->fakeStreamr();
        $this->signedJson('PUT', '/api/streamr/v1/clients/42', $this->snapshot(['first_name' => 'Lia ' . self::XSS, 'store.name' => 'Bags ' . self::XSS]))->assertOk();
        $this->postOrder(['client.first_name' => 'Lia ' . self::XSS])->assertOk();
        $this->postOrder(['uuid' => '2a6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e72', 'number' => 'STR-20261108-8KQ4', 'ai.verdict' => 'pass', 'ai.score' => 95,
            'decision' => ['auto_approved' => true, 'decided_by' => 'ai'], 'kind' => 'credits',
            'item' => ['key' => 'pack:pack_1000', 'sku' => 'streamr:pack:pack_1000', 'product_name' => 'Streamr Credits', 'variant_name' => '1,000 credits pack', 'days' => null, 'credits' => 1000],
            'amounts' => ['currency' => 'PHP', 'subtotal' => '500.00', 'discount' => '0.00', 'fee' => '0.00', 'total' => '500.00'], 'payment.amount_sent' => '500.00'])->assertOk();
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', [
            'uuid' => '6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f', 'number' => 'SR-2026-0007', 'streamr_user_id' => 42,
            'client' => ['name' => 'Lia ' . self::XSS, 'email' => 'lia@example.ph', 'store' => 'Bags'], 'subject' => 'Help ' . self::XSS,
            'category' => 'live', 'priority' => 'urgent', 'status' => 'open', 'created_at' => now('Asia/Manila')->toIso8601String(),
            'message' => ['uuid' => '7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51', 'author_type' => 'client', 'author_name' => 'Lia', 'body' => 'Body ' . self::XSS,
                'body_format' => 'text', 'attachments' => [], 'is_internal' => false, 'created_at' => now('Asia/Manila')->toIso8601String()],
        ])->assertOk();
        DB::table('streamr_client_actions')->insert(['uuid' => 'a0a0a0a0-0000-4000-8000-000000000001', 'streamrUserId' => 42, 'action' => 'extend_days',
            'payload' => json_encode(['days' => 30, 'reason' => 'Goodwill']), 'requestedBy' => 1, 'requestedByName' => 'Test Admin', 'status' => 'failed',
            'httpStatus' => 409, 'error' => 'Lifetime plans have no expiry ' . self::XSS, 'attempts' => 1, 'delete_status' => 'active',
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function assertEscaped($response): void
    {
        $response->assertDontSee(self::XSS, false);
    }

    public function test_every_page_renders_and_escapes(): void
    {
        $this->seedFixtures();
        $admin = $this->admin();
        $client = StreamrClient::firstOrFail();
        $ticket = StreamrDeskTicket::firstOrFail();

        $pages = [
            route('streamr-sales.index') => ['Sales dashboard', 'Active subscribers', 'Recent orders'],
            route('streamr-clients.index') => ['Clients', 'Expiring ≤ 7 days', 'Admins (unlimited)', 'Export CSV'],
            route('streamr-payments.index') => ['Payment reviews', 'Payment settings', 'Auto-approved · last 7 days', 'Name, as GCash prints it'],
            route('streamr-payments.index', ['open' => 'STR-20261108-7KQ4']) => ['STR-20261108-7KQ4'],
            route('streamr-support.index') => ['SR-2026-0007', 'Open'],
            route('streamr-support.index', ['status' => 'all', 'q' => 'Help']) => ['SR-2026-0007'],
            route('streamr-support.index', ['id' => $ticket->id]) => ['SR-2026-0007', 'Internal note (not sent to the client)', 'Close ticket'],
            route('streamr-health.index') => ['Link status', 'STREAMR_API_TOKEN', 'Failed client actions', 'Recent errors', 'streamr.test'],
        ];
        foreach ($pages as $url => $texts) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            foreach ($texts as $text) {
                $response->assertSee($text);
            }
            $this->assertEscaped($response);
            $response->assertDontSee(self::SECRET);
        }

        // The JSON behind the pages.
        foreach ([
            route('streamr-sales.data', ['range' => 'all']),
            route('streamr-clients.data', ['draw' => 1, 'start' => 0, 'length' => 25]),
            route('streamr-clients.show', ['id' => $client->id]),
            route('streamr-payments.data', ['status' => 'all']),
            route('streamr-payments.data', ['status' => 'auto7']),
            route('streamr-payments.one', ['number' => 'STR-20261108-7KQ4']),
        ] as $url) {
            $this->actingAs($admin)->getJson($url)->assertOk();
        }
    }

    public function test_the_client_action_forms_are_on_the_clients_page(): void
    {
        $this->seedFixtures();

        $this->actingAs($this->admin())->get(route('streamr-clients.index'))
            ->assertOk()
            ->assertSee('Edit client details')
            ->assertSee('id="stProfileForm"', false)
            ->assertSee('id="stFieldFirstName"', false)
            ->assertSee('id="stFieldStoreName"', false)
            ->assertSee('Change end date')
            ->assertSee('id="stExpiryForm"', false)
            ->assertSee('type="datetime-local"', false)
            ->assertSee('Cancel upcoming period')
            ->assertSee('id="stQueuedForm"', false)
            ->assertSee('id="stQueuedList"', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('streamr-clients-action', false);
    }

    public function test_pages_say_when_there_is_nothing_yet(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('streamr-clients.index'))->assertOk()
            ->assertSee('No Streamr clients yet — they appear here once Streamr is linked and sends their details.');
        $this->actingAs($admin)->get(route('streamr-support.index'))->assertOk()->assertSee('Tickets appear here once Streamr is linked');
        $this->actingAs($admin)->get(route('streamr-sales.index'))->assertOk();
        $this->actingAs($admin)->getJson(route('streamr-sales.data'))->assertOk()->assertJsonPath('data.kpis.orders', 0);
        $this->actingAs($admin)->get(route('streamr-payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('streamr-health.index'))->assertOk()->assertSee('No errors logged.');
    }

    public function test_pages_say_when_the_tables_are_not_installed(): void
    {
        foreach (array_reverse(\App\Http\Controllers\Streamr\StreamrController::TABLES) as $table) {
            \Illuminate\Support\Facades\Schema::drop($table);
        }
        $admin = $this->admin();

        foreach (['streamr-sales.index', 'streamr-clients.index', 'streamr-payments.index', 'streamr-support.index', 'streamr-health.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk()->assertSee('The Streamr tables are not installed yet (run the deploy migrations).');
        }
        $this->actingAs($admin)->getJson(route('streamr-clients.data'))->assertStatus(503);
        $this->actingAs($admin)->get(route('streamr-proofs.show', ['order' => 'STR-20261108-7KQ4', 'n' => 1]))->assertNotFound();
    }
}
