<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GET /api/streamr/v1/references/check: a reference (normalised: spaces,
 * dashes and "Ref No." labels ignored) or a receipt file already used by
 * anee.io, the games web checkout, any /ecom-orders payment or order, or an
 * earlier Streamr receipt; the order being checked can be left out; a table
 * missing on this host is skipped.
 */
class StreamrReferencesTest extends StreamrTestCase
{
    private function check(array $query)
    {
        return $this->signedGet('/api/streamr/v1/references/check', $query);
    }

    public function test_a_new_reference_is_not_used(): void
    {
        $this->check(['ref_key' => '1023796539709'])->assertOk()->assertJsonPath('data', ['used' => false, 'sources' => []]);
    }

    public function test_references_are_found_in_every_venture_normalised(): void
    {
        DB::table('games_payments')->insert(['gameKey' => 'salpakan', 'checkoutToken' => 'tok-1', 'refNumber' => '1023 796 539709', 'status' => 'paid',
            'delete_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->check(['ref_key' => '1023796539709'])->assertOk()->assertJsonPath('data', ['used' => true, 'sources' => ['games_payments']]);

        DB::table('as_orders')->insert(['orderNumber' => 'AN-261108-ABCD', 'refNumber' => 'Ref No. 2000 111 222333', 'created_at' => now()]);
        $this->check(['ref_key' => '2000111222333'])->assertOk()->assertJsonPath('data.sources', ['as_orders']);

        // anee.io's AI reading of the receipt counts too.
        DB::table('as_orders')->insert(['orderNumber' => 'AN-261108-EFGH', 'refNumber' => null,
            'aiReport' => json_encode(['verdict' => 'pass', 'read' => ['ref' => '3000-444-555666']]), 'created_at' => now()]);
        $this->check(['ref_key' => '3000444555666'])->assertOk()->assertJsonPath('data.sources', ['as_orders']);

        $this->otherOrder('ORD-20261108-ABCD');   // its payment carries 9999888877776
        $this->check(['ref_key' => 'REFNO9999888877776'])->assertOk()->assertJsonPath('data.sources', ['ecom_order_payments']);
    }

    public function test_a_streamr_order_is_found_unless_it_is_the_one_being_checked(): void
    {
        $bytes = $this->receiptBytes();
        $this->postOrder([], $bytes)->assertOk();

        $this->check(['ref_key' => '1023796539709'])->assertOk()
            ->assertJsonPath('data', ['used' => true, 'sources' => ['ecom_order_payments', 'ecom_orders', 'streamr_payment_proofs']]);
        $this->check(['sha256' => hash('sha256', $bytes)])->assertOk()->assertJsonPath('data', ['used' => true, 'sources' => ['streamr_payment_proofs']]);

        $this->check(['ref_key' => '1023796539709', 'sha256' => hash('sha256', $bytes), 'exclude_number' => 'STR-20261108-7KQ4'])
            ->assertOk()->assertJsonPath('data', ['used' => false, 'sources' => []]);
    }

    public function test_a_file_hash_used_on_anee_or_the_games_checkout_counts(): void
    {
        $sha = str_repeat('ab', 32);
        DB::table('as_orders')->insert(['orderNumber' => 'AN-261108-WXYZ', 'proofSha' => $sha, 'created_at' => now()]);

        $this->check(['sha256' => strtoupper($sha)])->assertOk()->assertJsonPath('data', ['used' => true, 'sources' => ['as_orders']]);
    }

    public function test_it_works_where_anee_has_no_orders_table(): void
    {
        Schema::drop('as_orders');
        DB::table('games_payments')->insert(['gameKey' => 'salpakan', 'checkoutToken' => 'tok-2', 'refNumber' => '5555666677778', 'status' => 'paid',
            'delete_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->check(['ref_key' => '5555666677778'])->assertOk()->assertJsonPath('data', ['used' => true, 'sources' => ['games_payments']]);
    }

    public function test_one_of_ref_key_or_sha256_is_needed(): void
    {
        $this->check([])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->check(['sha256' => 'not-a-hash'])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['sha256']]]);
    }
}
