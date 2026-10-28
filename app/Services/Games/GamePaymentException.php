<?php

namespace App\Services\Games;

use RuntimeException;

/**
 * A web checkout step that can't be done, in words the buyer can read, with the
 * HTTP status the website's API answers with (404, 409, 410, 422, 503).
 */
class GamePaymentException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message, $status);
    }
}
