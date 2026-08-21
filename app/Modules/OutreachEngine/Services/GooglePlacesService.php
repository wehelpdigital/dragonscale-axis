<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the legacy Google Places JSON web service (Nearby Search + Details).
 *
 * Google answers HTTP 200 for nearly everything and hides the real outcome in the
 * body's `status` field, so every call here is judged on that field and never on
 * the HTTP code alone. Each failing status becomes its own ->['error'] string,
 * prefixed with the raw status token, because the scraper has to tell a quota
 * stall (OVER_QUERY_LIMIT - wait, then retry the same cell) apart from a dead key
 * (REQUEST_DENIED - stop, a human must fix Settings). Callers classify with
 * isRetryableError() / isFatalError() instead of matching on prose.
 *
 * next_page_token is handed back as-is: it is the CALLER that must wait ~2s before
 * spending it, because Google keeps a fresh token invalid for a moment.
 */
class GooglePlacesService
{
    const NEARBY_ENDPOINT = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';
    const DETAILS_ENDPOINT = 'https://maps.googleapis.com/maps/api/place/details/json';

    /** Google rejects a Nearby Search radius above 50 km. */
    const MAX_RADIUS_METERS = 50000;

    /** Places is normally sub-second; 20s only covers a bad network day. */
    const TIMEOUT_SECONDS = 20;

    /** Fields Place Details is asked for - anything more is billed for nothing. */
    const DETAIL_FIELDS = 'formatted_phone_number,website,url';

    /** Worth another attempt later: the key and the request were both fine. */
    const RETRYABLE_STATUSES = [
        'OVER_QUERY_LIMIT',
        'UNKNOWN_ERROR',
        'HTTP_ERROR',
        'TRANSPORT_ERROR',
    ];

    /** Will keep failing identically until someone edits the settings. */
    const FATAL_STATUSES = [
        'REQUEST_DENIED',
        'NOT_CONFIGURED',
    ];

    protected OutreachSetting $settings;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Is there a Places key saved for this user?
     */
    public function isConfigured(): bool
    {
        return $this->settings->hasPlacesKey();
    }

    /**
     * Nearby Search around one point.
     *
     * @param  float       $lat
     * @param  float       $lng
     * @param  float       $radiusMeters  Clamped to Google's 50 km ceiling.
     * @param  string      $keyword       Business type, e.g. "resort".
     * @param  string|null $pageToken     Continuation token from a previous call.
     * @return array ['status'=>string,'results'=>array,'nextPageToken'=>?string,'error'=>?string]
     */
    public function nearbySearch(float $lat, float $lng, float $radiusMeters, string $keyword, ?string $pageToken = null): array
    {
        if (!$this->isConfigured()) {
            return $this->nearbyFailure(
                'NOT_CONFIGURED',
                'No Google Places API key is saved. Add one under Lead Finder > Settings.'
            );
        }

        $query = ['key' => $this->settings->googlePlacesApiKey];

        if ($pageToken !== null && $pageToken !== '') {
            // With a pagetoken Google ignores every other search parameter, so nothing
            // else is sent - repeating them cannot change the page and only risks a
            // parameter mismatch.
            $query['pagetoken'] = $pageToken;
        } else {
            // sprintf keeps the coordinate out of PHP's float notation (1.0E-5 and the
            // like), which Google's parser rejects.
            $query['location'] = sprintf('%.7F,%.7F', $lat, $lng);
            $query['radius'] = (int) max(1, min(self::MAX_RADIUS_METERS, round($radiusMeters)));

            $keyword = trim($keyword);
            if ($keyword !== '') {
                $query['keyword'] = $keyword;
            }
        }

        $call = $this->call(self::NEARBY_ENDPOINT, $query);

        if (!$call['ok']) {
            return $this->nearbyFailure($call['status'], $call['message']);
        }

        $status = $call['status'];

        // ZERO_RESULTS is a real answer, not a fault: an empty patch of map is exactly
        // what a rural grid cell looks like. Treating it as an error would fail cells
        // that worked perfectly, so it comes back as a success with no results.
        if ($status !== 'OK' && $status !== 'ZERO_RESULTS') {
            return $this->nearbyFailure($status, $this->describeStatus($status, $call['body']));
        }

        $results = $call['body']['results'] ?? null;
        $token = $call['body']['next_page_token'] ?? null;

        return [
            'status' => $status,
            'results' => is_array($results) ? $results : [],
            'nextPageToken' => (is_string($token) && $token !== '') ? $token : null,
            'error' => null,
        ];
    }

    /**
     * Place Details for one place id - phone and website for the enrichment pass.
     *
     * @return array ['status'=>string,'phone'=>?string,'website'=>?string,'mapsUrl'=>?string,'error'=>?string]
     */
    public function placeDetails(string $placeId): array
    {
        $placeId = trim($placeId);

        if ($placeId === '') {
            return $this->detailsFailure('INVALID_REQUEST', 'No place id was supplied.');
        }

        if (!$this->isConfigured()) {
            return $this->detailsFailure(
                'NOT_CONFIGURED',
                'No Google Places API key is saved. Add one under Lead Finder > Settings.'
            );
        }

        $call = $this->call(self::DETAILS_ENDPOINT, [
            'key' => $this->settings->googlePlacesApiKey,
            'place_id' => $placeId,
            'fields' => self::DETAIL_FIELDS,
        ]);

        if (!$call['ok']) {
            return $this->detailsFailure($call['status'], $call['message']);
        }

        if ($call['status'] !== 'OK') {
            return $this->detailsFailure($call['status'], $this->describeStatus($call['status'], $call['body']));
        }

        $result = $call['body']['result'] ?? null;
        $result = is_array($result) ? $result : [];

        return [
            'status' => 'OK',
            'phone' => $this->stringOrNull($result['formatted_phone_number'] ?? null),
            'website' => $this->stringOrNull($result['website'] ?? null),
            'mapsUrl' => $this->stringOrNull($result['url'] ?? null),
            'error' => null,
        ];
    }

    /**
     * Should the caller try this same request again later?
     * Reads the status token that every error string is prefixed with.
     */
    public static function isRetryableError(?string $error): bool
    {
        return in_array(static::statusOfError($error), self::RETRYABLE_STATUSES, true);
    }

    /**
     * Is this a dead-key style failure that will repeat until Settings changes?
     * A batch that sees one of these must stop rather than burn through every cell.
     */
    public static function isFatalError(?string $error): bool
    {
        return in_array(static::statusOfError($error), self::FATAL_STATUSES, true);
    }

    /**
     * Pull the leading status token back out of an error string.
     */
    public static function statusOfError(?string $error): string
    {
        $error = trim((string) $error);

        if ($error === '') {
            return '';
        }

        $position = strpos($error, ':');

        return $position === false ? $error : substr($error, 0, $position);
    }

    // ==================== INTERNALS ====================

    /**
     * One HTTP round trip, reduced to a decided outcome.
     *
     * @return array ['ok'=>bool,'status'=>string,'message'=>string,'body'=>array]
     */
    protected function call(string $endpoint, array $query): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->acceptJson()->get($endpoint, $query);
        } catch (\Throwable $e) {
            Log::warning('[OutreachEngine] Places transport failure on ' . $endpoint . ': ' . $e->getMessage());

            return [
                'ok' => false,
                'status' => 'TRANSPORT_ERROR',
                'message' => 'Could not reach Google Places (' . $e->getMessage() . '). Try again shortly.',
                'body' => [],
            ];
        }

        if (!$response->successful()) {
            Log::warning('[OutreachEngine] Places answered HTTP ' . $response->status() . ' on ' . $endpoint);

            return [
                'ok' => false,
                'status' => 'HTTP_ERROR',
                'message' => 'Google Places answered HTTP ' . $response->status() . '. Try again shortly.',
                'body' => [],
            ];
        }

        $body = $response->json();

        if (!is_array($body)) {
            Log::warning('[OutreachEngine] Places returned an unreadable body on ' . $endpoint);

            return [
                'ok' => false,
                'status' => 'HTTP_ERROR',
                'message' => 'Google Places returned a response we could not read as JSON.',
                'body' => [],
            ];
        }

        return [
            'ok' => true,
            'status' => (string) ($body['status'] ?? 'UNKNOWN_ERROR'),
            'message' => '',
            'body' => $body,
        ];
    }

    /**
     * A distinct, operator-readable sentence per Google status.
     * Google's own error_message is appended when it sends one - it usually names
     * the exact restriction that blocked the key.
     */
    protected function describeStatus(string $status, array $body): string
    {
        switch ($status) {
            case 'OVER_QUERY_LIMIT':
                $message = 'Google is refusing calls for now - the daily quota or the per-second rate limit is exhausted. This cell can be retried later.';
                break;

            case 'REQUEST_DENIED':
                $message = 'Google refused the API key. It is invalid, restricted to other referrers/IPs, or the Places API is not enabled on that project. Fix the key under Lead Finder > Settings.';
                break;

            case 'INVALID_REQUEST':
                $message = 'Google rejected the request parameters. While paging, this usually means the next page token was spent too soon or has already expired.';
                break;

            case 'NOT_FOUND':
                $message = 'Google no longer knows this place id - the listing was removed or merged.';
                break;

            case 'ZERO_RESULTS':
                $message = 'Google found nothing for this lookup.';
                break;

            case 'UNKNOWN_ERROR':
                $message = 'Google hit a server-side error. The very same request may succeed on a retry.';
                break;

            default:
                $message = 'Google returned an unexpected status.';
                break;
        }

        $detail = trim((string) ($body['error_message'] ?? ''));

        return $detail !== '' ? $message . ' Google said: ' . $detail : $message;
    }

    /**
     * Failed Nearby Search, in the shape callers already destructure.
     */
    protected function nearbyFailure(string $status, string $message): array
    {
        $error = $status . ': ' . $message;
        Log::warning('[OutreachEngine] Places nearbySearch failed - ' . $error);

        return [
            'status' => $status,
            'results' => [],
            'nextPageToken' => null,
            'error' => $error,
        ];
    }

    /**
     * Failed Place Details, in the shape callers already destructure.
     */
    protected function detailsFailure(string $status, string $message): array
    {
        $error = $status . ': ' . $message;
        Log::warning('[OutreachEngine] Places placeDetails failed - ' . $error);

        return [
            'status' => $status,
            'phone' => null,
            'website' => null,
            'mapsUrl' => null,
            'error' => $error,
        ];
    }

    /**
     * Trimmed string, or null for anything empty or non-scalar.
     */
    protected function stringOrNull($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
