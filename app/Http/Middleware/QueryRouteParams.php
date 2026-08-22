<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin URLs carry their ids in the query string, not in the path.
 *
 * The AniSenso admin is served where a second path segment does not survive
 * the trip, so /anisenso-community-groups/45 became
 * /anisenso-community-groups?id=45 — one segment, everything else after the
 * question mark.
 *
 * That would normally mean rewriting a hundred controller methods to stop
 * taking $id and start reading the request. Instead this puts the value back
 * where the framework already looks for it: a controller's arguments are
 * resolved from the ROUTE's parameters, so writing them there keeps every
 * existing `show($id)` and `edit(Request $r, $module, $device)` working as
 * written.
 *
 * The names — and their ORDER — come from the controller method itself.
 * Laravel fills any argument it cannot match by name from what is left, in
 * order, so a middleware that guessed at a fixed list of names handed
 * toggleRestrict(string $type, int $id) its id as the type and its type as
 * the id. The method's own signature is the only list that cannot be wrong.
 */
class QueryRouteParams
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route) {
            return $next($request);
        }

        foreach ($this->scalarArguments($route) as $name) {
            if ($route->hasParameter($name)) {
                continue;               // still in the path: leave it alone
            }
            // The query string first; a body field of the same name only
            // stands in when the URL said nothing.
            $value = $request->query($name, $request->input($name));
            if ($value !== null && $value !== '') {
                $route->setParameter($name, $value);
            }
        }

        return $next($request);
    }

    /**
     * The plain (non class-hinted) arguments this route's action takes, in
     * declaration order. Anything that cannot be reflected simply yields
     * nothing, and the request goes on as it always did.
     */
    private function scalarArguments($route): array
    {
        $uses = $route->getAction('uses');
        if (! is_string($uses) || ! str_contains($uses, '@')) {
            return [];
        }

        [$class, $method] = explode('@', $uses, 2);
        if (! class_exists($class) || ! method_exists($class, $method)) {
            return [];
        }

        try {
            $names = [];
            foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();
                // Request, a model, anything the container builds: not ours.
                if ($type && ! $type->isBuiltin()) {
                    continue;
                }
                $names[] = $parameter->getName();
            }

            return $names;
        } catch (\ReflectionException $e) {
            return [];
        }
    }
}
