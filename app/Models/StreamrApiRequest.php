<?php

namespace App\Models;

/**
 * One call between this app and Streamr (streamr_api_requests), for Streamr ›
 * Link status: direction in/out, method, path, status, error code, time
 * taken. No bodies. Written by App\Services\Streamr\StreamrApiLog.
 */
class StreamrApiRequest extends BaseModel
{
    protected $table = 'streamr_api_requests';

    public const UPDATED_AT = null;

    protected $fillable = [
        'direction', 'method', 'path', 'status', 'errorCode', 'durationMs', 'requestId', 'idempotencyKey', 'ip', 'note',
    ];

    protected $casts = [
        'status' => 'integer',
        'durationMs' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
}
