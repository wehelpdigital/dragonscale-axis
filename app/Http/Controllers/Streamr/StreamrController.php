<?php

namespace App\Http\Controllers\Streamr;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * What the Streamr pages share (Streamr > Sales dashboard, Clients, Payment
 * reviews, Support, Link status): the "not installed yet" guard, JSON
 * answers in the admin's {success, message, data} shape, CSV output and
 * links into Streamr. Every page is behind 'auth' like the other modules.
 */
abstract class StreamrController extends Controller
{
    public const TABLES = [
        'streamr_clients', 'streamr_client_actions', 'streamr_orders', 'streamr_payment_proofs',
        'streamr_desk_tickets', 'streamr_desk_messages', 'streamr_settings', 'streamr_api_requests',
    ];

    /** Whether this host has run the Streamr migration (its first and its last table exist). */
    protected function installed(): bool
    {
        try {
            foreach (['streamr_clients', 'streamr_api_requests'] as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function notInstalled(string $page)
    {
        return response()->view('streamr.not-installed', ['page' => $page]);
    }

    protected function json($data = null, string $message = '', int $status = 200)
    {
        return response()->json(['success' => $status < 400, 'message' => $message, 'data' => $data], $status);
    }

    protected function fail(string $message, int $status = 422, $data = null)
    {
        return response()->json(['success' => false, 'message' => $message, 'data' => $data], $status);
    }

    /** A page in Streamr's own admin, or null while STREAMR_APP_URL is not set. */
    protected function streamrUrl(string $path): ?string
    {
        $base = rtrim((string) config('streamr.app_url', ''), '/');

        return $base === '' ? null : $base . '/' . ltrim($path, '/');
    }

    /** An id from ?id= (or the form), as a positive integer, or 0. */
    protected function id($value): int
    {
        return is_scalar($value) && ctype_digit((string) $value) ? (int) $value : 0;
    }

    /**
     * Stream rows as a CSV file Excel opens as UTF-8.
     *
     * @param  iterable|callable  $rows  rows, or fn (callable $emit) that emits them (chunked)
     */
    protected function csv(string $filename, array $headers, $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            try {
                $emit = function (array $row) use ($handle) {
                    fputcsv($handle, array_map(fn ($v) => self::cell($v), $row));
                };
                if (is_callable($rows)) {
                    $rows($emit);
                } else {
                    foreach ($rows as $row) {
                        $emit((array) $row);
                    }
                }
            } catch (\Throwable $e) {
                // The headers are already sent; the file ends where it ends.
                Log::error('[Streamr] CSV export failed: ' . $e->getMessage());
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** A spreadsheet never runs a cell that starts with = + - @ as a formula. */
    private static function cell($value): string
    {
        $value = is_scalar($value) || $value === null ? (string) $value : json_encode($value);

        return preg_match('/^[=+\-@\t\r]/', $value) && ! is_numeric($value) ? "'" . $value : $value;
    }
}
