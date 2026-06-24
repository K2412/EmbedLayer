<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines;

use App\Analytics\Pipelines\Pipes\ApplyAccessPolicies;
use App\Analytics\Pipelines\Pipes\CheckCache;
use App\Analytics\Pipelines\Pipes\ExecuteQuery;
use App\Analytics\Pipelines\Pipes\NormalizeResult;
use App\Analytics\Pipelines\Pipes\RecordQueryRun;
use App\Analytics\Pipelines\Pipes\ResolveModel;
use App\Analytics\Pipelines\Pipes\StoreResultCache;
use App\Analytics\Pipelines\Pipes\ValidateQueryPermissions;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Models\Chart;
use App\Models\Dashboard;
use RuntimeException;
use Throwable;

/**
 * Plan §10. Owns the eight-step pipeline that turns a {@see SemanticQuery}
 * into a {@see SemanticResult}: resolve model, validate permissions, apply
 * access policies, check cache, execute, normalize, store cache, record run.
 *
 * The pipeline is a simple imperative composition rather than Laravel's
 * Pipeline facade because each pipe needs to read AND write the shared
 * {@see PipelineState}, and the cache pipe needs to short-circuit cleanly.
 */
final readonly class QueryExecutionPipeline
{
    public function __construct(
        private ResolveModel $resolveModel,
        private ValidateQueryPermissions $validateQueryPermissions,
        private ApplyAccessPolicies $applyAccessPolicies,
        private CheckCache $checkCache,
        private ExecuteQuery $executeQuery,
        private NormalizeResult $normalizeResult,
        private StoreResultCache $storeResultCache,
        private RecordQueryRun $recordQueryRun,
    ) {}

    public function execute(
        ProviderContext $context,
        SemanticQuery $query,
        ?Dashboard $dashboard = null,
        ?Chart $chart = null,
    ): SemanticResult {
        $state = new PipelineState(
            context: $context,
            query: $query,
            dashboard: $dashboard,
            chart: $chart,
        );

        try {
            ($this->resolveModel)($state);
            ($this->validateQueryPermissions)($state);
            ($this->applyAccessPolicies)($state);
            ($this->checkCache)($state);
            ($this->executeQuery)($state);
            ($this->normalizeResult)($state);
            ($this->storeResultCache)($state);
        } catch (Throwable $e) {
            ($this->recordQueryRun)($state, $e);
            throw $e;
        }

        ($this->recordQueryRun)($state);

        if ($state->result === null) {
            throw new RuntimeException('QueryExecutionPipeline produced no result.');
        }

        return $state->result;
    }
}
