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
        // Only check for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = session()->getId();

            // Check if the session ID matches the one stored in the database
            if ($user->session_id && $user->session_id !== $currentSessionId) {
                // Log the session invalidation
                Log::info('Single session enforcement: User logged out due to new session', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'old_session' => substr($user->session_id, 0, 10) . '...',
                    'new_session' => substr($currentSessionId, 0, 10) . '...',
                ]);

                // Logout the user from this session
                Auth::logout();

                // Invalidate and regenerate session
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirect to login with message
                return redirect()->route('login')
                    ->with('warning', 'Your session has been terminated because you logged in from another device or browser. Only one active session is allowed at a time.')
                    ->withErrors(['email' => 'Na-logout ka dahil may nag-login gamit ang account mo sa ibang device o browser.']);
            }
        }

        return $next($request);
    }
}
