<?php

namespace App\Models;

/**
 * One line of a Streamr support thread (streamr_desk_messages): a message
 * (`kind` message) or a status change (`kind` status). Rows written here
 * have `origin` mother; Streamr picks them up by doorbell or through
 * GET /api/streamr/v1/support/updates (cursor = id) and `pulledAt` is set.
 */
class StreamrDeskMessage extends BaseModel
{
    protected $table = 'streamr_desk_messages';

    protected $fillable = [
        'uuid', 'ticketId', 'ticketUuid', 'kind', 'authorType', 'authorId', 'authorName', 'origin', 'body',
        'bodyFormat', 'attachments', 'isInternal', 'meta', 'pulledAt', 'delete_status',
    ];

    protected $casts = [
        'ticketId' => 'integer',
        'authorId' => 'integer',
        'attachments' => 'array',
        'isInternal' => 'boolean',
        'meta' => 'array',
        'pulledAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function ticket()
    {
        return $this->belongsTo(StreamrDeskTicket::class, 'ticketId');
    }

    /** The body as plain text: HTML from Streamr is reduced to its words (this app has no HTML purifier). */
    public function plainBody(): string
    {
        $body = (string) $this->body;
        if ($this->bodyFormat === 'html') {
            $body = html_entity_decode(strip_tags(preg_replace('#<(br|/p|/div|/li)\b[^>]*>#i', "\n", $body)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim($body);
    }
}
