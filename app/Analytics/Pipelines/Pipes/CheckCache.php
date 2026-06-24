<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Models\QueryCacheEntry;
use Illuminate\Support\Carbon;

/**
 * Looks the query up in `analytics_query_cache_entries` (Plan §10). On a hit
 * the result is hydrated back into a {@see SemanticResult} and the pipeline
 * short-circuits — downstream pipes inspect {@see PipelineState::$cacheHit} to
 * decide whether to execute or store.
 */
final class CheckCache
{
    public function __invoke(PipelineState $state): PipelineState
    {
        $state->cacheKey = CacheKey::for($state);

        $entry = QueryCacheEntry::query()
            ->where('organization_id', $state->context->organizationId)
            ->where('cache_key', $state->cacheKey)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($entry === null) {
            return $state;
        }

        $payload = is_array($entry->result) ? $entry->result : [];

        $columns = isset($payload['columns']) && is_array($payload['columns'])
            ? array_values($payload['columns'])
            : [];

        $rows = isset($payload['rows']) && is_array($payload['rows'])
            ? array_values($payload['rows'])
            : [];

        $metadata = isset($payload['metadata']) && is_array($payload['metadata'])
            ? $payload['metadata']
            : [];

        /** @var list<array{key: string, label: string, type: string}> $columns */
        /** @var list<array<string, mixed>> $rows */
        $state->result = new SemanticResult(
            columns: $columns,
            rows: $rows,
            metadata: array_merge($metadata, ['cache_hit' => true]),
        );

        $state->cacheHit = true;

        $entry->forceFill(['last_accessed_at' => Carbon::now()])->save();

        return $state;
    }
}
