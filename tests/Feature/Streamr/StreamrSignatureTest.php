<?php

namespace Tests\Feature\Streamr;

use App\Services\Streamr\StreamrSignature;
use Illuminate\Http\Request;

/**
 * The four test vectors of Streamr's architecture §10.1 (secret test-secret,
 * timestamp 1794096000), reproduced by this app's twin of the signer, both
 * from the canonical strings and from requests as they arrive.
 */
class StreamrSignatureTest extends StreamrTestCase
{
    private const TS = 1794096000;

    public function test_the_four_contract_vectors(): void
    {
        $this->assertSame('bdfc15f1bc656d32c471a0375d667686853f76e89b448d79c40c83f54ad630e9',
            StreamrSignature::sign('test-secret', self::TS, '{"ping":true}'));

        $this->assertSame('007b79945f90a0fe6da6531f6e270373cc5abe84e092f0564a3e6e4bf31cd3d3',
            StreamrSignature::sign('test-secret', self::TS, StreamrSignature::requestLine('GET', '/api/streamr/v1/orders/STR-20261108-7KQ4')));

        $line = StreamrSignature::requestLine('GET', '/api/streamr/v1/orders', ['numbers' => ['STR-20261108-7KQ4', 'STR-20261108-9ZZZ']]);
        $this->assertSame('GET /api/streamr/v1/orders?numbers%5B0%5D=STR-20261108-7KQ4&numbers%5B1%5D=STR-20261108-9ZZZ', $line);
        $this->assertSame('60c1d7cd80d105e0e164611e8aff60a6a997071bde8671abbc12caaa44213068', StreamrSignature::sign('test-secret', self::TS, $line));

        $line = StreamrSignature::requestLine('GET', '/api/streamr/v1/support/updates', ['since' => 'c_1834', 'limit' => 100]);
        $this->assertSame('GET /api/streamr/v1/support/updates?limit=100&since=c_1834', $line);
        $this->assertSame('4b972842405423f16c8bff71b43f73eab5f615020020e548f49507a866dd7ac4', StreamrSignature::sign('test-secret', self::TS, $line));
    }

    public function test_received_requests_rebuild_the_same_canonical_strings(): void
    {
        $get = Request::create('/api/streamr/v1/orders?numbers%5B0%5D=STR-20261108-7KQ4&numbers%5B1%5D=STR-20261108-9ZZZ', 'GET');
        $this->assertSame('60c1d7cd80d105e0e164611e8aff60a6a997071bde8671abbc12caaa44213068',
            StreamrSignature::sign('test-secret', self::TS, StreamrSignature::canonical($get)));

        // Keys arrive in any order; the canonical form sorts them.
        $updates = Request::create('/api/streamr/v1/support/updates?since=c_1834&limit=100', 'GET');
        $this->assertSame('4b972842405423f16c8bff71b43f73eab5f615020020e548f49507a866dd7ac4',
            StreamrSignature::sign('test-secret', self::TS, StreamrSignature::canonical($updates)));

        // Brackets not percent-encoded by a proxy: still the same string.
        $raw = Request::create('/api/streamr/v1/orders?numbers[0]=STR-20261108-7KQ4&numbers[1]=STR-20261108-9ZZZ', 'GET');
        $this->assertSame('numbers[0]=STR-20261108-7KQ4&numbers[1]=STR-20261108-9ZZZ', $raw->server->get('QUERY_STRING'));
        $this->assertSame('GET /api/streamr/v1/orders?numbers%5B0%5D=STR-20261108-7KQ4&numbers%5B1%5D=STR-20261108-9ZZZ', StreamrSignature::canonical($raw));

        $one = Request::create('/api/streamr/v1/orders/STR-20261108-7KQ4', 'GET');
        $this->assertSame('007b79945f90a0fe6da6531f6e270373cc5abe84e092f0564a3e6e4bf31cd3d3',
            StreamrSignature::sign('test-secret', self::TS, StreamrSignature::canonical($one)));

        $json = Request::create('/api/streamr/v1/clients/42', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"ping":true}');
        $this->assertSame('bdfc15f1bc656d32c471a0375d667686853f76e89b448d79c40c83f54ad630e9',
            StreamrSignature::sign('test-secret', self::TS, StreamrSignature::canonical($json)));

        // Multipart: the `payload` field is what is signed.
        $multipart = Request::create('/api/streamr/v1/orders', 'POST', ['payload' => '{"ping":true}'], [], [], ['CONTENT_TYPE' => 'multipart/form-data; boundary=x']);
        $this->assertSame('{"ping":true}', StreamrSignature::canonical($multipart));
    }

    public function test_outbound_headers_sign_the_body_or_the_request_line(): void
    {
        $headers = StreamrSignature::headers('test-secret', 'POST', '/api/mother/orders/changed', '{"ping":true}', [], self::TS);
        $this->assertSame('Bearer test-secret', $headers['Authorization']);
        $this->assertSame((string) self::TS, $headers['X-Streamr-Timestamp']);
        $this->assertSame('bdfc15f1bc656d32c471a0375d667686853f76e89b448d79c40c83f54ad630e9', $headers['X-Streamr-Signature']);
        $this->assertSame('DS-AXIS-Streamr/1.0', $headers['User-Agent']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $headers['X-Streamr-Request-Id']);

        $get = StreamrSignature::headers('test-secret', 'GET', '/api/streamr/v1/orders/STR-20261108-7KQ4', null, [], self::TS);
        $this->assertSame('007b79945f90a0fe6da6531f6e270373cc5abe84e092f0564a3e6e4bf31cd3d3', $get['X-Streamr-Signature']);
    }
}
