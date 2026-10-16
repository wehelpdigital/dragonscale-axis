<?php

namespace App\Services\RssFeeds;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Every request the RSS Feeds module makes goes through here.
 *
 * The addresses are typed by admins or come from feeds, and the server then
 * fetches them, so an address (or a redirect from one) that is not on the
 * public internet is refused before anything is sent: loopback, private and
 * shared ranges, link-local (the cloud metadata address), and IPv4 written
 * as IPv6 all fail FILTER_FLAG_GLOBAL_RANGE. Only the A records that were
 * checked are dialled (IPv4 only), and only on web ports.
 *
 * Bodies are capped at MAX_BYTES twice: on the wire (curl aborts the
 * download) and after gzip/deflate, which is undone here rather than by curl
 * so a small compressed bomb cannot unpack into gigabytes. Read a body with
 * RssHttp::text(), never ->body().
 *
 * The browser User-Agent is deliberate: several newsrooms answer a library's
 * default agent with an empty page or a 403.
 */
class RssHttp
{
    public const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36';

    private const MAX_BYTES = 8 * 1024 * 1024;

    private const PORTS = [80, 443, 8080, 8443];

    public static function get(string $url, int $timeout = 20, array $headers = []): Response
    {
        self::assertPublic($url);

        return self::client($timeout, $headers)->get($url);
    }

    public static function postForm(string $url, array $form, int $timeout = 20, array $headers = []): Response
    {
        self::assertPublic($url);

        return self::client($timeout, $headers)->asForm()->post($url, $form);
    }

    /** The response's body, unpacked, at most MAX_BYTES. */
    public static function text(Response $res): string
    {
        $body = $res->body();
        $encoding = strtolower(trim((string) $res->header('Content-Encoding')));
        $text = match ($encoding) {
            '', 'identity' => $body,
            'gzip', 'x-gzip' => @gzdecode($body, self::MAX_BYTES),
            'deflate' => @gzinflate($body, self::MAX_BYTES) ?: @gzuncompress($body, self::MAX_BYTES),
            default => throw new \RuntimeException('The site sent its page in a form this reader cannot open (' . $encoding . ').'),
        };
        if ($text === false) {
            throw new \RuntimeException('The page is too large to read, or arrived damaged.');
        }

        return $text;
    }

    /** Refuses anything that is not http(s) on a public address and a web port. */
    public static function assertPublic(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new \RuntimeException('Only http and https addresses can be read.');
        }
        if (isset($parts['port']) && ! in_array((int) $parts['port'], self::PORTS, true)) {
            throw new \RuntimeException('Only the usual web ports (80, 443, 8080, 8443) can be read.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Requests go out over IPv4 only, so an IPv6 address could not be reached anyway.
            throw new \RuntimeException('That address is not on the public internet.');
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $ips) {
            throw new \RuntimeException('The site could not be found.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE)) {
                throw new \RuntimeException('That address is not on the public internet.');
            }
        }
    }

    private static function client(int $timeout, array $headers)
    {
        return Http::timeout($timeout)
            ->connectTimeout(min(10, $timeout))
            ->withHeaders($headers + [
                'User-Agent' => self::USER_AGENT,
                'Accept-Language' => 'en-PH,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
            ])
            ->withOptions([
                'decode_content' => false,
                'allow_redirects' => [
                    'max' => 5,
                    'protocols' => ['http', 'https'],
                    'on_redirect' => function ($request, $response, $uri) {
                        self::assertPublic((string) $uri);
                    },
                ],
                'on_headers' => function ($response) {
                    if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                        throw new \RuntimeException('The page is too large to read.');
                    }
                },
                'curl' => [
                    // Dial only the IPv4 addresses assertPublic() looked at.
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    // A body with no Content-Length (chunked) is stopped here once it passes the cap.
                    CURLOPT_NOPROGRESS => false,
                    CURLOPT_XFERINFOFUNCTION => fn ($curl, $downTotal, $downNow) => $downNow > self::MAX_BYTES ? 1 : 0,
                ],
            ]);
    }
}
