<?php

namespace Tests\Feature\Streamr;

use App\Models\User;
use App\Services\Streamr\StreamrSignature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * Base for the Streamr module's tests (a copy of GamesTestCase's harness,
 * not a subclass).
 *
 * .env points at the PRODUCTION database. These tests make sure they can never
 * reach it, whichever runner started them: the app's database settings are
 * replaced with an in-memory SQLite database before anything can connect, the
 * MySQL connection is aimed at an address that does not exist, and setUp
 * refuses to go on unless the connection really is SQLite in memory. Only the
 * tables these tests need are made: this module's own migration, the real
 * ecom_order_payments and games-site migrations, plus the e-commerce, CRM and
 * users tables built here to mirror production. Streamr is faked with
 * Http::fake (streamr.test); any other request fails the test.
 *
 * Run with: vendor/bin/phpunit -c phpunit.streamr.xml
 */
abstract class StreamrTestCase extends TestCase
{
    public const SECRET = 'test-secret';

    public const STREAMR = 'https://streamr.test';

    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $config->set('database.default', 'sqlite');
        $config->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'url' => null,
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        foreach (['mysql', 'mariadb', 'pgsql', 'sqlsrv'] as $connection) {
            if ($config->has('database.connections.' . $connection)) {
                $config->set('database.connections.' . $connection . '.url', null);
                $config->set('database.connections.' . $connection . '.host', 'no-database.invalid');
                $config->set('database.connections.' . $connection . '.port', '1');
                $config->set('database.connections.' . $connection . '.username', 'nobody');
                $config->set('database.connections.' . $connection . '.password', 'none');
            }
        }
        $config->set('session.driver', 'array');
        $config->set('cache.default', 'array');
        $config->set('queue.default', 'sync');
        $config->set('mail.default', 'array');
        // Never a bucket: the public disk is a local folder (and faked where a test writes to it).
        $config->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/public'),
            'url' => 'http://localhost/storage',
            'visibility' => 'public',
            'throw' => false,
        ]);

        // Streamr, as the fakes below answer it.
        $config->set('streamr.app_url', self::STREAMR);
        $config->set('streamr.api_token', self::SECRET);
        $config->set('streamr.store_name', 'Streamr');
        $config->set('streamr.store_id', null);

        $app['db']->purge();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Streamr tests only run on an in-memory SQLite database. Refusing to touch '
                . $connection->getDriverName() . ':' . $connection->getDatabaseName() . '.');
        }

        Http::preventStrayRequests();
        $this->createTables();
    }

    protected function admin(string $name = 'Test Admin', string $email = 'admin@example.test'): User
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $existing;
        }

        $user = new User();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret-password'),
            'delete_status' => 'active',
            'allow_multiple_logins' => true,
        ])->save();

        return $user;
    }

    // ------------------------------------------------------------------
    // Signed calls into /api/streamr/v1 (as Streamr makes them)
    // ------------------------------------------------------------------

    protected function signedHeaders(string $method, string $path, string $body = '', array $query = [], ?int $timestamp = null, string $secret = self::SECRET): array
    {
        $timestamp ??= time();
        $method = strtoupper($method);
        $canonical = in_array($method, ['GET', 'DELETE'], true) ? StreamrSignature::requestLine($method, $path, $query) : $body;

        return [
            'Authorization' => 'Bearer ' . $secret,
            'X-Streamr-Timestamp' => (string) $timestamp,
            'X-Streamr-Signature' => StreamrSignature::sign($secret, $timestamp, $canonical),
            'X-Streamr-Request-Id' => 'b7d1c1a4-5a1e-4f0e-9a51-7a3f2f0b1c2d',
            'Accept' => 'application/json',
        ];
    }

    protected function signedJson(string $method, string $uri, array $body, array $headers = [], ?string $raw = null): TestResponse
    {
        $json = $raw ?? (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $server = $this->transformHeadersToServerVars(array_merge($this->signedHeaders($method, $uri, $json), [
            'Content-Type' => 'application/json',
            'Idempotency-Key' => '5f0c2c1e-4a8e-4d6b-9e57-3f0a6b2c9d11',
        ], $headers));

        return $this->call(strtoupper($method), $uri, [], [], [], $server, $json);
    }

    protected function signedGet(string $path, array $query = [], array $headers = [], ?int $timestamp = null): TestResponse
    {
        $uri = $path . ($query === [] ? '' : '?' . StreamrSignature::query($query));
        $server = $this->transformHeadersToServerVars(array_merge($this->signedHeaders('GET', $path, '', $query, $timestamp), $headers));

        return $this->call('GET', $uri, [], [], [], $server);
    }

    protected function signedMultipart(string $uri, array $payload, ?UploadedFile $file = null, array $headers = []): TestResponse
    {
        $json = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $server = $this->transformHeadersToServerVars(array_merge($this->signedHeaders('POST', $uri, $json), [
            'Content-Type' => 'multipart/form-data; boundary=----streamrtest',
            'Idempotency-Key' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ], $headers));

        return $this->call('POST', $uri, ['payload' => $json], [], $file ? ['proof' => $file] : [], $server);
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    /** A real JPEG (so finfo says image/jpeg). */
    protected function receiptBytes(string $text = 'GCash receipt', int $width = 120, int $height = 200): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 0, 90, 230));
        imagestring($image, 3, 6, 20, mb_substr($text, 0, 16), imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagejpeg($image, null, 85);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    protected function pngBytes(int $size = 64): string
    {
        $image = imagecreatetruecolor($size, $size);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagefilledrectangle($image, 8, 8, $size - 8, $size - 8, imagecolorallocate($image, 0, 0, 0));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    protected function upload(string $bytes, string $name = 'receipt.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    /** A valid POST /orders payload (architecture §10.2), changed by $over (dot keys allowed). */
    protected function orderPayload(array $over = [], ?string $proofBytes = null): array
    {
        $payload = [
            'uuid' => '0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77',
            'number' => 'STR-20261108-7KQ4',
            'kind' => 'plan',
            'item' => ['key' => 'plan:yearly', 'sku' => 'streamr:plan:yearly', 'product_name' => 'Streamr Subscription',
                'variant_name' => 'Yearly (365 days)', 'days' => 365, 'credits' => 0],
            'amounts' => ['currency' => 'PHP', 'subtotal' => '11000.00', 'discount' => '0.00', 'fee' => '0.00', 'total' => '11000.00'],
            'client' => ['streamr_user_id' => 42, 'first_name' => 'Lia', 'last_name' => 'Santos', 'email' => 'lia@example.ph',
                'phone' => '09171234567', 'store_name' => "Lia's Preloved Bags", 'store_handle' => 'lias-preloved'],
            'payment' => ['method' => 'manual_gcash', 'payer_name' => 'Lia Santos', 'amount_sent' => '11000.00',
                'reference' => '1023 796 539709', 'ref_key' => '1023796539709', 'sender_phone' => '09171234567',
                'buyer_note' => null, 'submitted_at' => '2026-11-08T14:03:00+08:00',
                'proof' => null],
            'ai' => ['verdict' => 'fail', 'score' => 62, 'summary' => 'Something on the receipt does not match this payment.',
                'checks' => [
                    ['key' => 'amount', 'label' => 'The amount is ₱11,000.00', 'ok' => false, 'hard' => true, 'detail' => 'The receipt shows ₱1,100.00.'],
                    ['key' => 'time', 'label' => 'Paid after the checkout was opened', 'ok' => null, 'hard' => true, 'detail' => 'No date and time could be read.'],
                    ['key' => 'recipient', 'label' => 'Sent to our account (GCash 09XX XXX XXXX)', 'ok' => true, 'hard' => true, 'detail' => 'Read: J•••N D•••Z, +63 9XX •••.'],
                ],
                'provider' => 'anthropic', 'model' => 'claude-opus-5-5', 'checked_at' => '2026-11-08T14:03:41+08:00'],
            'decision' => ['auto_approved' => false],
            'notes' => 'Streamr subscription checkout. Plan: Yearly (365 days). Streamr account #42 (lias-preloved). Streamr order 0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77',
        ];
        if ($proofBytes !== null) {
            $payload['payment']['proof'] = ['filename' => 'receipt.jpg', 'mime' => 'image/jpeg', 'bytes' => strlen($proofBytes),
                'sha256' => hash('sha256', $proofBytes)];
        }
        foreach ($over as $key => $value) {
            data_set($payload, $key, $value);
        }

        return $payload;
    }

    /** POST /orders with a receipt; returns the response. */
    protected function postOrder(array $over = [], ?string $bytes = null, bool $withFile = true): TestResponse
    {
        $bytes ??= $this->receiptBytes();

        return $this->signedMultipart('/api/streamr/v1/orders', $this->orderPayload($over, $bytes), $withFile ? $this->upload($bytes) : null);
    }

    /** A client snapshot (architecture §10.2). */
    protected function snapshot(array $over = []): array
    {
        $snapshot = [
            'streamr_user_id' => 42, 'email' => 'lia@example.ph', 'first_name' => 'Lia', 'last_name' => 'Santos', 'phone' => '09171234567',
            'role' => 'client', 'account_status' => 'active',
            'store' => ['id' => 7, 'name' => "Lia's Preloved Bags", 'handle' => 'lias-preloved', 'url' => 'https://lias-preloved.streamr.shop', 'custom_domain' => null],
            'subscription' => ['plan_key' => 'yearly', 'plan_name' => 'Yearly', 'price' => '11000.00', 'state' => 'active',
                'starts_at' => now('Asia/Manila')->subMonth()->toIso8601String(), 'expires_at' => now('Asia/Manila')->addMonths(11)->toIso8601String(),
                'is_lifetime' => false, 'grace_until' => null,
                'queued' => [['id' => 88, 'plan_key' => 'monthly', 'starts_at' => now('Asia/Manila')->addMonths(11)->toIso8601String(),
                    'expires_at' => now('Asia/Manila')->addMonths(12)->toIso8601String()]]],
            'credits' => ['plan' => 820, 'purchased' => 3450, 'available' => 4270, 'plan_cycle_ends_at' => now('Asia/Manila')->addDays(20)->toIso8601String()],
            'totals' => ['paid' => '23000.00', 'orders' => 3, 'last_order_number' => 'STR-20261108-7KQ4'],
            'usage' => ['lives' => 12, 'live_minutes' => 1440, 'leads' => 530, 'products' => 210, 'storage_bytes' => 5368709120],
            'support' => ['open_tickets' => 1],
            'notes' => ['count' => 3, 'recent' => [['at' => now('Asia/Manila')->subDay()->toIso8601String(), 'by' => 'Ana Cruz', 'body' => 'Asked about lifetime.']]],
            'registered_at' => now('Asia/Manila')->subMonths(2)->toIso8601String(),
            'last_login_at' => now('Asia/Manila')->subHour()->toIso8601String(),
            'last_live_at' => now('Asia/Manila')->subDays(2)->toIso8601String(),
            'snapshot_at' => now('Asia/Manila')->toIso8601String(),
        ];
        foreach ($over as $key => $value) {
            data_set($snapshot, $key, $value);
        }

        return $snapshot;
    }

    /** An order of another venture in /ecom-orders (not Streamr's). */
    protected function otherOrder(string $number = 'ORD-20261108-ABCD', string $store = 'Dragon Scale Store', array $order = []): int
    {
        $now = now('Asia/Manila')->format('Y-m-d H:i:s');
        $id = DB::table('ecom_orders')->insertGetId(array_merge([
            'usersId' => 1, 'orderNumber' => $number, 'orderStatus' => 'pending', 'shippingStatus' => 'pending',
            'clientFirstName' => 'Juan', 'clientLastName' => 'Dela Cruz', 'clientEmail' => 'juan@example.ph',
            'subtotal' => 500, 'shippingTotal' => 0, 'discountTotal' => 0, 'grandTotal' => 500, 'affiliateCommissionTotal' => 0, 'netRevenue' => 500,
            'paymentMethod' => 'manual_gcash', 'paymentVerificationStatus' => 'pending', 'paymentAmountSent' => 500,
            'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now,
        ], $order));
        DB::table('ecom_order_items')->insert([
            'orderId' => $id, 'productName' => 'Rice seeds', 'productStore' => $store, 'productType' => 'ship',
            'unitPrice' => 500, 'quantity' => 1, 'subtotal' => 500, 'shippingCost' => 0, 'deleteStatus' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('ecom_order_payments')->insert([
            'orderId' => $id, 'paymentNumber' => 'PAY-OTHER-' . $id, 'paymentMethod' => 'manual_gcash', 'paymentStatus' => 'pending',
            'amountSent' => 500, 'referenceNumber' => '9999888877776', 'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return $id;
    }

    /**
     * Fake Streamr's /api/mother/*: $responses maps a path (after /api/mother)
     * to a response or a closure; anything else answers 202 {queued: true}.
     */
    protected function fakeStreamr(array $responses = []): void
    {
        Http::fake([
            'streamr.test/*' => function ($request) use ($responses) {
                $path = substr((string) parse_url($request->url(), PHP_URL_PATH), strlen('/api/mother'));
                foreach ($responses as $pattern => $response) {
                    if ($pattern === $path || (str_contains($pattern, '*') && \Illuminate\Support\Str::is($pattern, $path))) {
                        return is_callable($response) ? $response($request) : $response;
                    }
                }

                return Http::response(['success' => true, 'data' => ['queued' => true]], 202);
            },
        ]);
    }

    /** The calls made to the faked Streamr: [method, path, json body, request]. */
    protected function streamrCalls(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_starts_with($pair[0]->url(), self::STREAMR))
            ->map(fn ($pair) => [$pair[0]->method(), (string) parse_url($pair[0]->url(), PHP_URL_PATH), json_decode($pair[0]->body(), true), $pair[0]])
            ->values()
            ->all();
    }

    /** Assert a recorded request carries a valid signature for its body/path. */
    protected function assertSignedForStreamr($request): void
    {
        $method = strtoupper($request->method());
        $path = (string) parse_url($request->url(), PHP_URL_PATH);
        $canonical = in_array($method, ['GET', 'DELETE'], true) ? StreamrSignature::requestLine($method, $path) : $request->body();
        $timestamp = (int) $request->header('X-Streamr-Timestamp')[0];

        $this->assertSame('Bearer ' . self::SECRET, $request->header('Authorization')[0]);
        $this->assertLessThanOrEqual(300, abs(time() - $timestamp));
        $this->assertSame(StreamrSignature::sign(self::SECRET, $timestamp, $canonical), $request->header('X-Streamr-Signature')[0]);
        $this->assertSame(StreamrSignature::USER_AGENT, $request->header('User-Agent')[0]);
    }

    /** Runs what was dispatched afterResponse() outside a request (a test that saves a model directly). */
    protected function runAfterResponse(): void
    {
        $this->app->terminate();
    }

    // ------------------------------------------------------------------

    private function createTables(): void
    {
        // This module's own tables, exactly as they will be made in production.
        $migration = require base_path('database/migrations/2026_11_20_100000_create_streamr_module_tables.php');
        $migration->up();
        // Real migrations of tables this module writes or reads.
        $payments = require base_path('database/migrations/2026_03_10_100000_create_ecom_order_payments_table.php');
        $payments->up();
        $site = require base_path('database/migrations/2026_10_28_120000_create_games_site_tables.php');
        $site->up();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->date('dob')->nullable();
            $table->text('avatar')->nullable();
            $table->text('delete_status')->nullable();
            $table->string('api_key')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->boolean('allow_multiple_logins')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        // The e-commerce tables Streamr's orders are written into, as production
        // has them after every migration (enums included, so a wrong status fails).
        Schema::create('ecom_product_stores', function (Blueprint $table) {
            $table->id();
            $table->text('storeName');
            $table->text('storeDescription')->nullable();
            $table->text('storeLogo')->nullable();
            $table->integer('isActive')->default(1);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
        });

        Schema::create('ecom_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('orderNumber', 50)->unique();
            $table->enum('orderStatus', ['pending', 'paid', 'complete', 'cancelled', 'refunded'])->nullable()->default('pending');
            $table->enum('shippingStatus', ['pending', 'shipped', 'not_applicable'])->nullable()->default('pending');
            $table->string('trackingNumber', 100)->nullable();
            $table->unsignedBigInteger('clientId')->nullable();
            $table->string('clientFirstName', 100)->nullable();
            $table->string('clientMiddleName', 100)->nullable();
            $table->string('clientLastName', 100)->nullable();
            $table->string('clientPhone', 20)->nullable();
            $table->string('clientEmail', 255)->nullable();
            $table->enum('shippingType', ['Regular', 'Cash on Delivery', 'Cash on Pickup'])->nullable();
            $table->string('shippingName', 255)->nullable();
            foreach (['shippingFirstName', 'shippingMiddleName', 'shippingLastName', 'shippingHouseNumber', 'shippingZone', 'shippingMunicipality', 'shippingProvince'] as $column) {
                $table->string($column, 100)->nullable();
            }
            $table->string('shippingPhone', 20)->nullable();
            $table->string('shippingEmail', 255)->nullable();
            $table->string('shippingStreet', 255)->nullable();
            $table->string('shippingZipCode', 20)->nullable();
            foreach (['subtotal', 'shippingTotal', 'discountTotal', 'grandTotal', 'affiliateCommissionTotal', 'netRevenue'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            $table->text('orderNotes')->nullable();
            $table->enum('paymentMethod', ['manual_gcash', 'manual_bank', 'manual_maya', 'manual_instapay', 'manual_other', 'online_payment', 'cod', 'cop'])->nullable();
            $table->enum('paymentVerificationStatus', ['not_required', 'pending', 'verified', 'rejected'])->default('not_required');
            $table->string('paymentPayerName', 255)->nullable();
            $table->decimal('paymentAmountSent', 15, 2)->nullable();
            $table->string('paymentReferenceNumber', 100)->nullable();
            $table->string('paymentPhoneNumber', 20)->nullable();
            $table->string('paymentBankName', 100)->nullable();
            $table->string('paymentBankAccountName', 255)->nullable();
            $table->string('paymentBankAccountNumber', 50)->nullable();
            $table->string('paymentScreenshot', 500)->nullable();
            $table->timestamp('paymentVerifiedAt')->nullable();
            $table->unsignedBigInteger('paymentVerifiedBy')->nullable();
            $table->text('paymentNotes')->nullable();
            $table->string('recoveryToken', 64)->nullable()->index();
            $table->timestamp('recoveryTokenExpiresAt')->nullable();
            $table->timestamp('recoveryEmailSentAt')->nullable();
            $table->integer('recoveryEmailCount')->default(0);
            $table->boolean('isPackage')->default(false);
            $table->unsignedBigInteger('packageId')->nullable();
            $table->string('packageName', 255)->nullable();
            $table->text('packageDescription')->nullable();
            $table->decimal('packageCalculatedPrice', 15, 2)->nullable();
            $table->decimal('packagePrice', 15, 2)->nullable();
            $table->decimal('packageSavings', 15, 2)->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
        });

        Schema::create('ecom_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId');
            $table->unsignedBigInteger('productId')->nullable();
            $table->string('productName', 255);
            $table->string('productStore', 255)->nullable();
            $table->enum('productType', ['ship', 'access'])->default('ship');
            $table->unsignedBigInteger('variantId')->nullable();
            $table->string('variantName', 255)->nullable();
            $table->string('variantSku', 100)->nullable();
            $table->string('variantImage', 500)->nullable();
            $table->decimal('unitPrice', 15, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->unsignedBigInteger('shippingMethodId')->nullable();
            $table->string('shippingMethodName', 255)->nullable();
            $table->decimal('shippingCost', 15, 2)->default(0);
            $table->unsignedBigInteger('accessClientId')->nullable();
            $table->string('accessClientName', 255)->nullable();
            $table->string('accessClientPhone', 20)->nullable();
            $table->string('accessClientEmail', 255)->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
            $table->foreign('orderId')->references('id')->on('ecom_orders')->onDelete('cascade');
        });

        Schema::create('ecom_order_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId');
            $table->string('orderNumber', 50);
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('userName', 255)->nullable();
            $table->string('actionType', 50);
            $table->string('fieldChanged', 100)->nullable();
            $table->string('previousValue', 255)->nullable();
            $table->string('newValue', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });

        // The View modal's other relations, from their real migrations.
        foreach (['2026_01_07_100002_create_ecom_order_discounts_table.php', '2026_01_08_140000_add_is_auto_applied_to_ecom_order_discounts.php',
            '2026_01_07_100003_create_ecom_order_affiliate_commissions_table.php'] as $file) {
            (require base_path('database/migrations/' . $file))->up();
        }

        Schema::create('ecom_refund_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId');
            $table->unsignedBigInteger('storeId')->nullable();
            $table->string('storeName')->nullable();
            $table->string('clientName')->nullable();
            $table->string('clientEmail')->nullable();
            $table->string('clientPhone')->nullable();
            $table->string('refundNumber')->unique();
            $table->text('requestReason')->nullable();
            $table->datetime('requestedAt');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->enum('refundType', ['full', 'partial'])->nullable();
            $table->decimal('orderSubtotal', 15, 2)->default(0);
            $table->decimal('requestedAmount', 15, 2)->default(0);
            $table->decimal('approvedAmount', 15, 2)->default(0);
            $table->unsignedBigInteger('processedBy')->nullable();
            $table->datetime('processedAt')->nullable();
            $table->text('adminNotes')->nullable();
            $table->text('rejectionReason')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
            $table->foreign('orderId')->references('id')->on('ecom_orders')->onDelete('cascade');
        });

        Schema::create('ecom_refund_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refundRequestId');
            $table->unsignedBigInteger('orderItemId');
            $table->unsignedBigInteger('productId')->nullable();
            $table->unsignedBigInteger('variantId')->nullable();
            $table->string('productName');
            $table->string('variantName')->nullable();
            $table->string('productStore')->nullable();
            $table->integer('originalQuantity');
            $table->integer('refundQuantity');
            $table->decimal('unitPrice', 15, 2);
            $table->decimal('refundAmount', 15, 2);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
            $table->foreign('refundRequestId')->references('id')->on('ecom_refund_requests')->onDelete('cascade');
            $table->foreign('orderItemId')->references('id')->on('ecom_order_items')->onDelete('cascade');
        });

        // Per-store payment receiving details (after its three migrations).
        Schema::create('ecom_store_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('storeId');
            $table->string('bankName', 255)->nullable();
            $table->string('bankAccountName', 255)->nullable();
            $table->string('bankAccountNumber', 100)->nullable();
            $table->string('bankQrCodeImage')->nullable();
            $table->boolean('isBankActive')->default(0);
            $table->string('gcashNumber', 20)->nullable();
            $table->string('gcashAccountName', 255)->nullable();
            $table->string('paymentScreenshot', 500)->nullable();
            $table->string('qrCodeImage', 500)->nullable();
            $table->boolean('isGcashActive')->default(0);
            $table->string('mayaNumber')->nullable();
            $table->string('mayaAccountName')->nullable();
            $table->string('mayaQrCodeImage')->nullable();
            $table->boolean('isMayaActive')->default(false);
            $table->string('paypalEmail')->nullable();
            $table->string('paypalAccountName')->nullable();
            $table->boolean('isPaypalActive')->default(false);
            $table->text('paymentInstructions')->nullable();
            $table->boolean('isActive')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
            $table->unique(['storeId', 'deleteStatus']);
        });

        // The legacy CRM: its text columns are NOT NULL (pass '' for unknowns).
        Schema::create('clients_all_database', function (Blueprint $table) {
            $table->id();
            $table->text('clientFirstName');
            $table->text('clientMiddleName');
            $table->text('clientLastName');
            $table->text('clientPhoneNumber');
            $table->text('clientEmailAddress');
            $table->integer('deleteStatus')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // anee.io's own orders, only what the reference check reads.
        Schema::create('as_orders', function (Blueprint $table) {
            $table->id();
            $table->string('orderNumber', 24)->unique();
            $table->string('refNumber', 64)->nullable()->index();
            $table->char('proofSha', 64)->nullable();
            $table->json('aiReport')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}
