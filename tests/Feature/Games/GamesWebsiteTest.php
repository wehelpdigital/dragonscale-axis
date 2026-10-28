<?php

namespace Tests\Feature\Games;

use App\Console\Commands\GamesPaymentsCommand;
use App\Models\GamePayment;
use App\Models\GameSetting;
use App\Models\GameSiteGame;
use App\Services\Games\GamePayments;
use App\Services\Games\GamesSiteSettings;
use App\Support\AiKeyCipher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Games > Website: the studio website's API (/api/games-site/v1), the web
 * checkout (a game's order, the GCash receipt, the AI's reading and the code's
 * judgement, approval, the game granting the item), and the admin pages. The
 * game server and the AI are faked; nothing leaves the test.
 *
 * Run with: vendor/bin/phpunit -c phpunit.games.xml
 */
class GamesWebsiteTest extends GamesTestCase
{
    private const SITE = 'test-site-token';

    private const TOKEN = 'Ab3dEf6hIj9kLm2nOp5qRs8tUv1wXy4zAb7cDe0fGh3iJk6l';

    /** What the faked AI says it read on the next receipt. */
    private array $reading = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['games.site.token' => self::SITE, 'anisystem.ai_key_secret' => 'test-ai-secret']);
        Storage::fake('public');

        Schema::create('anisystem_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('claude');
            $table->text('apiKey')->nullable();
            $table->string('model')->nullable();
            $table->text('systemPrompt')->nullable();
            $table->integer('maxOutputTokens')->default(2000);
            $table->decimal('temperature', 4, 2)->default(0.7);
            $table->boolean('isEnabled')->default(true);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
        });
        DB::table('anisystem_ai_settings')->insert(['provider' => 'claude', 'apiKey' => AiKeyCipher::encrypt('sk-test'), 'isEnabled' => 1]);

        GamesSiteSettings::savePay(['gcashNumber' => '09569044144', 'gcashName' => 'ME****O JO*N I* T.']);
        $this->reading = $this->goodReading(295.0);

        Http::fake([
            'salpakan.test/*' => fn ($request) => $this->game->handle($request),
            'api.anthropic.com/*' => fn () => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode($this->reading)]],
                'usage' => ['input_tokens' => 1500, 'output_tokens' => 200],
            ]),
        ]);
        $this->game->checkouts[self::TOKEN] = FakeGameServer::checkout(self::TOKEN);
    }

    private function goodReading(float $amount, array $overrides = []): array
    {
        return array_merge([
            'isReceipt' => true, 'app' => 'gcash', 'kind' => 'express_send', 'status' => 'success',
            'amountToRecipient' => $amount, 'amount' => $amount, 'currency' => 'PHP',
            'recipientName' => 'ME•••O JO•N I• T.', 'recipientNumber' => '+63 956 904 4144', 'recipientBank' => 'GCash',
            'senderName' => 'GABRIELA S.', 'referenceNumber' => '1023 796 539709',
            'dateTime' => now('Asia/Manila')->format('Y-m-d H:i'), 'editingSigns' => [], 'authenticity' => 95, 'notes' => 'A GCash Express Send receipt.',
        ], $overrides);
    }

    private function site(string $method, string $path, array $data = [])
    {
        return $this->withToken(self::SITE)->json($method, '/api/games-site/v1' . $path, $data);
    }

    private function receipt(): UploadedFile
    {
        return UploadedFile::fake()->image('GCash-receipt.png', 720, 1400);
    }

    public function test_the_website_needs_its_token(): void
    {
        $this->getJson('/api/games-site/v1/site')->assertStatus(401);
        $this->withToken('wrong')->getJson('/api/games-site/v1/site')->assertStatus(401);
        config(['games.site.token' => null]);
        $this->withToken(self::SITE)->getJson('/api/games-site/v1/site')->assertStatus(503);
    }

    public function test_the_website_reads_its_settings_and_games(): void
    {
        GamesSiteSettings::saveSite(['siteName' => 'Dragon Scale Games', 'contactEmail' => 'hello@example.com']);
        GameSiteGame::create(['gameKey' => 'hidden_one', 'slug' => 'hidden-one', 'name' => 'Not Yet', 'status' => 'hidden']);

        $this->site('GET', '/site')->assertOk()
            ->assertJsonPath('data.site.siteName', 'Dragon Scale Games')
            ->assertJsonPath('data.site.contactEmail', 'hello@example.com')
            ->assertJsonPath('data.games.0.key', 'salpakan')
            ->assertJsonPath('data.games.0.status', 'live')
            ->assertJsonPath('data.games.0.screenshots.0.url', '/images/games/salpakan/01-match.webp')
            ->assertJsonCount(1, 'data.games')
            ->assertJsonPath('data.payments.gcashFee', 5);
    }

    public function test_a_checkout_link_opens_one_payment_priced_by_the_game(): void
    {
        $this->site('POST', '/checkouts/not-a-real-token!')->assertStatus(404);
        $this->site('POST', '/checkouts/' . str_repeat('x', 40))->assertStatus(404);

        $this->site('POST', '/checkouts/' . self::TOKEN, ['ip' => '203.0.113.9'])->assertOk()
            ->assertJsonPath('data.status', 'awaiting_payment')
            ->assertJsonPath('data.item.name', 'Lava Pit')
            ->assertJsonPath('data.player', 'Gabriela')
            ->assertJsonPath('data.amounts.price', 290)
            ->assertJsonPath('data.amounts.fee', 5)
            ->assertJsonPath('data.amounts.total', 295)
            ->assertJsonPath('data.methods.gcash.ready', true)
            ->assertJsonPath('data.methods.gcash.number', '0956 904 4144')
            ->assertJsonPath('data.methods.paypal.soon', true)
            ->assertJsonPath('data.canUpload', true);

        // Opened again: the same payment, the game asked once.
        $this->site('POST', '/checkouts/' . self::TOKEN)->assertOk();
        $this->assertSame(1, GamePayment::count());
        $this->assertCount(1, $this->game->requestsTo('checkouts/' . self::TOKEN));
        $this->assertSame('203.0.113.9', GamePayment::first()->buyerIp);

        // A link the game no longer takes payment for.
        $old = str_repeat('Q', 48);
        $this->game->checkouts[$old] = FakeGameServer::checkout($old, ['payable' => false]);
        $this->site('POST', '/checkouts/' . $old)->assertStatus(410);
    }

    public function test_a_good_receipt_is_approved_by_the_ai_and_the_game_gives_the_item(): void
    {
        $this->site('POST', '/checkouts/' . self::TOKEN);

        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => $this->receipt(), 'reference' => '1023796539709'], ['Accept' => 'application/json'])
            ->assertOk();

        // The upload's after-response step read it (the scheduler would have, too): asked again, nothing changes.
        $payment = app(GamePayments::class)->check(GamePayment::first());

        $this->assertSame(GamePayment::PAID, $payment->status, json_encode($payment->aiReport));
        $this->assertSame('pass', $payment->aiStatus);
        $this->assertNotNull($payment->fulfilledAt);
        $this->assertSame([['method' => 'gcash', 'reference' => '1023796539709']], $this->game->fulfilled[self::TOKEN]);
        Storage::disk('public')->assertExists($payment->proofPath);

        $this->site('GET', '/checkouts/' . self::TOKEN)->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.canUpload', false)
            ->assertJsonPath('data.returnUrl', 'https://salpakan.test/play/?paid=' . urlencode($payment->orderId));

        // The AI never sees what the payment expects: only the guide and the task.
        $sent = collect(Http::recorded())->first(fn ($pair) => str_contains($pair[0]->url(), 'anthropic'))[0];
        $this->assertStringNotContainsString('295', json_encode($sent->data()['messages']));
    }

    public function test_a_receipt_for_the_wrong_amount_waits_for_a_person(): void
    {
        $this->reading = $this->goodReading(290.0);
        $this->site('POST', '/checkouts/' . self::TOKEN);
        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => $this->receipt()], ['Accept' => 'application/json'])->assertOk();

        $payment = app(GamePayments::class)->check(GamePayment::first());
        $this->assertSame(GamePayment::REVIEW, $payment->status);
        $this->assertSame('fail', $payment->aiStatus);
        $this->assertArrayNotHasKey(self::TOKEN, $this->game->fulfilled);

        $view = $this->site('GET', '/checkouts/' . self::TOKEN)->assertOk()->json('data');
        $this->assertTrue($view['canUpload'], 'the wrong picture can be replaced before a person looks');
        $this->assertSame('The amount is ₱295.00', $view['check']['problems'][0]['label'] ?? null);

        // A person approves it after checking GCash: the game gives the item.
        $this->actingAs($this->admin())->post(route('games-payments.approve'), ['id' => $payment->id])->assertOk()->assertJsonPath('status', 'paid');
        $this->assertCount(1, $this->game->fulfilled[self::TOKEN]);
    }

    public function test_a_reference_used_before_is_refused_and_a_person_can_reject(): void
    {
        $this->site('POST', '/checkouts/' . self::TOKEN);
        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => $this->receipt()], ['Accept' => 'application/json']);
        app(GamePayments::class)->check(GamePayment::first());
        $this->assertSame(GamePayment::PAID, GamePayment::first()->status);

        // A second order paid with the same receipt (reference) again.
        $second = str_repeat('B', 48);
        $this->game->checkouts[$second] = FakeGameServer::checkout($second);
        $this->site('POST', '/checkouts/' . $second);
        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . $second . '/gcash', ['proof' => $this->receipt()], ['Accept' => 'application/json']);
        $payment = app(GamePayments::class)->check(GamePayment::where('checkoutToken', $second)->first());
        $this->assertSame(GamePayment::REVIEW, $payment->status);
        $ref = collect($payment->aiReport['checks'])->firstWhere('key', 'ref');
        $this->assertFalse($ref['ok']);

        $this->actingAs($this->admin())->post(route('games-payments.reject'), ['id' => $payment->id, 'note' => ''])->assertStatus(422);
        $this->actingAs($this->admin())->post(route('games-payments.reject'), ['id' => $payment->id, 'note' => 'This receipt was already used for another purchase.'])->assertOk();
        $this->site('GET', '/checkouts/' . $second)->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.note', 'This receipt was already used for another purchase.')
            ->assertJsonPath('data.canUpload', false);
    }

    public function test_uploads_are_pictures_and_limited(): void
    {
        $this->site('POST', '/checkouts/' . self::TOKEN);
        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => UploadedFile::fake()->create('receipt.pdf', 40, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->reading = $this->goodReading(1.0);
        for ($i = 0; $i < GamePayment::MAX_UPLOADS; $i++) {
            $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => $this->receipt()], ['Accept' => 'application/json'])->assertOk();
            app(GamePayments::class)->check(GamePayment::first());
        }
        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => $this->receipt()], ['Accept' => 'application/json'])
            ->assertStatus(409);
    }

    public function test_the_scheduler_delivers_approvals_the_game_missed_and_expires_unpaid_links(): void
    {
        $this->site('POST', '/checkouts/' . self::TOKEN);
        $this->game->failures['checkouts/' . self::TOKEN . '/fulfill'] = 503;
        $this->withToken(self::SITE)->post('/api/games-site/v1/checkouts/' . self::TOKEN . '/gcash', ['proof' => $this->receipt()], ['Accept' => 'application/json']);

        $payment = app(GamePayments::class)->check(GamePayment::first());
        $this->assertSame(GamePayment::APPROVED, $payment->status);
        $this->assertNotNull($payment->fulfillError);

        unset($this->game->failures['checkouts/' . self::TOKEN . '/fulfill']);
        $this->artisan(GamesPaymentsCommand::class)->assertSuccessful();
        $this->assertSame(GamePayment::PAID, GamePayment::first()->status);

        $stale = str_repeat('C', 48);
        $this->game->checkouts[$stale] = FakeGameServer::checkout($stale);
        $this->site('POST', '/checkouts/' . $stale);
        GamePayment::where('checkoutToken', $stale)->update(['expiresAt' => now()->subMinute()]);
        $this->artisan(GamesPaymentsCommand::class)->assertSuccessful();
        $this->assertSame(GamePayment::EXPIRED, GamePayment::where('checkoutToken', $stale)->first()->status);
    }

    public function test_the_admin_pages_open_and_the_rate_reaches_the_game(): void
    {
        $admin = $this->admin();
        $this->site('POST', '/checkouts/' . self::TOKEN);

        $this->actingAs($admin)->get(route('games-site.index'))->assertOk()->assertSee('Salpakan Tactics!');
        $this->actingAs($admin)->get(route('games-site.edit', ['id' => GameSiteGame::first()->id]))->assertOk()->assertSee('salpakan-tactics');
        $this->actingAs($admin)->get(route('games-payments.index'))->assertOk()->assertSee('Lava Pit');
        $this->actingAs($admin)->getJson(route('games-payments.show', ['id' => GamePayment::first()->id]))->assertOk()->assertJsonPath('payment.total', 295);

        $this->actingAs($admin)->post(route('games-payments.settings'), [
            'gcashNumber' => '0917 123 4567', 'gcashName' => 'JU*N DE* C.', 'gcashFee' => 7, 'reviewHours' => 12, 'aiCheck' => 1,
        ])->assertRedirect(route('games-payments.index'));
        $this->assertSame('09171234567', GamesSiteSettings::pay()['gcashNumber']);
        $this->assertFalse(GamesSiteSettings::pay()['aiAutoApprove']);

        $this->actingAs($admin)->post(route('games-site.save'), [
            'id' => GameSiteGame::first()->id, 'name' => 'Salpakan Tactics!', 'gameKey' => 'salpakan', 'slug' => 'salpakan-tactics',
            'status' => 'live', 'tagline' => 'A new line', 'platforms' => [['key' => 'web', 'status' => 'live', 'url' => '']],
            'features' => [['title' => 'One', 'text' => 'Two']], 'screenshots' => [['url' => '/images/games/salpakan/01-match.webp', 'caption' => 'Kept']],
        ])->assertRedirect(route('games-site.index'));
        $this->assertSame('A new line', GameSiteGame::first()->tagline);

        $this->actingAs($admin)->postJson(route('games-salpakan-settings.rate'), ['usdToPhpRate' => 59.5])->assertOk();
        $this->assertSame(['php_per_usd' => 59.5], $this->game->settingsPut);
        $this->assertSame('59.5', GameSetting::getValue('salpakan', 'usdToPhpRate'));
    }
}
