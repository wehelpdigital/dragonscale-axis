<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SingleSession
{
    /**
     * Handle an incoming request.
     *
     * Ensures only one active session per user. If a user logs in from another
     * device/browser, the previous session is invalidated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip enforcement entirely for the auth flow itself. Otherwise the
        // login POST can get caught mid-flight: the user types credentials,
        // submits, this middleware sees a session_id mismatch (from a prior
        // device), invalidates + regenerateToken()s, and the now-stale CSRF
        // token from the just-rendered login page yields a 419 on retry.
        if ($request->routeIs('login', 'logout', 'password.*', 'register')) {
            return $next($request);
        }

        // Skip non-idempotent requests too — kicking the user out mid-POST
        // (form submit, AJAX action) loses their unsaved work AND tends to
        // produce a 419 storm. Wait for a safe GET to enforce.
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // DB errors from the remote MySQL (intermittent unreachability, 1040
        // too-many-connections) used to bubble out of Auth::check() and
        // crash the request. Treat any DB hiccup as "skip the check this
        // round" so a transient blip doesn't masquerade as a session bug.
        try {
            if (!Auth::check()) {
                return $next($request);
            }
            $user = Auth::user();
            // Compare a STABLE token stored in the session (not the raw session
            // id). Laravel regenerates the session id when it re-authenticates
            // via the remember-me cookie, and comparing raw ids would boot the
            // user on every such regeneration.
            $sessionToken = $request->session()->get('single_session_token');
        } catch (\Throwable $e) {
            Log::warning('SingleSession: skipping enforcement due to error', [
                'error' => $e->getMessage(),
            ]);
            return $next($request);
        }

        // Per-user opt-out: admin can grant "allow multiple logins" to a
        // specific account (see users.edit toggle). When set, the account
        // can be logged in from any number of devices simultaneously — no
        // session comparison, no forced logout.
        if (!empty($user->allow_multiple_logins)) {
            return $next($request);
        }

        // The session was re-established without our token — this happens when
        // Laravel silently re-authenticates via the remember-me cookie (a fresh
        // session with no data) or for sessions that predate this token scheme.
        // Re-adopt the session for this device instead of bouncing the user; a
        // genuine login elsewhere goes through the login controller, which sets
        // a brand-new token in the DB and would then be caught below.
        if ($sessionToken === null) {
            $token = \Illuminate\Support\Str::random(40);
            $request->session()->put('single_session_token', $token);
            try {
                $user->forceFill(['session_id' => $token])->save();
            } catch (\Throwable $e) {
                // Non-fatal — worst case the next request re-adopts again.
            }
            return $next($request);
        }

        if ($user->session_id && $user->session_id !== $sessionToken) {
            Log::info('Single session enforcement: User logged out due to new session', [
                'user_id'     => $user->id,
                'user_email'  => $user->email,
                'old_token'   => substr((string) $user->session_id, 0, 8) . '...',
                'this_token'  => substr((string) $sessionToken, 0, 8) . '...',
            ]);

            // Log the user out, but DO NOT regenerateToken() — the freshly
            // rendered /login page will already carry a token bound to the
            // newly-issued session cookie, so rotating again here would
            // immediately invalidate it and 419 the user's first POST.
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')
                ->with('warning', 'Your session has been terminated because you logged in from another device or browser. Only one active session is allowed at a time.')
                ->withErrors(['email' => 'Na-logout ka dahil may nag-login gamit ang account mo sa ibang device o browser.']);
        }

        return $next($request);
    }
}
