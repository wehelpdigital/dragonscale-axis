<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Games > Website (2026-10-28): the studio website (wehelpdigital/game-website)
 * is managed here.
 *
 *   games_site_games  the games the website features: words, pictures, platforms,
 *                     whether it is live or coming soon, and where to play it.
 *   games_payments    the web checkout: a purchase in a web game, paid on the
 *                     website (GCash for now). One row per checkout link; the
 *                     buyer's receipt, what the AI read on it, the checks, and
 *                     whether the game was told to grant the item.
 *
 * The website's own settings (its name, tagline, contact) and the payment
 * settings (the GCash account, the fee for the AI check) are rows in
 * games_settings under gameKey "site".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games_site_games', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40)->unique();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('tagline', 200)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('genre', 80)->nullable();
            $table->enum('status', ['live', 'coming_soon', 'hidden'])->default('coming_soon');
            $table->string('playUrl', 255)->nullable();
            $table->string('accent', 20)->nullable();
            $table->string('logoImage', 255)->nullable();
            $table->string('heroImage', 255)->nullable();
            $table->json('platforms')->nullable();
            $table->json('features')->nullable();
            $table->json('screenshots')->nullable();
            $table->integer('sortOrder')->default(0);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();
        });

        Schema::create('games_payments', function (Blueprint $table) {
            $table->id();
            $table->string('gameKey', 40);
            $table->string('checkoutToken', 64)->unique();
            $table->string('orderId', 64)->nullable();
            $table->unsignedBigInteger('playerId')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('itemName', 160)->nullable();
            $table->string('itemCategory', 40)->nullable();
            $table->string('itemSlug', 120)->nullable();
            $table->unsignedInteger('priceUsdCents')->default(0);
            $table->decimal('pricePhp', 15, 2)->default(0);
            $table->decimal('feePhp', 15, 2)->default(0);
            $table->decimal('totalPhp', 15, 2)->default(0);
            $table->string('method', 20)->default('gcash');
            // awaiting_payment -> checking -> approved -> paid (the game granted it), or
            // checking -> review (a person decides) -> approved -> paid | rejected;
            // expired when the link ran out unpaid.
            $table->string('status', 20)->default('awaiting_payment');
            $table->string('refNumber', 64)->nullable();
            $table->string('proofPath', 255)->nullable();
            $table->string('proofMime', 80)->nullable();
            $table->string('proofSha', 64)->nullable()->index();
            $table->dateTime('proofUploadedAt')->nullable();
            $table->unsignedTinyInteger('proofUploads')->default(0);
            $table->string('aiStatus', 20)->nullable();
            $table->unsignedTinyInteger('aiScore')->nullable();
            $table->json('aiReport')->nullable();
            $table->dateTime('aiCheckedAt')->nullable();
            $table->text('reviewNote')->nullable();
            $table->unsignedBigInteger('reviewedBy')->nullable();
            $table->dateTime('reviewedAt')->nullable();
            $table->dateTime('fulfilledAt')->nullable();
            $table->unsignedInteger('fulfillAttempts')->default(0);
            $table->text('fulfillError')->nullable();
            $table->string('buyerIp', 45)->nullable();
            $table->dateTime('expiresAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['gameKey', 'status']);
        });

        // The first game on the website (edited from now on in Games > Website).
        $game = \App\Services\Games\GamesSiteDefaults::salpakan();
        foreach (['platforms', 'features', 'screenshots'] as $json) {
            $game[$json] = json_encode($game[$json], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        \Illuminate\Support\Facades\DB::table('games_site_games')->insert($game + ['delete_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('games_payments');
        Schema::dropIfExists('games_site_games');
    }
};
