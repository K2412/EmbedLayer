<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Queries;

use App\Analytics\Pipelines\QueryExecutionPipeline;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Models\Chart;
use App\Models\Dashboard;

/**
 * Thin action wrapper around the {@see QueryExecutionPipeline}. Callers that
 * don't need to thread the dashboard / chart through the pipeline (e.g. ad-hoc
 * preview queries from the builder) can still invoke this action directly.
 */
final readonly class RunSemanticQuery
{
    public function __construct(private QueryExecutionPipeline $pipeline) {}

    public function handle(
        ProviderContext $context,
        SemanticQuery $query,
        ?Dashboard $dashboard = null,
        ?Chart $chart = null,
    ): SemanticResult {
        return $this->pipeline->execute($context, $query, $dashboard, $chart);
    }
}
