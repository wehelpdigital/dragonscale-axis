<?php

namespace Tests\Feature\Games;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Base for the Games module's tests.
 *
 * .env points at the PRODUCTION database. These tests make sure they can never
 * reach it, whichever runner started them: the app's database settings are
 * replaced with an in-memory SQLite database before anything can connect, the
 * MySQL connection is aimed at an address that does not exist, and setUp
 * refuses to go on unless the connection really is SQLite in memory. Only the
 * tables these tests need are made: this module's own migration, plus the
 * e-commerce and users tables it writes to, built here to mirror production.
 *
 * Run with: vendor/bin/phpunit -c phpunit.games.xml
 */
abstract class GamesTestCase extends TestCase
{
    protected FakeGameServer $game;

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

        // The game server and Acumbamail, as the fakes below answer them.
        $config->set('games.salpakan.api_url', FakeGameServer::BASE);
        $config->set('games.salpakan.sync_token', FakeGameServer::TOKEN);
        $config->set('games.salpakan.usd_to_php_rate', 58.0);
        $config->set('games.salpakan.acumbamail_list_id', 1571430);
        $config->set('services.acumbamail.key', 'test-acumbamail-key');
        $config->set('services.acumbamail.url', 'https://acumbamail.com/api/1');
        $config->set('services.acumbamail.pause_ms', 0);
        $config->set('services.acumbamail.double_optin', 0);

        $app['db']->purge();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Games tests only run on an in-memory SQLite database. Refusing to touch '
                . $connection->getDriverName() . ':' . $connection->getDatabaseName() . '.');
        }

        Http::preventStrayRequests();
        $this->createTables();
        $this->game = new FakeGameServer();
    }

    /** Fake the game server (and Acumbamail with $acumbamail, or "always fine"). */
    protected function fakeServices(?callable $acumbamail = null): void
    {
        $acumbamail ??= function ($request) {
            parse_str((string) $request->body(), $form);

            return Http::response(['email' => $form['merge_fields']['email'] ?? $form['email'] ?? null, 'id' => 555], 201);
        };

        Http::fake([
            'salpakan.test/*' => fn ($request) => $this->game->handle($request),
            'acumbamail.com/*' => $acumbamail,
        ]);
    }

    protected function admin(): User
    {
        $existing = User::where('email', 'admin@example.test')->first();
        if ($existing) {
            return $existing;
        }

        $user = new User();
        $user->forceFill([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret-password'),
            'delete_status' => 'active',
            'allow_multiple_logins' => true,
        ])->save();

        return $user;
    }

    /** Acumbamail calls made so far, as [function, form fields]. */
    protected function acumbamailCalls(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'acumbamail.com'))
            ->map(function ($pair) {
                parse_str((string) $pair[0]->body(), $form);

                return [basename(rtrim(parse_url($pair[0]->url(), PHP_URL_PATH), '/')), $form];
            })
            ->values()
            ->all();
    }

    private function createTables(): void
    {
        // This module's own tables, exactly as they will be made in production.
        $migration = require base_path('database/migrations/2026_10_27_100000_create_games_tables.php');
        $migration->up();

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

        // The e-commerce tables game orders are logged into, as production has
        // them after every migration (enums included, so a wrong status fails).
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
            $table->enum('orderStatus', ['pending', 'paid', 'complete', 'cancelled', 'refunded'])->default('pending');
            $table->enum('shippingStatus', ['pending', 'shipped', 'not_applicable'])->default('pending');
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
    }
}
