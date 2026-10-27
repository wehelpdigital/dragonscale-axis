<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Games (2026-10-27): the games Dragon Scale publishes, starting with
 * Salpakan Tactics! (gameKey "salpakan").
 *
 *   games_players          everyone registered in a game, as the game server
 *                          last described them, plus where they stand with
 *                          Acumbamail (the marketing list)
 *   games_player_activity  one row per player per day they played (the day is
 *                          the game server's, in UTC)
 *   games_orders           every real-money purchase, and the /ecom-orders
 *                          order it was logged as (ecomOrderId)
 *   games_sync_states      per game and data type: where the last sync got to
 *                          (cursorValue), how it went, and what it said
 *   games_settings         per game key/value settings (analytics codes as the
 *                          game last confirmed them, the peso rate, run notes)
 *
 * Every row carries gameKey, so the next game shares these tables. The rows
 * mirror the game server and are shared by every admin: there is no usersId.
 * Players and orders are matched to the game by remotePlayerId/remoteOrderId
 * (the game's own ids), never by this table's id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games_players', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40);
            $table->unsignedBigInteger('remotePlayerId');
            $table->string('username', 100)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('signUpMethod', 20)->nullable();
            $table->dateTime('registeredAt')->nullable();
            $table->dateTime('remoteUpdatedAt')->nullable();
            $table->dateTime('lastActiveAt')->nullable();
            $table->string('lastPlatform', 20)->nullable();
            $table->char('countryCode', 2)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->unsignedInteger('gamesPlayed')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->integer('elo')->nullable();
            $table->unsignedSmallInteger('rankTier')->nullable();
            $table->string('rankName', 50)->nullable();
            $table->unsignedInteger('xp')->default(0);
            $table->integer('credits')->default(0);
            $table->boolean('adFree')->default(false);
            $table->boolean('isBanned')->default(false);
            $table->boolean('marketingOptOut')->default(false);

            // Worked out from games_orders after each sync (completed, not test
            // purchases); the total is in the game's currency, in cents.
            $table->unsignedInteger('purchasesCount')->default(0);
            $table->unsignedBigInteger('totalSpentCents')->default(0);

            // Acumbamail: pending = still to be looked at; subscribed; skipped
            // (no real email, or opted out); failed (the API refused, retried);
            // unsubscribed (opted out after being added).
            $table->enum('acumbamailStatus', ['pending', 'subscribed', 'skipped', 'failed', 'unsubscribed'])->default('pending');
            $table->string('acumbamailEmail', 191)->nullable();
            $table->string('acumbamailSubscriberId', 50)->nullable();
            $table->dateTime('acumbamailSubscribedAt')->nullable();
            $table->dateTime('acumbamailUnsubscribedAt')->nullable();
            $table->dateTime('acumbamailTriedAt')->nullable();
            $table->unsignedTinyInteger('acumbamailAttempts')->default(0);
            $table->string('acumbamailMessage', 500)->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique(['gameKey', 'remotePlayerId']);
            $table->index(['gameKey', 'delete_status', 'lastActiveAt']);
            $table->index(['gameKey', 'registeredAt']);
            $table->index(['gameKey', 'countryCode']);
            $table->index(['gameKey', 'acumbamailStatus']);
            $table->index('email');
        });

        Schema::create('games_player_activity', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40);
            $table->unsignedBigInteger('remotePlayerId');
            $table->date('activityDate');
            $table->string('platform', 20)->nullable();
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('matchesPlayed')->default(0);
            $table->unsignedInteger('matchesWon')->default(0);
            $table->unsignedInteger('minutesPlayed')->default(0);
            $table->dateTime('firstSeenAt')->nullable();
            $table->dateTime('lastSeenAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Named: the generated name would sit right on MySQL's 64-character limit.
            $table->unique(['gameKey', 'remotePlayerId', 'activityDate'], 'games_activity_player_day_unique');
            $table->index(['gameKey', 'activityDate'], 'games_activity_day_index');
        });

        Schema::create('games_orders', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40);
            $table->string('remoteOrderId', 64);
            $table->unsignedBigInteger('remotePlayerId')->nullable();
            $table->string('store', 20);
            $table->string('orderKind', 20);
            $table->string('productId', 100)->nullable();
            $table->string('itemSlug', 100)->nullable();
            $table->string('itemName', 191)->nullable();
            $table->string('itemCategory', 50)->nullable();
            $table->unsignedInteger('priceCents')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->enum('orderStatus', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->boolean('isTestPurchase')->default(false);
            $table->string('storeOrderId', 191)->nullable();
            $table->dateTime('orderedAt')->nullable();
            $table->dateTime('completedAt')->nullable();
            $table->dateTime('remoteUpdatedAt')->nullable();

            // The /ecom-orders order this purchase was logged as, the game status
            // that order last reflected, the peso amount and the rate used.
            $table->unsignedBigInteger('ecomOrderId')->nullable();
            $table->string('ecomStatus', 20)->nullable();
            $table->dateTime('ecomLoggedAt')->nullable();
            $table->decimal('ecomPhpAmount', 15, 2)->nullable();
            $table->decimal('ecomRate', 10, 4)->nullable();
            $table->string('ecomError', 500)->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique(['gameKey', 'remoteOrderId']);
            $table->index(['gameKey', 'orderStatus', 'orderedAt']);
            $table->index(['gameKey', 'remotePlayerId']);
            $table->index('ecomOrderId');
        });

        Schema::create('games_sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40);
            $table->string('dataType', 30);
            // players/orders: the newest updated_at kept (ISO 8601, UTC);
            // activity: the first day the next sync asks for (Y-m-d, UTC).
            $table->string('cursorValue', 64)->nullable();
            // A sync cut short part way through its pages starts again here.
            $table->string('resumeCursor', 64)->nullable();
            $table->unsignedInteger('resumePage')->nullable();
            $table->enum('lastStatus', ['running', 'ok', 'partial', 'failed', 'skipped'])->nullable();
            $table->string('lastMessage', 1000)->nullable();
            $table->unsignedInteger('lastCount')->default(0);
            $table->unsignedInteger('remoteTotal')->nullable();
            $table->unsignedInteger('failCount')->default(0);
            $table->dateTime('lastRunAt')->nullable();
            $table->dateTime('lastSuccessAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique(['gameKey', 'dataType']);
        });

        Schema::create('games_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40);
            $table->string('settingKey', 100);
            $table->text('settingValue')->nullable();
            $table->timestamps();

            $table->unique(['gameKey', 'settingKey']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games_settings');
        Schema::dropIfExists('games_sync_states');
        Schema::dropIfExists('games_orders');
        Schema::dropIfExists('games_player_activity');
        Schema::dropIfExists('games_players');
    }
};
