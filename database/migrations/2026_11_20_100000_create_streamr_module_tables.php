<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Streamr module (2026-11-20): the tables of the Streamr module in DS AXIS.
 * Streamr (the livestream-selling SaaS) never reads this database; it calls
 * /api/streamr/v1 and this app writes these tables (and /ecom-orders) for it.
 *
 *   streamr_clients         one row per Streamr user: the snapshot Streamr pushes
 *   streamr_client_actions  actions sent from Streamr > Clients, and how they went
 *   streamr_orders          Streamr's identity of each /ecom-orders order (STR-...)
 *   streamr_payment_proofs  each receipt sent (bytes kept here, never on a public
 *                           disk) with Streamr's AI reading of it
 *   streamr_desk_tickets    support tickets, synced both ways with Streamr
 *   streamr_desk_messages   their messages and status changes (id = feed cursor)
 *   streamr_settings        the module's settings shelf (never secrets)
 *   streamr_api_requests    the request log behind Link status (no bodies)
 *
 * Only creates tables, each behind hasTable, so it is safe on any host and
 * touches nothing that already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('streamr_clients')) {
            Schema::create('streamr_clients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('streamrUserId')->unique();
                $table->string('email', 191)->index();
                $table->string('firstName', 100)->nullable();
                $table->string('lastName', 100)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('role', 20)->default('client');
                // active | suspended | revoked | deletion_requested | deleted
                $table->string('accountStatus', 20)->default('active');
                $table->unsignedBigInteger('storeId')->nullable();
                $table->string('storeName', 150)->nullable();
                $table->string('storeHandle', 80)->nullable()->index();
                $table->string('storeUrl', 255)->nullable();
                $table->string('customDomain', 191)->nullable();
                $table->string('planKey', 20)->nullable();
                $table->string('planName', 60)->nullable();
                $table->decimal('planPrice', 15, 2)->nullable();
                // active | grace | expired | suspended | none
                $table->string('subscriptionState', 12)->default('none');
                $table->dateTime('startsAt')->nullable();
                $table->dateTime('expiresAt')->nullable();
                $table->dateTime('graceUntil')->nullable();
                $table->boolean('isLifetime')->default(false);
                $table->integer('planCredits')->default(0);
                $table->integer('purchasedCredits')->default(0);
                $table->integer('availableCredits')->default(0);
                $table->dateTime('planCycleEndsAt')->nullable();
                $table->decimal('totalPaid', 15, 2)->default(0);
                $table->unsignedInteger('ordersCount')->default(0);
                $table->string('lastOrderNumber', 50)->nullable();
                $table->unsignedInteger('livesCount')->default(0);
                $table->unsignedInteger('liveMinutes')->default(0);
                $table->unsignedInteger('leadsCount')->default(0);
                $table->unsignedInteger('productsCount')->default(0);
                $table->unsignedInteger('openTickets')->default(0);
                $table->unsignedInteger('notesCount')->default(0);
                $table->unsignedBigInteger('storageBytes')->default(0);
                $table->json('recentNotes')->nullable();
                $table->json('snapshot')->nullable();
                $table->unsignedBigInteger('crmClientId')->nullable();
                $table->dateTime('registeredAt')->nullable();
                $table->dateTime('lastLoginAt')->nullable();
                $table->dateTime('lastLiveAt')->nullable();
                // Streamr's snapshot_at: a push older than this one changes nothing.
                $table->dateTime('snapshotAt')->nullable();
                $table->dateTime('syncedAt')->nullable();
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->index(['subscriptionState', 'expiresAt'], 'streamr_clients_state_idx');
                $table->index(['delete_status', 'registeredAt'], 'streamr_clients_list_idx');
            });
        }

        if (! Schema::hasTable('streamr_client_actions')) {
            Schema::create('streamr_client_actions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->unique();
                $table->unsignedBigInteger('streamrUserId')->index();
                $table->string('action', 40);
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('requestedBy')->nullable();
                $table->string('requestedByName', 120)->nullable();
                // sent | applied | failed
                $table->string('status', 12)->default('sent');
                $table->unsignedSmallInteger('httpStatus')->nullable();
                $table->json('result')->nullable();
                $table->string('error', 1000)->nullable();
                $table->unsignedInteger('durationMs')->nullable();
                $table->unsignedSmallInteger('attempts')->default(1);
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->index(['streamrUserId', 'id'], 'streamr_actions_user_idx');
                $table->index(['status', 'id'], 'streamr_actions_status_idx');
            });
        }

        if (! Schema::hasTable('streamr_orders')) {
            Schema::create('streamr_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->unique();
                $table->string('orderNumber', 50)->unique();
                $table->unsignedBigInteger('ecomOrderId')->unique();
                $table->unsignedBigInteger('streamrUserId')->index();
                // plan | credits
                $table->string('kind', 10);
                $table->string('itemKey', 40);
                $table->string('sku', 100);
                $table->string('variantName', 255);
                $table->decimal('total', 15, 2);
                // manual_gcash | manual_maya | manual_bank
                $table->string('method', 20);
                $table->unsignedBigInteger('crmClientId')->nullable();
                $table->string('aiVerdict', 10)->nullable();
                $table->unsignedTinyInteger('aiScore')->nullable();
                $table->boolean('autoApproved')->default(false);
                // ai | streamr_admin
                $table->string('decidedBy', 20)->nullable();
                $table->unsignedTinyInteger('proofsCount')->default(0);
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->index(['delete_status', 'created_at'], 'streamr_orders_list_idx');
            });
        }

        if (! Schema::hasTable('streamr_payment_proofs')) {
            Schema::create('streamr_payment_proofs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->unique();
                $table->unsignedBigInteger('streamrOrderId')->index();
                $table->string('orderNumber', 50);
                $table->unsignedTinyInteger('n');
                $table->unsignedBigInteger('ecomPaymentId')->nullable();
                // image | pdf | ref (a reference number only)
                $table->string('kind', 8);
                $table->string('name', 191)->nullable();
                $table->string('mime', 60)->nullable();
                $table->unsignedInteger('size')->nullable();
                $table->char('sha256', 64)->nullable()->index();
                // The receipt itself. LONGBLOB on MySQL (below); BLOB stops at 64 KB.
                $table->binary('bytes')->nullable();
                $table->string('reference', 100)->nullable();
                $table->string('refKey', 64)->nullable()->index();
                $table->string('aiVerdict', 10)->nullable();
                $table->unsignedTinyInteger('aiScore')->nullable();
                $table->json('aiReport')->nullable();
                $table->dateTime('aiCheckedAt')->nullable();
                $table->dateTime('submittedAt')->nullable();
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->unique(['orderNumber', 'n'], 'streamr_proofs_order_n_uniq');
            });
        }

        // Receipts are up to 8 MB. Safe to repeat: it only widens the column.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && Schema::hasTable('streamr_payment_proofs')) {
            DB::statement('ALTER TABLE streamr_payment_proofs MODIFY bytes LONGBLOB NULL');
        }

        if (! Schema::hasTable('streamr_desk_tickets')) {
            Schema::create('streamr_desk_tickets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->unique();
                $table->string('ticketNumber', 20)->unique();
                $table->unsignedBigInteger('streamrUserId')->index();
                $table->string('clientName', 191)->nullable();
                $table->string('clientEmail', 191)->nullable();
                $table->string('storeName', 150)->nullable();
                $table->string('subject', 200);
                $table->string('category', 40)->default('general');
                // low | normal | high | urgent
                $table->string('priority', 12)->default('normal');
                // open | answered | closed
                $table->string('status', 20)->default('open');
                $table->dateTime('lastMessageAt')->nullable();
                // client | admin | system
                $table->string('lastMessageBy', 10)->nullable();
                $table->unsignedInteger('adminUnread')->default(0);
                $table->unsignedInteger('clientUnread')->default(0);
                $table->dateTime('adminReadAt')->nullable();
                $table->dateTime('statusChangedAt')->nullable();
                $table->string('statusChangedBy', 80)->nullable();
                $table->string('origin', 10)->default('streamr');
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->index(['status', 'lastMessageAt'], 'streamr_desk_queue_idx');
            });
        }

        if (! Schema::hasTable('streamr_desk_messages')) {
            Schema::create('streamr_desk_messages', function (Blueprint $table) {
                // The id is also the cursor of GET /api/streamr/v1/support/updates.
                $table->bigIncrements('id');
                $table->char('uuid', 36)->unique();
                $table->unsignedBigInteger('ticketId')->index();
                $table->char('ticketUuid', 36)->index();
                // message | status
                $table->string('kind', 10)->default('message');
                // client | admin | system
                $table->string('authorType', 10);
                $table->unsignedBigInteger('authorId')->nullable();
                $table->string('authorName', 120)->nullable();
                // streamr | mother
                $table->string('origin', 10);
                $table->mediumText('body');
                $table->string('bodyFormat', 8)->default('text');
                $table->json('attachments')->nullable();
                $table->boolean('isInternal')->default(false);
                $table->json('meta')->nullable();
                // Mother messages: when Streamr received it (doorbell or feed).
                $table->dateTime('pulledAt')->nullable();
                $table->enum('delete_status', ['active', 'deleted'])->default('active');
                $table->timestamps();

                $table->index(['origin', 'id'], 'streamr_desk_feed_idx');
                $table->index(['origin', 'pulledAt'], 'streamr_desk_pull_idx');
            });
        }

        if (! Schema::hasTable('streamr_settings')) {
            Schema::create('streamr_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('settingKey', 100)->unique();
                $table->text('settingValue')->nullable();
                $table->unsignedBigInteger('updatedBy')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('streamr_api_requests')) {
            Schema::create('streamr_api_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                // in (Streamr called us) | out (we called Streamr)
                $table->string('direction', 3);
                $table->string('method', 6);
                $table->string('path', 191);
                $table->unsignedSmallInteger('status')->nullable();
                $table->string('errorCode', 40)->nullable();
                $table->unsignedInteger('durationMs')->nullable();
                $table->string('requestId', 36)->nullable();
                $table->string('idempotencyKey', 36)->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['direction', 'created_at'], 'streamr_api_dir_idx');
                $table->index('created_at', 'streamr_api_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('streamr_api_requests');
        Schema::dropIfExists('streamr_settings');
        Schema::dropIfExists('streamr_desk_messages');
        Schema::dropIfExists('streamr_desk_tickets');
        Schema::dropIfExists('streamr_payment_proofs');
        Schema::dropIfExists('streamr_orders');
        Schema::dropIfExists('streamr_client_actions');
        Schema::dropIfExists('streamr_clients');
    }
};
