<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;
use App\Models\QueryCacheEntry;
use Illuminate\Support\Carbon;

/**
 * Persists a freshly-executed result back to `analytics_query_cache_entries`
 * with the configured TTL. Skips on cache hits and when the pipeline already
 * has an existing row for the same key (handles races).
 */
final class StoreResultCache
{
    public function __invoke(PipelineState $state): PipelineState
    {
        if ($state->cacheHit) {
            return $state;
        }

        if ($state->result === null || $state->cacheKey === null) {
            return $state;
        }

        $ttl = (int) config('embedlayer.default_ttl_seconds', 300);
        $expiresAt = Carbon::now()->addSeconds($ttl);

        QueryCacheEntry::query()->updateOrCreate(
            ['cache_key' => $state->cacheKey],
            [
                'organization_id' => $state->context->organizationId,
                'result' => [
                    'columns' => $state->result->columns,
                    'rows' => $state->result->rows,
                    'metadata' => $state->result->metadata,
                ],
                'metadata' => [
                    'cached_at' => Carbon::now()->toIso8601String(),
                ],
                'expires_at' => $expiresAt,
                'last_accessed_at' => null,
            ],
        );

        return $state;
    }
}
