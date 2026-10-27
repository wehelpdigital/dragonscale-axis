<?php

namespace App\Services\Games;

use RuntimeException;

/**
 * The game server said no, or could not be reached. status is the HTTP status
 * (0 when there was no answer); errors holds a 422's field messages.
 */
class SalpakanApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly array $errors = [])
    {
        parent::__construct($message, $status);
    }

    /** The token is wrong or missing: nothing else will work until it is fixed. */
    public function isAuthFailure(): bool
    {
        return $this->status === 401;
    }
}
