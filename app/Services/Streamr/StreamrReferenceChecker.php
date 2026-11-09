<?php

namespace App\Services\Streamr;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Has this payment reference (or this very receipt file) been used before,
 * by any of the ventures paid into the same wallets? Looks in anee.io's
 * as_orders, the games web checkout (games_payments), every /ecom-orders
 * payment and order, and the receipts Streamr already sent here.
 *
 * References are compared normalised (A-Z and 0-9 only, upper case, a leading
 * "Ref No." style label dropped), so "1023 796 539709" matches "1023796539709".
 * Candidates are found with LIKE on their last six characters, then compared
 * in PHP. A table that does not exist on this host is skipped. Streamr waits
 * 5 seconds at most, so after 2 seconds the remaining tables are skipped.
 */
final class StreamrReferenceChecker
{
    public const SOURCES = ['as_orders', 'games_payments', 'ecom_order_payments', 'ecom_orders', 'streamr_payment_proofs'];

    private const BUDGET_SECONDS = 2.0;

    private array $tables = [];

    private float $deadline = 0.0;

    public static function normalize(?string $ref): string
    {
        $raw = trim((string) $ref);
        $raw = (string) preg_replace('/^(ref(erence)?|trace|transaction|txn)\.?\s*(no\.?|number|id|#)?\s*[:.#]?\s*/i', '', $raw);
        $key = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        // Already squeezed by the caller ("REFNO1023796539709"): drop the label too.
        return (string) preg_replace('/^(REFERENCENO|REFERENCENUMBER|REFNO|REFNUMBER)(?=\d)/', '', $key);
    }

    /** @return array{used: bool, sources: list<string>} */
    public function check(?string $refKey, ?string $sha256, ?string $excludeNumber = null): array
    {
        $this->deadline = microtime(true) + self::BUDGET_SECONDS;
        $ref = self::normalize($refKey);
        $sha = strtolower(trim((string) $sha256));
        $sha = preg_match('/^[a-f0-9]{64}$/', $sha) ? $sha : '';
        $exclude = trim((string) $excludeNumber);
        $excludeOrderId = $exclude !== '' && $this->has('ecom_orders')
            ? DB::table('ecom_orders')->where('orderNumber', $exclude)->value('id')
            : null;

        $found = [];
        foreach (self::SOURCES as $source) {
            if (microtime(true) > $this->deadline) {
                break;
            }
            try {
                if ($this->has($source) && $this->hit($source, $ref, $sha, $exclude, $excludeOrderId)) {
                    $found[] = $source;
                }
            } catch (\Throwable $e) {
                // A table shaped differently on this host: say nothing about it.
            }
        }

        return ['used' => $found !== [], 'sources' => $found];
    }

    private function hit(string $source, string $ref, string $sha, string $exclude, $excludeOrderId): bool
    {
        switch ($source) {
            case 'as_orders':
                return ($ref !== '' && $this->refIn('as_orders', 'refNumber', $ref, null, 'aiReport'))
                    || ($sha !== '' && $this->column('as_orders', 'proofSha') && DB::table('as_orders')->where('proofSha', $sha)->exists());

            case 'games_payments':
                return ($ref !== '' && $this->refIn('games_payments', 'refNumber', $ref, fn ($q) => $q->where('delete_status', 'active'), 'aiReport'))
                    || ($sha !== '' && DB::table('games_payments')->where('delete_status', 'active')->where('proofSha', $sha)->exists());

            case 'ecom_order_payments':
                return $ref !== '' && $this->refIn('ecom_order_payments', 'referenceNumber', $ref, function ($q) use ($excludeOrderId) {
                    $q->where('deleteStatus', 1);
                    if ($excludeOrderId) {
                        $q->where('orderId', '!=', $excludeOrderId);
                    }
                });

            case 'ecom_orders':
                return $ref !== '' && $this->refIn('ecom_orders', 'paymentReferenceNumber', $ref, function ($q) use ($exclude) {
                    $q->where('deleteStatus', 1);
                    if ($exclude !== '') {
                        $q->where('orderNumber', '!=', $exclude);
                    }
                });

            case 'streamr_payment_proofs':
                $scope = function ($q) use ($exclude) {
                    $q->where('delete_status', 'active');
                    if ($exclude !== '') {
                        $q->where('orderNumber', '!=', $exclude);
                    }
                };

                return ($ref !== '' && $this->refIn('streamr_payment_proofs', 'refKey', $ref, $scope))
                    || ($sha !== '' && DB::table('streamr_payment_proofs')->where($scope)->where('sha256', $sha)->exists());
        }

        return false;
    }

    /** Whether $ref appears in $table.$column (normalised), or as read.ref in its JSON report column. */
    private function refIn(string $table, string $column, string $ref, ?callable $scope, ?string $reportColumn = null): bool
    {
        if (! $this->column($table, $column)) {
            return false;
        }
        $needle = '%' . substr($ref, -6) . '%';
        $report = $reportColumn && $this->column($table, $reportColumn) ? $reportColumn : null;

        $rows = DB::table($table)
            ->when($scope, fn ($q) => $q->where($scope))
            ->where(function ($q) use ($column, $needle, $report) {
                $q->where($column, 'like', $needle);
                if ($report) {
                    $q->orWhere($report, 'like', $needle);
                }
            })
            ->limit(200)
            ->get(array_values(array_filter([$column, $report])));

        foreach ($rows as $row) {
            if (self::normalize($row->{$column} ?? null) === $ref) {
                return true;
            }
            if ($report) {
                $decoded = json_decode((string) ($row->{$report} ?? ''), true);
                $read = is_array($decoded) ? ($decoded['read']['ref'] ?? null) : null;
                if (is_scalar($read) && self::normalize((string) $read) === $ref) {
                    return true;
                }
            }
        }

        return false;
    }

    private function has(string $table): bool
    {
        if (! array_key_exists($table, $this->tables)) {
            try {
                $this->tables[$table] = Schema::hasTable($table) ? [] : false;
            } catch (\Throwable $e) {
                $this->tables[$table] = false;
            }
        }

        return $this->tables[$table] !== false;
    }

    private function column(string $table, string $column): bool
    {
        if (! $this->has($table)) {
            return false;
        }
        if (! array_key_exists($column, $this->tables[$table])) {
            try {
                $this->tables[$table][$column] = Schema::hasColumn($table, $column);
            } catch (\Throwable $e) {
                $this->tables[$table][$column] = false;
            }
        }

        return $this->tables[$table][$column];
    }
}
