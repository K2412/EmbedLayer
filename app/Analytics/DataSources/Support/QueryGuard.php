<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Support;

use Illuminate\Database\Connection;

/**
 * Connector-level cost guardrails (Plan §17.3, §14.2):
 *   - cap result row count by appending a LIMIT to compiled SQL if absent
 *   - cap server-side execution time via a per-statement timeout
 *
 * Dialect-specific timeout syntax:
 *   - postgres: SET LOCAL statement_timeout = '<ms>ms'
 *   - mysql:    SET SESSION max_execution_time = <ms>
 *   - others:   skipped (compiler/SDK is responsible)
 */
final class QueryGuard
{
    public static function defaultRowLimit(): int
    {
        return (int) config('embedlayer.default_row_limit', 10000);
    }

    public static function defaultTimeoutMs(): int
    {
        return (int) config('embedlayer.default_query_timeout_ms', 30000);
    }

    /**
     * Append a LIMIT clause if the compiled SQL doesn't already cap rows.
     * Trims trailing semicolons/whitespace before checking.
     */
    public static function ensureRowLimit(string $sql, ?int $limit = null): string
    {
        $effective = $limit ?? self::defaultRowLimit();
        $trimmed = rtrim($sql, "; \t\n\r\0\x0B");

        return preg_match('/\\blimit\\s+\\d+/i', $trimmed) === 1
            ? $sql
            : $trimmed.' LIMIT '.$effective;
    }

    public static function applyStatementTimeout(Connection $connection, string $dialect, ?int $timeoutMs = null): void
    {
        $ms = $timeoutMs ?? self::defaultTimeoutMs();

        if ($ms <= 0) {
            return;
        }

        match ($dialect) {
            'postgres', 'pgsql' => $connection->statement("SET statement_timeout = {$ms}"),
            'mysql' => $connection->statement("SET SESSION max_execution_time = {$ms}"),
            default => null,
        };
    }
}
