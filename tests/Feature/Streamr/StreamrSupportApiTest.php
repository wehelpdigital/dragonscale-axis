<?php

namespace Tests\Feature\Streamr;

use App\Models\StreamrDeskMessage;
use App\Models\StreamrDeskTicket;

/**
 * Streamr's support pushes: POST /support/tickets (with its first message),
 * POST /support/tickets/{uuid}/messages (a ticket object may come along when
 * the ticket push is still on its way), PUT /support/tickets/{uuid}/status
 * (the newest change wins). All idempotent by uuid.
 */
class StreamrSupportApiTest extends StreamrTestCase
{
    public const TICKET = '6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f';

    private function message(string $uuid, array $over = []): array
    {
        return array_replace([
            'uuid' => $uuid, 'author_type' => 'client', 'author_id' => 42, 'author_name' => 'Lia Santos', 'origin' => 'streamr',
            'body' => "Hi! My live stopped.\nCan you check?", 'body_format' => 'text',
            'attachments' => [['url' => 'https://streamr.test/media/c/abc123', 'name' => 'screen.png', 'mime' => 'image/png', 'size' => 12345]],
            'is_internal' => false, 'created_at' => now('Asia/Manila')->toIso8601String(),
        ], $over);
    }

    private function ticket(array $over = []): array
    {
        return array_replace([
            'uuid' => self::TICKET, 'number' => 'SR-2026-0007', 'streamr_user_id' => 42,
            'client' => ['name' => 'Lia Santos', 'email' => 'lia@example.ph', 'store' => "Lia's Preloved Bags"],
            'subject' => 'Live stopped', 'category' => 'live', 'priority' => 'high', 'status' => 'open',
            'created_at' => now('Asia/Manila')->subMinute()->toIso8601String(),
        ], $over);
    }

    public function test_a_ticket_and_its_first_message_are_stored_once(): void
    {
        $payload = $this->ticket(['message' => $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51')]);

        $this->signedJson('POST', '/api/streamr/v1/support/tickets', $payload)->assertOk()->assertJsonPath('data', ['stored' => true, 'duplicate' => false]);
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', $payload)->assertOk()->assertJsonPath('data', ['stored' => false, 'duplicate' => true]);

        $ticket = StreamrDeskTicket::firstOrFail();
        $this->assertSame(['SR-2026-0007', 42, 'Live stopped', 'live', 'high', 'open', 1, 'client'],
            [$ticket->ticketNumber, $ticket->streamrUserId, $ticket->subject, $ticket->category, $ticket->priority, $ticket->status, $ticket->adminUnread, $ticket->lastMessageBy]);
        $this->assertSame(['Lia Santos', 'lia@example.ph', "Lia's Preloved Bags"], [$ticket->clientName, $ticket->clientEmail, $ticket->storeName]);
        $this->assertSame(1, StreamrDeskMessage::count());
        $message = StreamrDeskMessage::firstOrFail();
        $this->assertSame(['streamr', 'client', 'message', null], [$message->origin, $message->authorType, $message->kind, $message->pulledAt]);
        $this->assertSame('https://streamr.test/media/c/abc123', $message->attachments[0]['url']);
    }

    public function test_messages_change_the_ticket_like_streamr_does(): void
    {
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', $this->ticket(['message' => $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51')]))->assertOk();

        // A Streamr admin answers: answered, the client has one unread.
        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages',
            $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e52', ['author_type' => 'admin', 'author_name' => 'Ana Cruz', 'body' => '<p>Restarted it.</p>', 'body_format' => 'html']))
            ->assertOk()->assertJsonPath('data', ['stored' => true, 'duplicate' => false]);
        $ticket = StreamrDeskTicket::firstOrFail();
        $this->assertSame(['answered', 1, 'admin'], [$ticket->status, $ticket->clientUnread, $ticket->lastMessageBy]);

        // An internal note changes nothing.
        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages',
            ['message' => $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e53', ['author_type' => 'admin', 'is_internal' => true, 'body' => 'VIP client'])])
            ->assertOk();
        $this->assertSame(['answered', 1], [StreamrDeskTicket::firstOrFail()->status, StreamrDeskTicket::firstOrFail()->clientUnread]);

        // The client writes again: open, one more unread for the admins.
        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages', $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e54'))->assertOk();
        $ticket = StreamrDeskTicket::firstOrFail();
        $this->assertSame(['open', 2, 'client'], [$ticket->status, $ticket->adminUnread, $ticket->lastMessageBy]);

        // Repeated: a duplicate.
        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages', $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e54'))
            ->assertOk()->assertJsonPath('data', ['stored' => false, 'duplicate' => true]);
        $this->assertSame(4, StreamrDeskMessage::count());
    }

    public function test_a_message_for_an_unknown_ticket_creates_it_from_the_ticket_object(): void
    {
        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages', $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51'))
            ->assertStatus(404)->assertJsonPath('error.code', 'unknown_ticket');

        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages',
            $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51') + ['ticket' => $this->ticket()])
            ->assertOk()->assertJsonPath('data.stored', true);

        $this->assertSame('SR-2026-0007', StreamrDeskTicket::firstOrFail()->ticketNumber);
        $this->assertSame(1, StreamrDeskMessage::count());
    }

    public function test_status_changes_apply_when_newer(): void
    {
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', $this->ticket())->assertOk();

        $this->signedJson('PUT', '/api/streamr/v1/support/tickets/' . self::TICKET . '/status',
            ['status' => 'closed', 'changed_at' => now('Asia/Manila')->toIso8601String(), 'changed_by' => 'streamr:admin:3'])
            ->assertOk()->assertJsonPath('data', ['applied' => true]);
        $this->assertSame(['closed', 'streamr:admin:3'], [StreamrDeskTicket::firstOrFail()->status, StreamrDeskTicket::firstOrFail()->statusChangedBy]);
        $row = StreamrDeskMessage::where('kind', 'status')->firstOrFail();
        $this->assertSame(['streamr', 'closed'], [$row->origin, $row->meta['status']]);

        // An older change loses.
        $this->signedJson('PUT', '/api/streamr/v1/support/tickets/' . self::TICKET . '/status',
            ['status' => 'open', 'changed_at' => now('Asia/Manila')->subHour()->toIso8601String(), 'changed_by' => 'client'])
            ->assertOk()->assertJsonPath('data', ['applied' => false]);
        $this->assertSame('closed', StreamrDeskTicket::firstOrFail()->status);

        $this->signedJson('PUT', '/api/streamr/v1/support/tickets/7a1c0d2e-3b4a-4c5d-8e9f-000000000000/status',
            ['status' => 'open', 'changed_at' => now('Asia/Manila')->toIso8601String(), 'changed_by' => 'client'])
            ->assertStatus(404)->assertJsonPath('error.code', 'unknown_ticket');
    }

    public function test_invalid_support_payloads_are_422(): void
    {
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', $this->ticket(['priority' => 'whenever']))
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['priority']]]);

        $this->signedJson('POST', '/api/streamr/v1/support/tickets', $this->ticket())->assertOk();
        $this->signedJson('POST', '/api/streamr/v1/support/tickets/' . self::TICKET . '/messages',
            $this->message('7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e55', ['attachments' => [['url' => 'javascript:alert(1)', 'name' => 'x']]]))
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['attachments.0.url']]]);
    }
}
