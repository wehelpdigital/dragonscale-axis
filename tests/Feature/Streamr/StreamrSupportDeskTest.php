<?php

namespace Tests\Feature\Streamr;

use App\Models\StreamrDeskMessage;
use App\Models\StreamrDeskTicket;
use Illuminate\Support\Facades\Http;

/**
 * Streamr > Support in DS AXIS: a reply here is kept with origin "mother",
 * marks the ticket answered, rings Streamr after the response (delivered ->
 * pulledAt), and is also in GET /support/updates; Close/Reopen likewise.
 * Every form posts back to streamr-support.index?id=.
 */
class StreamrSupportDeskTest extends StreamrTestCase
{
    private StreamrDeskTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', [
            'uuid' => '6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f', 'number' => 'SR-2026-0007', 'streamr_user_id' => 42,
            'client' => ['name' => 'Lia Santos', 'email' => 'lia@example.ph', 'store' => 'Bags'], 'subject' => 'Live stopped',
            'category' => 'live', 'priority' => 'normal', 'status' => 'open', 'created_at' => now('Asia/Manila')->subHour()->toIso8601String(),
            'message' => ['uuid' => '7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51', 'author_type' => 'client', 'author_name' => 'Lia Santos',
                'body' => 'My live stopped.', 'body_format' => 'text', 'attachments' => [], 'is_internal' => false,
                'created_at' => now('Asia/Manila')->subHour()->toIso8601String()],
        ])->assertOk();
        $this->ticket = StreamrDeskTicket::firstOrFail();
    }

    public function test_opening_a_ticket_marks_it_read(): void
    {
        $this->assertSame(1, $this->ticket->adminUnread);

        $this->actingAs($this->admin())->get(route('streamr-support.index', ['id' => $this->ticket->id]))
            ->assertOk()->assertSee('My live stopped.')->assertSee('Live stopped');

        $fresh = StreamrDeskTicket::firstOrFail();
        $this->assertSame(0, $fresh->adminUnread);
        $this->assertNotNull($fresh->adminReadAt);

        $this->actingAs($this->admin())->get(route('streamr-support.index', ['id' => 999]))->assertRedirect(route('streamr-support.index'));
    }

    public function test_a_reply_is_kept_answered_and_rung_to_streamr_after_the_response(): void
    {
        $this->fakeStreamr(['/support/messages' => Http::response(['success' => true, 'data' => ['stored' => true, 'duplicate' => false]])]);
        $admin = $this->admin('Ana Cruz', 'ana@example.test');

        $this->actingAs($admin)->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => "We restarted it.\nTry again."])
            ->assertRedirect(route('streamr-support.index', ['id' => $this->ticket->id]))
            ->assertSessionHas('success', 'Reply sent. Streamr emails the client.');

        $message = StreamrDeskMessage::where('origin', 'mother')->firstOrFail();
        $this->assertSame(['admin', $admin->id, 'Ana Cruz', 'text', false], [$message->authorType, $message->authorId, $message->authorName, $message->bodyFormat, $message->isInternal]);
        $ticket = StreamrDeskTicket::firstOrFail();
        $this->assertSame(['answered', 1, 'admin'], [$ticket->status, $ticket->clientUnread, $ticket->lastMessageBy]);

        $calls = $this->streamrCalls();
        $this->assertCount(1, $calls);
        [$method, $path, $body, $request] = $calls[0];
        $this->assertSame(['POST', '/api/mother/support/messages'], [$method, $path]);
        $this->assertSame('6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f', $body['ticket_uuid']);
        $this->assertSame([$message->uuid, 'admin', null, 'Ana Cruz', 'mother', "We restarted it.\nTry again.", 'text', [], false],
            [$body['message']['uuid'], $body['message']['author_type'], $body['message']['author_id'], $body['message']['author_name'], $body['message']['origin'],
                $body['message']['body'], $body['message']['body_format'], $body['message']['attachments'], $body['message']['is_internal']]);
        $this->assertSignedForStreamr($request);
        $this->assertNotNull(StreamrDeskMessage::where('origin', 'mother')->firstOrFail()->pulledAt);
    }

    public function test_an_undelivered_reply_waits_for_the_feed(): void
    {
        $this->fakeStreamr(['/support/messages' => Http::response(['success' => false, 'error' => ['code' => 'server_error', 'message' => 'Down']], 500)]);

        $this->actingAs($this->admin())->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => 'Hello'])
            ->assertRedirect(route('streamr-support.index', ['id' => $this->ticket->id]));

        $this->assertNull(StreamrDeskMessage::where('origin', 'mother')->firstOrFail()->pulledAt);
    }

    public function test_an_internal_note_does_not_answer_the_ticket(): void
    {
        $this->fakeStreamr();

        $this->actingAs($this->admin())->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => 'VIP, be quick.', 'internal' => '1'])
            ->assertSessionHas('success', 'Internal note saved (the client does not see it).');

        $ticket = StreamrDeskTicket::firstOrFail();
        $this->assertSame(['open', 0], [$ticket->status, $ticket->clientUnread]);
        $this->assertTrue($this->streamrCalls()[0][2]['message']['is_internal']);
    }

    public function test_a_reply_is_required_and_capped(): void
    {
        Http::fake();
        $this->actingAs($this->admin())->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => ''])
            ->assertRedirect(route('streamr-support.index', ['id' => $this->ticket->id]))->assertSessionHasErrors('body');
        $this->actingAs($this->admin())->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => str_repeat('x', 8001)])
            ->assertSessionHasErrors('body');
        Http::assertNothingSent();
    }

    public function test_close_and_reopen_are_kept_and_rung(): void
    {
        $this->fakeStreamr();
        $admin = $this->admin('Jun Reyes', 'jun@example.test');

        $this->actingAs($admin)->post(route('streamr-support.status', ['id' => $this->ticket->id, 'status' => 'closed']))
            ->assertRedirect(route('streamr-support.index', ['id' => $this->ticket->id]))->assertSessionHas('success');

        $ticket = StreamrDeskTicket::firstOrFail();
        $this->assertSame(['closed', 'mother:Jun Reyes'], [$ticket->status, $ticket->statusChangedBy]);
        [$method, $path, $body] = $this->streamrCalls()[0];
        $this->assertSame(['POST', '/api/mother/support/tickets/6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f/status'], [$method, $path]);
        $this->assertSame(['closed', 'mother:Jun Reyes'], [$body['status'], $body['changed_by']]);
        $this->assertStringEndsWith('+08:00', $body['changed_at']);

        // Answered cannot be picked; a reply to a closed ticket is allowed and answers it.
        $this->actingAs($admin)->post(route('streamr-support.status', ['id' => $this->ticket->id, 'status' => 'answered']))->assertSessionHas('error');
        $this->actingAs($admin)->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => 'One more thing.']);
        $this->assertSame('answered', StreamrDeskTicket::firstOrFail()->status);
    }

    public function test_the_feed_gives_streamr_what_was_written_here(): void
    {
        Http::fake(['streamr.test/*' => Http::response('', 500)]);   // doorbells lost: the feed delivers
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('streamr-support.reply', ['id' => $this->ticket->id]), ['body' => 'First answer']);
        $this->actingAs($admin)->post(route('streamr-support.status', ['id' => $this->ticket->id, 'status' => 'closed']));

        $data = $this->signedGet('/api/streamr/v1/support/updates', ['since' => '0', 'limit' => 100])->assertOk()->json('data');

        $this->assertCount(2, $data['items']);
        $this->assertSame(['message', 'status'], array_column($data['items'], 'type'));
        $this->assertSame('6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f', $data['items'][0]['ticket_uuid']);
        $this->assertSame(['First answer', 'mother', null, 'text'], [$data['items'][0]['message']['body'], $data['items'][0]['message']['origin'],
            $data['items'][0]['message']['author_id'], $data['items'][0]['message']['body_format']]);
        $this->assertSame(['closed', 'mother:Test Admin'], [$data['items'][1]['status'], $data['items'][1]['changed_by']]);
        $this->assertStringEndsWith('+08:00', $data['items'][1]['changed_at']);
        $lastId = (string) StreamrDeskMessage::where('origin', 'mother')->max('id');
        $this->assertSame($lastId, $data['next_cursor']);
        $this->assertSame(0, StreamrDeskMessage::where('origin', 'mother')->whereNull('pulledAt')->count());

        // From the cursor on: nothing new. Streamr's own rows are never in the feed.
        $this->signedGet('/api/streamr/v1/support/updates', ['since' => $lastId])->assertOk()
            ->assertJsonPath('data', ['items' => [], 'next_cursor' => $lastId]);
        $this->signedGet('/api/streamr/v1/support/updates', ['limit' => 1])->assertOk()->assertJsonCount(1, 'data.items');
        $this->signedGet('/api/streamr/v1/support/updates', ['limit' => 500])->assertStatus(422);
    }

    public function test_the_list_filters_and_counts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('streamr-support.index'))->assertOk()->assertSee('SR-2026-0007');
        $this->actingAs($admin)->get(route('streamr-support.index', ['status' => 'closed']))->assertOk()->assertDontSee('SR-2026-0007');
        $this->actingAs($admin)->get(route('streamr-support.index', ['status' => 'all', 'q' => 'stopped']))->assertOk()->assertSee('SR-2026-0007');
        $this->actingAs($admin)->get(route('streamr-support.index', ['status' => 'all', 'client' => 42]))->assertOk()->assertSee('SR-2026-0007');
        $this->actingAs($admin)->get(route('streamr-support.index', ['status' => 'all', 'client' => 43]))->assertOk()->assertDontSee('SR-2026-0007');
    }
}
