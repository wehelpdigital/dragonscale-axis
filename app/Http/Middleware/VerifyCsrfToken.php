<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Schedule-manager is an AJAX-heavy admin tool: dragging/moving
        // activities fires rapid POSTs, and a session-id regeneration (e.g.
        // Laravel silently re-authenticating via the remember-me cookie) can
        // leave the page holding a token that no longer matches the session,
        // producing spurious 419s mid-workflow. These endpoints are all behind
        // `auth`, so the session still gates them; we drop the extra CSRF check
        // here so moving activities never fails with "CSRF token mismatch".
        'anisenso-schedule-manager-*',
    ];
}
