<?php

declare(strict_types=1);

namespace App\Analytics\Compiler\Dialects;

use App\Analytics\Support\Exceptions\UnsupportedAggregateException;
use App\Analytics\Support\Exceptions\UnsupportedTimeGrainException;

final class PostgresDialect implements SqlDialect
{
    public function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    public function dateTrunc(string $grain, string $expression): string
    {
        return match ($grain) {
            'day', 'week', 'month', 'quarter', 'year' => "DATE_TRUNC('{$grain}', {$expression})",
            default => throw new UnsupportedTimeGrainException($grain, $this->name()),
        };
    }

    public function limitOffset(?int $limit, ?int $offset): string
    {
        $sql = '';

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        return $sql;
    }

    public function aggregate(string $type, string $expression): string
    {
        return match ($type) {
            'count' => "COUNT({$expression})",
            'count_distinct' => "COUNT(DISTINCT {$expression})",
            'sum' => "SUM({$expression})",
            'avg' => "AVG({$expression})",
            'min' => "MIN({$expression})",
            'max' => "MAX({$expression})",
            default => throw new UnsupportedAggregateException($type, $this->name()),
        };
    }

    public function placeholder(int $position): string
    {
        return '?';
    }

    public function name(): string
    {
        return 'postgres';
    }
}
