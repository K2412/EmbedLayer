<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;
use App\Models\QueryRun;
use Throwable;

/**
 * Writes one `analytics_query_runs` row per query attempt (Plan §19).
 * Always called — including from the pipeline's catch block — so failed runs
 * are still observable. Errors thrown while logging are swallowed so they
 * never mask the original query exception.
 */
final class RecordQueryRun
{
    public function __invoke(PipelineState $state, ?Throwable $error = null): PipelineState
    {
        try {
            QueryRun::query()->create([
                'organization_id' => $state->context->organizationId,
                'semantic_provider_id' => $state->model?->semantic_provider_id,
                'dashboard_id' => $state->dashboard?->id,
                'chart_id' => $state->chart?->id,
                'provider_type' => 'internal',
                'model_name' => $state->model?->name ?? $state->query->model,
                'status' => $error === null ? 'ok' : 'error',
                'duration_ms' => (int) ((microtime(true) - $state->startedAt) * 1000),
                'cache_hit' => $state->cacheHit,
                'cache_key' => $state->cacheKey,
                'external_account_id' => $state->context->externalAccountId,
                'query_shape' => array_merge(
                    $state->queryRunMetadata,
                    ['query' => $state->query->jsonSerialize()],
                ),
                'error_message' => $error?->getMessage(),
            ]);
        } catch (Throwable) {
            // observability must never mask the real failure
        }

        return $state;
    }
}
