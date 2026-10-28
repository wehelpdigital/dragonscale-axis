<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The studio website's bearer token (config games.site.token, GAMES_SITE_TOKEN)
 * for /api/games-site/v1/*: 503 until one is set, 401 for a missing or wrong one.
 */
class GamesSiteToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) config('games.site.token'));

        if ($token === '') {
            return response()->json(['message' => 'The studio website is not set up in DS AXIS (GAMES_SITE_TOKEN).'], 503);
        }

        $given = (string) $request->bearerToken();

        if ($given === '' || ! hash_equals($token, $given)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
