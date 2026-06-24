<?php

declare(strict_types=1);

namespace App\Analytics\Compiler\Dialects;

/**
 * Per-warehouse SQL surface used by the deterministic InternalQueryCompiler.
 *
 * Implementations MUST never interpolate user-controlled values into SQL —
 * literal data must travel through {@see placeholder()} bindings.
 */
interface SqlDialect
{
    public function quoteIdentifier(string $identifier): string;

    public function dateTrunc(string $grain, string $expression): string;

    public function limitOffset(?int $limit, ?int $offset): string;

    public function aggregate(string $type, string $expression): string;

    public function placeholder(int $position): string;

    public function name(): string;
}
