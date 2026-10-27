<?php

namespace App\Services\Acumbamail;

use RuntimeException;

/**
 * Acumbamail did not do what was asked. status is the HTTP status (0 when it
 * could not be reached): 401 = the key is wrong, 429 = too many calls for now.
 */
class AcumbamailException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message, $status);
    }

    /** The key is wrong: every other call would fail the same way. */
    public function isAuthFailure(): bool
    {
        return $this->status === 401;
    }

    /** Too many calls: stop for now, the next sync carries on. */
    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }

    /** About this one subscriber (a bad address), not about the connection or the account. */
    public function isAboutTheSubscriber(): bool
    {
        return $this->status === 400;
    }
}
