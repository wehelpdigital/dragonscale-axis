<?php

namespace App\Modules\OutreachEngine\Support;

use RuntimeException;

/**
 * Raised when the module cannot proceed for a reason the operator can fix -
 * missing API keys, unconfigured SMTP, a region we hold no bounds for.
 *
 * Controllers catch this and surface getMessage() straight to the admin, so
 * keep messages short, specific and free of internals.
 */
class OutreachException extends RuntimeException
{
    //
}
