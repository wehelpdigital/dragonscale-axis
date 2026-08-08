<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Railway (and any similar PaaS edge) terminates TLS and forwards the
     * request to the container over plain HTTP, passing the original scheme in
     * X-Forwarded-Proto. Those headers are only honoured when the proxy is
     * trusted, so leaving this null makes Laravel build every asset()/url()
     * with http:// on an https:// page and the browser blocks them as mixed
     * content. The proxy IP is assigned dynamically, so trust all of them --
     * the platform edge is the only thing that can reach the container.
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB;
}
