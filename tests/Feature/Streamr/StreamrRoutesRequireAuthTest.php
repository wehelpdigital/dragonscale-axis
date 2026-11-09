<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/**
 * Every Streamr page and action is behind 'auth' (the admin has no other
 * gate); a guest is sent to the login page and nothing reaches Streamr.
 * The machine API is behind StreamrApiToken instead.
 */
class StreamrRoutesRequireAuthTest extends StreamrTestCase
{
    public function test_every_web_route_sends_guests_to_the_login_page(): void
    {
        Http::fake();
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with((string) $r->getName(), 'streamr-')
            && ! str_starts_with((string) $r->getName(), 'streamr-api.'));

        $this->assertCount(21, $routes);
        foreach ($routes as $route) {
            $this->assertContains('auth', $route->gatherMiddleware(), $route->getName());
            $method = in_array('GET', $route->methods(), true) ? 'GET' : 'POST';
            $this->call($method, '/' . $route->uri())->assertRedirect(route('login'));
            $this->assertStringNotContainsString('/', $route->uri(), $route->getName() . ' keeps to one path segment');
        }
        Http::assertNothingSent();
    }

    public function test_the_api_routes_are_guarded_by_the_streamr_token(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with((string) $r->getName(), 'streamr-api.'));

        $this->assertCount(13, $routes);
        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains(\App\Http\Middleware\StreamrApiToken::class, $middleware, $route->getName());
            $this->assertContains('throttle:120,1,streamr', $middleware, $route->getName());
            $this->assertStringStartsWith('api/streamr/v1/', $route->uri());
        }
    }

    public function test_the_routes_come_before_the_catch_all(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri())->values()->all();

        $this->assertLessThan(array_search('{any}', $uris, true), array_search('streamr-health-retry', $uris, true));
    }
}
